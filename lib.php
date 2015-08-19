<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    gradereport_wsexport
 * @copyright  2015 onwards Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/grade/report/lib.php');
require_once($CFG->libdir.'/tablelib.php');

class grade_report_wsexport extends grade_report {

    private $moodle_students = array(); // Moodle students.

    private $moodle_grades = array(); // Grades for course totals.
    private $other_moodle_grades = array(); // Grades for other grade items.

    private $grades_to_send = array(); // Course totals to be sent.
    private $other_grades_to_send = array(); // Grades for other grade items to be sent.

    private $not_in_remote_students = array(); // Students that are on moodle but not on remote system.

    // Um array com as contagens de alunos por problema.
    private $statistics = array('not_in_remote' => 0, 'not_in_moodle' => 0, 'ok' => 0,
                                'unformatted_grades' => 0, 'updated_on_remote' => 0);

    private $sendresults = array(); // Um array (matricula => msg) com as msgs de erro de envio de notas.

    private $cannot_submit = false; // If there is something preventing grades sending, set to true.

    private $using_metacourse_grades = false;
    private $has_metacourse = false;

    private $data_format = "d/m/Y H:i"; // o formato da data mostrada na listagem

    private $grades_format_status = 'all_grades_formatted'; // O estado da notas quanto à sua formatação (serve como classe CSS).

    private $group = null; // Selected group, none by default.

    private $rows_ok = array(); // Rows for table with students that are enrolled on moodle and on remote system.
    private $rows_not_in_moodle = array(); // Rows for table with students not enrolled on moodle.
    private $rows_not_in_remote = array(); // Rows for table with students not enrolled on remote system.

    private $force_course_grade = false;

    public function __construct($courseid, $gpr, $context, $force_course_grades, $group=null, $page=null) {
        global $CFG, $USER, $DB;

        parent::__construct($courseid, $gpr, $context, $page);
        
        $this->force_course_grades = $force_course_grades;

        // Weird, but works because result.php does not construct the report ;) .
        // refactor?
        if (isset($USER->gradereportwsexportsendresults)) {
            unset($USER->gradereportwsexportsendresults);
        }

        $this->group = $group;

        // TODO: paging bar url. really needed?
        $this->pbarurl = 'index.php?id='.$this->courseid;

        $this->setup_groups();
    }

    // TODO ?
    public function process_data($data) {
    }

    // TODO ?
    public function process_action($target, $action){
    }

    public function show() {
        global $CFG;

        $this->get_course_grade_item();

        $this->can_user_send_for_course();
        $this->are_grades_valid($this->moodle_grades, $this->course_grade_item);

        $this->get_moodle_grades();
        $this->get_remote_grades();

        $this->setup_and_fill_tables();

        if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
            echo html_writer::link(new moodle_url('/grade/report/wsexport/item.php', array('id' => $this->courseid)),
                                   get_string('editgradeitems', 'gradereport_wsexport'));
        }

        echo html_writer::tag('div', $this->group_selector);

        $this->print_remote_messages();
        $this->print_local_messages();

        echo html_writer::start_tag('form', array('method' => 'post', 'action' => "confirm.php?id={$this->courseid}"));

        $this->print_tables();

        echo html_writer::start_tag('div', array('class' => 'report_footer'));

        $this->select_overwrite_grades();

        $this->print_remote_messages();
        $this->print_local_messages();

        $attributes = array('type' => "submit",  'value' => get_string('submit_button', 'gradereport_wsexport'));
        if ($this->cannot_submit) {
            $attributes['disabled'] = 'disabled';
        }
        echo html_writer::empty_tag('input', $attributes),
             html_writer::end_tag('div'),
             html_writer::end_tag('form');
    }

    public function send_grades($grades, $othergrades = array()) {
        global $USER, $CFG, $DB;

        $courseshortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));

        $url = $CFG->grade_report_wsexport_get_grades_url;
        $functionname = $CFG->grade_report_wsexport_send_grades_function_name;

        $params = array($CFG->grade_report_wsexport_send_grades_username_param => $USER->username,
                        $CFG->grade_report_wsexport_send_grades_course_param => $courseshortname,
                        $CFG->grade_report_wsexport_send_grades_grades_param => $grades);

        if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
            for  ($i = 1; $i < 4; $i++) {
                $itemparam = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_param';
                $gradeitem = $CFG->{$itemparam};
                if (isset($othergrades[$gradeitem])) {
                    $params[$gradeitem] = $othergrades[$gradeitem];
                }
            }
        }

        // TODO: create and trigger event
        //add_to_log($this->courseid, 'grade', 'transposicao', 'send.php', $log_info);

        try {
            $this->sendresults = $this->call_ws($url, $functionname, $params);
            $USER->gradereportwsexportsendresults = $this->sendresults;
            $this->send_email_with_errors();
        } catch (Exception $e) {
            // TODO: Handle send exceptions.
        }
    }

    protected function setup_groups() {
        parent::setup_groups();

        if (!empty($this->group) && !is_null($this->group)) {
            $this->cannot_submit = true;
        }
    }

    private function print_tables() {
        ob_start();
        $this->print_table_not_in_moodle();
        $this->print_table_not_in_remote();
        $this->print_table_ok();
        ob_end_flush();
    }

    private function print_table_not_in_moodle() {

        if(!empty($this->rows_not_in_moodle) && ($this->statistics['not_in_moodle']  > 0)){

            echo html_writer::tag('h3', get_string('students_not_in_moodle', 'gradereport_wsexport',
                                                   $this->statistics['not_in_moodle'])),
                 html_writer::tag('h4', get_string('wont_be_sent', 'gradereport_wsexport'));

            foreach($this->rows_not_in_moodle as $row){
                $this->table_not_in_moodle->add_data($row);
            }
            $this->table_not_in_moodle->print_html();
        }
    }

    private function print_table_not_in_remote() {

        if (!empty($this->rows_not_in_remote) && $this->statistics['not_in_remote'] > 0) {

            echo html_writer::tag('h3', get_string('students_not_in_remote', 'gradereport_wsexport',
                                                   $this->statistics['not_in_remote'])),
                 html_writer::tag('h4', get_string('wont_be_sent', 'gradereport_wsexport'));

            foreach($this->rows_not_in_remote as $row){
                $this->table_not_in_remote->add_data($row); // Moodle 2: já imprime.
            }
            $this->table_not_in_remote->print_html();
        }
    }

    private function print_table_ok() {

        echo html_writer::tag('h3', get_string('students_ok', 'gradereport_wsexport', $this->statistics['ok']));
        foreach($this->rows_ok as $row){
            $this->table_ok->add_data($row);
        }
        $this->table_ok->print_html();
    }

    private function get_course_grade_item() {

        if ($id_course_grade = $this->get_parent_meta_id()) {
            $this->has_metacourse = true;
            $this->using_metacourse_grades = true;

            if ($this->force_course_grades) {
                $this->using_metacourse_grades = false;
                $id_course_grade = $this->courseid;
            }
        } else {
            $id_course_grade = $this->courseid;
        }

        $this->course_grade_item = grade_item::fetch_course_item($id_course_grade);
    }

    private function get_moodle_grades() {
        global $DB, $CFG;

        $coursecontext = context_course::instance($this->courseid);

        // TODO: use CFG->gradebookroles.
        $studentid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $this->moodle_students = get_role_users($studentid, $coursecontext, false,
                                                'u.id, '.get_all_user_name_fields(true). ', u.username',
                                                'u.firstname, u.lastname', null, $this->group);

        $grades = $DB->get_records('grade_grades', array('itemid' => $this->course_grade_item->id),
                                   'userid', 'userid, finalgrade');

        $this->moodle_grades = array();
        $this->grades_to_send = array();

        if ($this->course_grade_item->gradetype == GRADE_TYPE_SCALE) {
            // TODO: how to handle scales?
            $pg_scale = new grade_scale(array('id' => $CFG->grade_report_wsexport_escala));
            $scale_items = $pg_scale->load_items();
            foreach ($this->moodle_students as $st)  {
                if (isset($grades[$st->id])) {
                    $fg = (int)$grades[$st->id]->finalgrade;
                    if(isset($scale_items[$fg-1])) {
                        $this->moodle_grades[$st->id] = $scale_items[$fg-1];
                        $this->grades_to_send[$st->id] = substr($scale_items[$fg-1], 0, 1);
                    } else {
                        $this->moodle_grades[$st->id] = null;
                        $this->grades_to_send[$st->id] = 'E';
                    }
                } else {
                    $this->moodle_grades[$st->id] = null;
                    $this->grades_to_send[$st->id] = 'E';
                }
            }
        } else {
            foreach ($this->moodle_students as $st)  {
                if (isset($grades[$st->id]) && $grades[$st->id]->finalgrade != null) {

                    $this->moodle_grades[$st->id] = grade_format_gradevalue($grades[$st->id]->finalgrade,
                                                                            $this->course_grade_item, true,
                                                                            $this->course_grade_item->get_displaytype(), null);

                    $this->grades_to_send[$st->id] = grade_format_gradevalue($grades[$st->id]->finalgrade,
                                                                             $this->course_grade_item, false,
                                                                             //TODO: create config for teacher to change this.
                                                                             $this->course_grade_item->get_displaytype(), null);

                } else {

                    $this->moodle_grades[$st->id] = grade_format_gradevalue(null, $this->course_grade_item, true,
                                                                            $this->course_grade_item->get_displaytype(), null);

                    $this->grades_to_send[$st->id] = grade_format_gradevalue(0, $this->course_grade_item, false,
                                                                             //TODO: create config for teacher to change this.
                                                                             $this->course_grade_item->get_displaytype(), null);
                }
            }
        }

        if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {

            for ($i= 1; $i < 4; $i++) {

                $itemparamcfg = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_param';
                $itemparam = $CFG->{$itemparamcfg};

                $itemidcfg = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_course'.$this->courseid;
                $itemid = $CFG->{$itemidcfg};

                $item = grade_item::fetch(array('id' => $itemid));
                $grades = $DB->get_records('grade_grades', array('itemid' => $itemid), 'userid', 'userid, finalgrade');

                foreach ($this->moodle_students as $st)  {
                    if (isset($grades[$st->id]) && $grades[$st->id]->finalgrade != null) {
                        $this->other_grades_to_send[$itemparam][$st->id] = grade_format_gradevalue($grades[$st->id]->finalgrade,
                                                                                                   $item, true,
                                                                                                   $item->get_displaytype(), null);

                        $this->other_moodle_grades[$itemparam][$st->id] = $this->other_grades_to_send[$itemparam][$st->id];
                    } else {
                        // TODO: fix, show '-' instead of zero.
                        $this->other_grades_to_send[$itemparam][$st->id] = grade_format_gradevalue(0,
                                                                                                   $item, true,
                                                                                                   $item->get_displaytype(), null);

                        $this->other_moodle_grades[$itemparam][$st->id] = $this->other_grades_to_send[$itemparam][$st->id];
                    }
                }
            }
        }
    }

    // It will fill $this->not_in_remote_students.
    private function fill_table_ok() {
        global $CFG;

        $this->rows_ok = array();
        if (!is_array($this->moodle_students)) {
            return false; // Nenhum estudante no moodle.
        }
        foreach ($this->moodle_students as $student) {

            $student->moodle_grade = $this->moodle_grades[$student->id];

            if (isset($this->remote_grades[$student->username])) {

                $current_student = $this->remote_grades[$student->username];
                unset($this->remote_grades[$student->username]);

                $this->statistics['ok']++;

                $current_remotegrade     = $current_student[$CFG->grade_report_wsexport_get_grades_return_grade];
                $current_timeupdated     = $current_student[$CFG->grade_report_wsexport_get_grades_return_timeupdated];
                $current_updatedbymoodle = $current_student[$CFG->grade_report_wsexport_get_grades_return_updatedbymoodle];
                // TODO
                //$current_attendance      = $current_student[$CFG->grade_report_wsexport_get_grades_return_attendance];

                $grade_on_remote = $this->get_grade($current_student);

                $timeupdated = '';
                $alert = '';
                $grade_on_remote_hidden = '';

                if (empty($current_remotegrade) && $current_remotegrade != '0') {
                    $timeupdated = get_string('never_sent', 'gradereport_wsexport');
                } else {
                    if (empty($current_timeupdated)) {
                        $timeupdated = get_string('never_sent', 'gradereport_wsexport');
                    } else {
                        $timeupdated = date($this->data_format, $current_timeupdated);
                    }

                    if (!$current_updatedbymoodle) {

                        $this->statistics['updated_on_remote']++;

                        $alert .= html_writer::tag('<p>', get_string('grade_updated_on_remote', 'gradereport_wsexport'));

                        $grade_on_remote_hidden = '<input type="hidden" name="remotegrades['.$student->username.']" value="1"/>';
                    }
                }

                if (is_null($student->moodle_grade) || $student->moodle_grade == '-') {
                    $alert .= html_writer::tag('p', get_string('warning_null_grade', 'gradereport_wsexport',
                                                               $this->grades_to_send[$student->id]),
                                               array('class' => 'null_grade'));
                }

                $grade_hidden = html_writer::empty_tag('input', array('type'  => 'hidden',
                                                                      'name'  => 'grades['.$student->username.']',
                                                                      'value' => $this->grades_to_send[$student->id]));

                if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
                    for ($i = 1; $i < 4; $i++) {
                        $itemparamcfg = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_param';
                        $itemparam = $CFG->{$itemparamcfg};
                        $grade_hidden .= html_writer::empty_tag('input',
                                                          array('type'  => 'hidden',
                                                                'name'  => 'othergrades_'.$itemparam.'['.$student->username.']',
                                                                'value' => $this->other_grades_to_send[$itemparam][$student->id]));
                    }
                }

                $grade_on_remote_formatted = str_replace('.', ',', (string)$grade_on_remote);

                if ($this->grades_differ($current_student, $this->grades_to_send[$student->id], $grade_on_remote))  {

                    $grade_on_moodle = '<span class="diff_grade">'.
                                       $student->moodle_grade.$grade_hidden.$grade_on_remote_hidden.
                                       '</span>';

                    $grade_on_remote_formatted = '<span class="diff_grade">'.$grade_on_remote_formatted.'</span>';

                    $alert .= '<p class="diff_grade">'.get_string('warning_diff_grade', 'gradereport_wsexport').'</p>';

                } else {
                    $grade_on_moodle = $student->moodle_grade . $grade_hidden . $grade_on_remote_hidden;
                }

                $row = array(fullname($student), $grade_on_moodle);
                if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
                    for ($i = 1; $i < 4; $i++) {
                        $itemparamcfg = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_param';
                        $itemparam = $CFG->{$itemparamcfg};
                        // TODO: handle different grades from remote system.
                        $row[] = $this->other_grades_to_send[$itemparam][$student->id];
                    }
                }
                $row[] = $grade_on_remote_formatted;
                $row[] = $timeupdated;
                $row[] = $alert;
                $this->rows_ok[] = $row;

            } else {
                $this->not_in_remote_students[] = $student;
            }
        }
        $this->statistics['not_in_moodle'] = sizeof($this->remote_grades); // Os que estão no moodle foram removidos.
    }

    private function fill_table_not_in_moodle() {
        global $CFG;

        $this->rows_not_in_moodle =  array();

        // Caso a seleção seja feita por algum grupo,
        // são desconsiderados os alunos que estão no CAGR e não estão no Moodle.
        if (empty($this->group)) {

            // Nesse ponto $this->remote_grades contém apenas os estudantes que não estão no moodle,
            // isso ocorre por que essa função é chamada após fill_table_ok().
            foreach  ($this->remote_grades as $username => $student) {

                $grade_on_remote = $this->get_grade($student);

                if (empty($student[$CFG->grade_report_wsexport_get_grades_return_grade]) ||
                    empty($student[$CFG->grade_report_wsexport_get_grades_return_timeupdated])) {
                    $timeupdated = get_string('never_sent', 'gradereport_wsexport');
                } else {
                    $timeupdated = date($this->data_format,
                                      strtotime($student[$CFG->grade_report_wsexport_get_grades_return_timeupdated]));
                }

                $this->rows_not_in_moodle[] = array($student[$CFG->grade_report_wsexport_get_grades_return_fullname],
                                                    $grade_on_remote, $timeupdated);
            }
        }
    }

    private function fill_table_not_in_remote() {
        $this->rows_not_in_remote = array();
        foreach ($this->not_in_remote_students as $student) {
            $this->rows_not_in_remote[] = array(fullname($student), $this->moodle_grades[$student->id]);
        }
        $this->statistics['not_in_remote'] = sizeof($this->not_in_remote_students);
    }

    private function setup_and_fill_tables() {
        // The order here does not matter.
        $this->setup_table_ok();
        $this->setup_table_not_in_remote();
        $this->setup_table_not_in_moodle();

        // The order here matters.
        $this->fill_table_ok();
        $this->fill_table_not_in_moodle();
        $this->fill_table_not_in_remote();
    }

    private function setup_table_not_in_remote() {
        global $CFG;

        $columns = array('name', 'moodle_grade_course_total');
        $headers = array();
        foreach ($columns as $c) {
            $headers[] = get_string($c, 'gradereport_wsexport');
        }

        $this->table_not_in_remote = new flexible_table('gradereport-wsexport-not_in_remote');
        $this->table_not_in_remote->define_columns($columns);
        $this->table_not_in_remote->define_headers($headers);
        $this->table_not_in_remote->define_baseurl($this->baseurl);

        $this->table_not_in_remote->set_attribute('cellspacing', '0');
        $this->table_not_in_remote->set_attribute('id', 'gradereport-wsexport-not_in_remote');
        $this->table_not_in_remote->set_attribute('class', 'boxaligncenter generaltable');

        foreach ($columns as $c) {
            $this->table_not_in_remote->column_class($c, $c);
        }

        $this->table_not_in_remote->setup();
    }

    private function setup_table_not_in_moodle() {

        $columns = array('name', 'remote_grade', 'timeupdated');
        $headers = array();
        foreach ($columns as $c) {
            $headers[] = get_string($c, 'gradereport_wsexport');
        }

        $this->table_not_in_moodle = new flexible_table('gradereport-wsexport-not_in_moodle');
        $this->table_not_in_moodle->define_columns($columns);
        $this->table_not_in_moodle->define_headers($headers);
        $this->table_not_in_moodle->define_baseurl($this->baseurl);

        $this->table_not_in_moodle->set_attribute('cellspacing', '0');
        $this->table_not_in_moodle->set_attribute('id', 'gradereport-wsexport-not_in_moodle');
        $this->table_not_in_moodle->set_attribute('class', 'boxaligncenter generaltable');

        foreach ($columns as $c) {
            $this->table_not_in_moodle->column_class($c, $c);
        }

        $this->table_not_in_moodle->setup();
    }

    private function setup_table_ok() {
        global $CFG;

        $columns = array('name', 'moodle_grade_course_total');
        $headers = array();
        foreach ($columns as $c) {
            $headers[] = get_string($c, 'gradereport_wsexport');
        }
        if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
            $headers[] = $CFG->grade_report_wsexport_grade_items_gradeitem1_name;
            $headers[] = $CFG->grade_report_wsexport_grade_items_gradeitem2_name;
            $headers[] = $CFG->grade_report_wsexport_grade_items_gradeitem3_name;
                                     
            $columns[] = $CFG->grade_report_wsexport_grade_items_gradeitem1_param;
            $columns[] = $CFG->grade_report_wsexport_grade_items_gradeitem2_param;
            $columns[] = $CFG->grade_report_wsexport_grade_items_gradeitem3_param;
        }
        $othercolumns = array('remote_grade', 'timeupdated', 'alerts');
        foreach ($othercolumns as $c) {
            $headers[] = get_string($c, 'gradereport_wsexport');
        }

        $this->table_ok = new flexible_table('gradereport-wsexport-ok');
        $this->table_ok->define_columns(array_merge($columns, $othercolumns));
        $this->table_ok->define_headers($headers);
        $this->table_ok->define_baseurl($this->baseurl);

        $this->table_ok->set_attribute('cellspacing', '0');
        $this->table_ok->set_attribute('id', 'gradereport-wsexport-ok');
        $this->table_ok->set_attribute('class', 'boxaligncenter generaltable');

        foreach ($columns as $c) {
            $this->table_ok->column_class($c, $c);
        }

        $this->table_ok->setup();
    }

    private function select_overwrite_grades() {

        if ($this->statistics['updated_on_remote'] > 0) {

            $dis = $this->cannot_submit == true ? 'disable="disable"' : '';
            echo '<p class="overwrite_all">',
                 '<input type="checkbox" id="overwrite_all" name="overwrite_all" value="1"', $dis, ' />',
                 '<label for="overwrite_all">',get_string('overwrite_all_grades', 'gradereport_wsexport'), '</label>',
                 '</p>';
        }

    }

    private function msg_invalid_grades() {
        $url = new moodle_url('/grade/edit/tree/category.php',
                              array('courseid' => $this->course_grade_item->courseid,
                                    'id' => $this->course_grade_item->iteminstance));
        echo html_writer::tag('p', get_string($this->grades_format_status, 'gradereport_wsexport', $url), array('class' => 'error prevent'));
    }

    private function msg_grade_updated_on_remote() {
        if ($this->statistics['updated_on_remote'] > 0) {
            echo html_writer::tag('p', get_string('grades_updated_on_remote', 'gradereport_wsexport'), array('class' => 'warning'));
        }
    }

    private function msg_groups() {
        if (!empty($this->group) && !is_null($this->group)) {
            echo html_writer::tag('p', get_string('grades_selected_by_group', 'gradereport_wsexport'), array('class' => 'warning prevent'));
        }
    }

    private function msg_using_metacourse_grades() {
        if ($this->using_metacourse_grades) {
            $url = new moodle_url('/grade/report/wsexport/index.php', array('id' => $this->courseid, 'force_course_grades' => 1));
            echo '<p class="warning">',
                 get_string('using_metacourse_grades', 'gradereport_wsexport'),
                 ' <a href="'.$url.'">', get_string('dont_use_metacourse_grades', 'gradereport_wsexport'), '</a></p>';
        } else if ($this->has_metacourse) {
            $url = new moodle_url('/grade/report/wsexport/index.php', array('id' => $this->courseid, 'force_course_grades' => 0));
            echo '<p class="warning">',
                 get_string('using_course_grades', 'gradereport_wsexport'),
                 ' <a href="'.$url.'">', get_string('use_metacourse_grades', 'gradereport_wsexport'), '</a></p>';
        }
    }

    // Caso curso esteja agrupado em um metacurso, retorna o id deste.
    private function get_parent_meta_id(){
        global $DB;

        $sql = "SELECT cm.id
                 FROM {course} c
                 JOIN {enrol} e
                   ON (e.customint1 = c.id)
                 JOIN {course} cm
                   ON (cm.id = e.courseid)
                WHERE e.enrol = 'meta'
                  AND c.id = ?";
        if ($course = $DB->get_record_sql($sql, array($this->courseid))) {
            return $course->id;
        } else {
            return false;
        }
    }

    private function get_remote_grades() {
        global $DB, $CFG, $USER;

        $courseshortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));
        $url = $CFG->grade_report_wsexport_get_grades_url;
        $functionname = $CFG->grade_report_wsexport_get_grades_function_name;
        $params = array($CFG->grade_report_wsexport_get_grades_username_param => $USER->username,
                        $CFG->grade_report_wsexport_get_grades_course_param => $courseshortname);

        try {
            $this->remote_grades = $this->call_ws($url, $functionname, $params);
        } catch (Exception $e) {
            // TODO: handle remote grades fetch failure
            $this->remote_grades = array('teste1' => array('grade' => 8, 
                                                    'fullname' => 'testando', 'timeupdated' => 1440011313,
                                                    'updatedbymoodle' => true));
        }
    }

    private function are_grades_valid($grades, $course_grade_item) {
        global $DB, $CFG, $USER;

        if ($course_grade_item->display == 0) {
            // O displaytype do item não foi definido, então temos que pegar o displaytype do curso.
            if(!$display = $DB->get_field('grade_settings', 'value', array('courseid' => $course_grade_item->courseid, 'name' => 'displaytype'))) {
                $display = $CFG->grade_displaytype;
            }
        } else {
            $display = $course_grade_item->display;
        }

        $courseshortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));
        $url = $CFG->grade_report_wsexport_are_grades_valid_url;
        $functionname = $CFG->grade_report_wsexport_are_grades_valid_function_name;
        $params = array($CFG->grade_report_wsexport_are_grades_valid_username_param => $USER->username,
                        $CFG->grade_report_wsexport_are_grades_valid_course_param => $courseshortname,
                        $CFG->grade_report_wsexport_are_grades_valid_grades_param => $this->moodle_grades);

        if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
            $gradeitem1 = $CFG->grade_report_wsexport_grade_items_gradeitem1_param;
            $gradeitem2 = $CFG->grade_report_wsexport_grade_items_gradeitem2_param;
            $gradeitem3 = $CFG->grade_report_wsexport_grade_items_gradeitem3_param;
            if (isset($othergrades[$gradeitem1])) {
                $params[$gradeitem1] = $this->other_moodle_grades[$gradeitem1];
            }
            if (isset($othergrades[$gradeitem2])) {
                $params[$gradeitem2] = $this->other_moodle_grades[$gradeitem2];
            }
            if (isset($othergrades[$gradeitem3])) {
                $params[$gradeitem3] = $this->other_moodle_grades[$gradeitem3];
            }
        }

        try {
            // TODO: create config for how to test this.
            $this->gradesvalidationresults = $this->call_ws($url, $functionname, $params);
            foreach ($this->gradesvalidationresults as $r) {
                if (!$r) {
                    $this->grades_format_status = 'unformatted_remotegrades';
                    $this->cannot_submit = true;
                }
            }
        } catch (Exception $e) {
            // TODO: handle validation errors
            $this->cannot_submit = true;
        }
    }

    public function is_meta_course() {
        global $DB;
        $sql = "SELECT DISTINCT cm.id
                 FROM {course} cm
                 JOIN {enrol} e
                   ON (e.courseid = cm.id AND
                       e.enrol = 'meta')
                WHERE cm.id = ?";
        return $DB->record_exists_sql($sql, array($this->courseid));
    }

    public function check_grade_items() {
        global $CFG;
        if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
            for ($i = 1; $i < 4; $i++) {
                $itemparam = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_course'.$this->courseid;
                if (!isset($CFG->{$itemparam})) {
                    redirect(new moodle_url('/grade/report/wsexport/item.php', array('id' => $this->courseid)),
                             get_string('must_set_grade_items', 'gradereport_wsexport'));
                }
            }
        }
    }

    public function save_course_grade_items($data) {
        for ($i = 1; $i < 4; $i++) {
            $itemparam = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_course'.$this->courseid;
            if (isset($data->{$itemparam}) && grade_item::fetch(array('courseid' => $this->courseid,
                                                                      'id' => $data->{$itemparam}))) {
                set_config($itemparam, $data->{$itemparam});
            }
        }
    }

    public function lista_turmas_afiliadas() {
        global $OUTPUT, $CFG, $DB;

        echo $OUTPUT->box_start('generalbox');
        echo get_string('is_metacourse_error','gradereport_wsexport');
        echo $OUTPUT->box_end();

        $sql = "SELECT DISTINCT e.customint1 as id, filha.fullname
                           FROM {course} c
                           JOIN {enrol} e
                             ON (e.courseid = c.id)
                           JOIN {course} filha
                             ON (e.customint1 = filha.id)
                          WHERE e.enrol = 'meta'
                            AND c.id = ?";
        $turmas = $DB->get_records_sql($sql, array($this->courseid));

        $turmas_professor = array();
        $turmas_outros = array();

        foreach ($turmas as $t) {
            $context = context_course::instance($t->id);
            if (has_capability('gradereport/wsexport:view', $context)) {
                $turmas_professor[] = "<a href='{$CFG->wwwroot}/grade/report/wsexport/index.php?id={$t->id}' target='_blank'> {$t->fullname} </a>";
            }else{
                $turmas_outros[] = $t->fullname;
            }
        }

        if (!empty($turmas_professor)) {
            echo "<h3>", get_string('turmas_prof', 'gradereport_wsexport'), '</h3><ul>';
            foreach($turmas_professor as $t){
                echo "<li>{$t}</li>";
            }
            echo '</ul>';
        }

        if (!empty($turmas_outros)) {
            echo "<h3>", get_string('turmas_outros', 'gradereport_wsexport'), '</h3><ul>';
            foreach($turmas_outros as $t){
                echo "<li>{$t}</li>";
            }
            echo '</ul>';
        }
    }

    // TODO: refactor to make generic
    private function grades_differ($current_student, $moodle_grade, $ca_grade) {
        $has_fi = $this->has_fi($current_student);
        return ($ca_grade != '(I)') &&
               ($ca_grade != null) &&
               (($moodle_grade != null) && !$has_fi) &&
               (($moodle_grade != $ca_grade) && !$has_fi);
    }

    private function can_user_send_for_course() {
        global $DB, $USER, $CFG;

        $courseshortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));
        $url = $CFG->grade_report_wsexport_can_user_send_for_course_url;
        $functionname = $CFG->grade_report_wsexport_can_user_send_for_course_function_name;
        $params = array($CFG->grade_report_wsexport_can_user_send_for_course_username_param => $USER->username,
                        $CFG->grade_report_wsexport_can_user_send_for_course_course_param => $courseshortname);

        try {
            $result = $this->call_ws($url, $functionname, $params);

            $this->cannot_submit = !$result->is_allowed; // TODO: change to setting
            $this->remote_messages = $result->messages; // TODO: change to setting
        } catch (Exception $e) {
            // TODO: handle exception
            //echo 'Exception:', $e->getMessage();
            return true;
        }
    }

    private function call_ws($serverurl, $functionname, $params = array()) {

        $serverurl = $serverurl . '?wsdl';

        $client = new SoapClient($serverurl);
        $resp = $client->__soapCall($functionname, array($params));

        return $resp;
    }

    private function print_local_messages() {
        $this->msg_invalid_grades();
        $this->msg_grade_updated_on_remote();
        $this->msg_using_metacourse_grades();
        $this->msg_groups();
    }

    private function print_remote_messages() {
        if (!empty($this->remote_messages)) {
            foreach ($this->remote_messages as $msg) {
                echo '<p>', $msg, '</p>';
            }
        }
    }

    private function send_email_with_errors() {
        global $DB;

        if (!empty($this->sendresults)) {

            $course_name = $DB->get_field('course', 'fullname', array('id' => $this->courseid));
            $admin = get_admin();
            $subject = 'Falha na transposicao de notas (CAGR) da disciplina '.$course_name;
            $body = '';

            $names = $DB->get_records_select('user', 'username IN ('.implode(',', array_keys($this->sendresults)) . ')',
                            null, 'firstname,lastname', 'username,firstname');

            foreach ($this->sendresults as $matricula => $error) {
                $body .= "Matricula: {$matricula}; {$names[$matricula]->firstname} ; Erro: {$error}\n";
            }
            email_to_user($admin, $admin, $subject, $body);
        }
    }

    // TODO: refactor
    private function get_grade($st) {
        global $CFG;

        $i = false;

        /*
        if (!empty($st[$CFG->grade_report_wsexport_get_grade_return_mencao]) && 
            $st[$CFG->grade_report_wsexport_get_grade_return_mencao] != ' ') {

            $grade = "(I)"; // Se o aluno tem mencao I, entao a nota eh zero.
            $i = true;

        } else {
            */
            $grade = $st[$CFG->grade_report_wsexport_get_grades_return_grade];
       // }
        return $grade;
    }

    // TODO: refactor
    private function has_fi($st) {
        global $CFG;
        return  false;
                // TODO.
                //(isset($st[$CFG->grade_report_wsexport_get_grade_return_attendance]) &&
                //($st[$CFG->grade_report_wsexport_get_grade_return_attendance] == 'FI' ||
                // $st[$CFG->grade_report_wsexport_get_grade_return_attendance] == 'I'));
    }
}
