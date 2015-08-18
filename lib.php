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

    private $moodle_students = array(); // Um array com os alunos vindos do moodle - inicializado em fill_table().
    private $moodle_grades = array(); // Um array com as notas dos alunos do moodle - inicializado em get_moodle_grades().
    private $grades_to_send = array(); // Um array com as notas dos alunos do moodle a serem enviadas para o controle acadêmico - inicializado em get_moodle_grades().
    private $not_in_remote_students = array(); // Um array com os alunos do moodle que nao estao no remote - inicializado em fill_table_ok().

    // Um array com as contagens de alunos por problema.
    private $statistics = array('not_in_remote' => 0, 'not_in_moodle' => 0, 'ok' => 0,
                                'unformatted_grades' => 0, 'updated_on_remote' => 0);

    private $sendresults = array(); // Um array (matricula => msg) com as msgs de erro de envio de notas.

    private $show_fi = null; // from CFG, if must show the 'FI' column
    private $show_mencaoI = null;

    private $cannot_submit = false; // If there is something preventing grades sending, set it to true.

    private $using_metacourse_grades = false; // If we retrieving grades from metacourse.
    private $has_metacourse = false; // If courseid belongs to a metacourse.

    private $data_format = "d/m/Y H:i"; // o formato da data mostrada na listagem

    private $grades_format_status = 'all_grades_formatted'; // O estado da notas quanto à sua formatação (serve como classe CSS).

    private $group = null; // Selected group, none by default.

    private $rows_ok = array(); // Rows for table with students that are enrolled on moodle and on remote system.
    private $rows_not_in_moodle = array(); // Rows for table with students not enrolled on moodle.
    private $rows_not_in_remote = array(); // Rows for table with students not enrolled on remote system.

    public function __construct($courseid, $gpr, $context, $force_course_grades, $group=null, $page=null) {
        global $CFG, $USER, $DB;

        parent::__construct($courseid, $gpr, $context, $page);

        // Weird, but works because result.php does not construct the report ;) .
        if (isset($USER->gradereportwsexportsendresults)) {
            unset($USER->gradereportwsexportsendresults);
        }

        $this->show_fi = (isset($CFG->grade_report_wsexport_show_fi) &&
                          $CFG->grade_report_wsexport_show_fi == true);

        $this->group = $group;

        $coursecontext = context_course::instance($this->courseid);
        $this->moodle_students = get_role_users($DB->get_field('role', 'id', array('shortname' => 'student')),
                                                $coursecontext, false, '', 'u.firstname, u.lastname', null, $this->group);

        $this->get_course_grade_item($force_course_grades);

        $this->can_user_send_for_course(); // TODO: move out from constructor.

        $this->show_mention = isset($CFG->grade_report_wsexport_show_mention);

        $this->get_moodle_grades();

        try {
            // TODO: move out from constructor.
            $this->remote_grades = $this->get_grades_from_webservice();
        } catch (Exception $e) {
            // TODO: handle remote grades fetch failure
            $this->remote_grades = array('admin' => array('mencao' => '', // TODO: rename, use config.
                                                          'nota' =>8, // TODO: rename, use config.
                                                          'frequencia' => 'FS', // TODO: rename, use config.
                                                          'lastupdatebymoodle' => false)); // TODO: use config.
        }

        if (!$this->are_grades_valid($this->moodle_grades, $this->course_grade_item)) {
            $this->cannot_submit = true;
        }

        // TODO: paging bar url. real needed?
        $this->pbarurl = 'index.php?id='.$this->courseid;

        $this->setup_groups();
    }

    public function process_data($data){//TODO?
    }

    public function process_action($target, $action){//TODO?
    }

    public function show() {

        $this->setup_and_fill_tables();

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

    public function send_grades($grades, $mentions = array(), $insufficientattendances = array(), $othergrades = array()) {
        global $USER, $CFG, $DB;

        $courseshortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));

        $url = $CFG->grade_report_wsexport_get_grades_url;
        $functionname = $CFG->grade_report_wsexport_send_grades_function_name;
        // TODO: check if mentions and attendances should be sent.

        $params = array($CFG->grade_report_wsexport_send_grades_username_param => $USER->username,
                        $CFG->grade_report_wsexport_send_grades_course_param => $courseshortname,
                        $CFG->grade_report_wsexport_send_grades_grades_param => $grades);

        // TODO: improve this.
        if ($CFG->grade_report_wsexport_grade_items_multiplegrades) {
            $gradeitem1 = $CFG->grade_report_wsexport_grade_items_gradeitem1_param;
            $gradeitem2 = $CFG->grade_report_wsexport_grade_items_gradeitem2_param;
            $gradeitem3 = $CFG->grade_report_wsexport_grade_items_gradeitem3_param;
            if (isset($othergrades[$gradeitem1])) {
                $params[$gradeitem1] = $othergrades[$gradeitem1];
            }
            if (isset($othergrades[$gradeitem2])) {
                $params[$gradeitem2] = $othergrades[$gradeitem2];
            }
            if (isset($othergrades[$gradeitem3])) {
                $params[$gradeitem3] = $othergrades[$gradeitem3];
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

    private function fill_tables() {
        //funções "fill_" devem ser chamadas nesta ordem
        $this->rows_ok = $this->fill_table_ok();
        $this->rows_not_in_moodle = $this->fill_table_not_in_moodle();
        $this->rows_not_in_remote = $this->fill_table_not_in_remote();
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
            echo html_writer::tag('h2',
                                  get_string('students_not_in_moodle', 'gradereport_wsexport', $this->statistics['not_in_moodle']).
                                  get_string('wont_be_sent', 'gradereport_wsexport'), array('class' => 'table_title'));
            foreach($this->rows_not_in_moodle as $row){
                $this->table_not_in_moodle->add_data($row);
            }
            $this->table_not_in_moodle->print_html();//apenas finaliza tabela
        }
    }

    private function print_table_not_in_remote() {

        if (!empty($this->rows_not_in_remote) && $this->statistics['not_in_remote'] > 0) {
            echo html_writer::tag('h2',
                                  get_string('students_not_in_remote', 'gradereport_wsexport', $this->statistics['not_in_remote']).
                                  get_string('wont_be_sent', 'gradereport_wsexport'), array('class' => 'table_title'));

            foreach($this->rows_not_in_remote as $row){
                $this->table_not_in_remote->add_data(array_merge($row, array('', '', '')));//Moodle 2: já imprime
            }
            $this->table_not_in_remote->print_html();//apenas finaliza tabela
        }
    }

    private function print_table_ok() {

        echo html_writer::tag('h2',
                              get_string('students_ok', 'gradereport_wsexport', $this->statistics['ok']),
                              array('class' => 'table_title'));
        foreach($this->rows_ok as $row){
            $this->table_ok->add_data($row);
        }
        $this->table_ok->print_html();
    }

    private function get_course_grade_item($force_course_grades = false) {

        if ($id_course_grade = $this->get_parent_meta_id()) {
            $this->has_metacourse = true;
            $this->using_metacourse_grades = true;

            if ($force_course_grades) {
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

        $grades = $DB->get_records('grade_grades', array('itemid' => $this->course_grade_item->id), 'userid', 'userid, finalgrade');
        if(!is_array($grades)) {
            $grades = array();
        }

        $this->moodle_grades = array();
        $this->grades_to_send = array();

        if ($this->course_grade_item->gradetype == GRADE_TYPE_SCALE) {
            // TODO: how to handle scales?
            $pg_scale = new grade_scale(array('id' => $CFG->grade_report_wsexport_escala_pg));
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
                                                                        $this->course_grade_item->get_displaytype(), //TODO: create config for this.
                                                                        null);
                } else {
                    $this->moodle_grades[$st->id] = grade_format_gradevalue(null,
                                                                        $this->course_grade_item, true,
                                                                        $this->course_grade_item->get_displaytype(), null);
                    $this->grades_to_send[$st->id] = grade_format_gradevalue(0,
                                                                        $this->course_grade_item, false,
                                                                        $this->course_grade_item->get_displaytype(), //TODO: create config for this.
                                                                        null);
                }
            }
        }
    }

    private function fill_table_ok() {

        $rows = array();
        if (!is_array($this->moodle_students)) {
            return false; // Nenhum estudante no moodle.
        }
        foreach ($this->moodle_students as $student) {

            $student->moodle_grade = $this->moodle_grades[$student->id];

            if (isset($this->remote_grades[$student->username])) {

                $current_student = $this->remote_grades[$student->username];

                $this->statistics['ok']++;
                unset($this->remote_grades[$student->username]);

                list($has_mencao_i, $grade_in_remote) = $this->get_grade_and_mencao_i($current_student);

                $has_fi = (isset($current_student['frequencia']) && ($current_student['frequencia'] == 'FI' || $current_student['frequencia'] == 'I'));
                $sent_date = '';
                $alert = '';
                $grade_on_remote_hidden = '';

                if (empty($current_student['nota']) && $current_student['nota'] != '0') {
                    $sent_date = get_string('never_sent', 'gradereport_wsexport');
                } else {
                    if (empty($current_student['dataAtualizacao'])) {
                        $sent_date = get_string('never_sent', 'gradereport_wsexport');
                    } else {
                        $sent_date = date($this->data_format, strtotime($current_student['dataAtualizacao']));
                    }

                    if (!$current_student['lastupdatebymoodle']) {

                        $this->statistics['updated_on_remote']++;

                        $alert .= '<p>'.get_string('grade_updated_on_remote', 'gradereport_wsexport').'</p>';

                        $grade_on_remote_hidden = '<input type="hidden" name="remotegrades['.$student->username.']" value="1"/>';
                    }
                }
                if (is_null($student->moodle_grade) || $student->moodle_grade == '-') {
                    $alert .='<p class="null_grade">'.get_string('warning_null_grade', 'gradereport_wsexport', $this->grades_to_send[$student->id]).'</p>';
                }

                $grade_hidden =  '<input type="hidden" name="grades['.$student->username.']" value="'.$this->grades_to_send[$student->id].'"/>';

                $grade_in_remote_formatted = str_replace('.', ',', (string)$grade_in_remote);

                if ($this->grades_differ($has_fi, $this->grades_to_send[$student->id], $grade_in_remote))  {
                    $grade_in_remote = str_replace('.', ',', (string)$grade_in_remote);

                    $grade_in_moodle = '<span class="diff_grade">'.
                                       $student->moodle_grade.$grade_hidden.$grade_on_remote_hidden.
                                       '</span>';
                    $grade_in_remote = '<span class="diff_grade">'.$grade_in_remote_formatted.'</span>';
                    $alert .= '<p class="diff_grade">'.get_string('warning_diff_grade', 'gradereport_wsexport').'</p>';

                } else {
                    $grade_in_moodle = $student->moodle_grade . $grade_hidden . $grade_on_remote_hidden;
                }

                $row = array(fullname($student), $grade_in_moodle);

                /*
                TODO:
                if ($this->show_mencaoI) {
                    $row[] = $this->get_checkbox("mentions[{$student->username}]", $has_mencao_i, $this->cannot_submit);
                }
                if ($this->show_fi) {
                    $row[] = $this->get_checkbox("insufficientattendances[{$student->username}]", $has_fi, $this->cannot_submit);
                }
                */

                $row = array_merge($row, array($grade_in_remote_formatted, $sent_date, $alert));
                $rows[] = $row;

            } else {
                $this->not_in_remote_students[] = $student;
            }
        }
        $this->statistics['not_in_moodle'] = sizeof($this->remote_grades); // Os que estão no moodle foram removidos.
        return $rows;
    }

    private function fill_table_not_in_moodle() {
        $rows = array();

        // Caso a seleção seja feita por algum grupo,
        // são desconsiderados os alunos que estão no CAGR e não estão no Moodle.
        if (empty($this->group)) {

            // Nesse ponto $this->remote_grades contém apenas os estudantes que não estão no moodle,
            // isso ocorre por que essa função é chamada após fill_table_ok().
            foreach  ($this->remote_grades as $matricula => $student) {
                list($has_mencao_i, $grade_in_remote) = $this->get_grade_and_mencao_i($student);

                $row = array($student['nome'] . ' (' . $matricula . ')', ''); // Moodle grade doesn't exist.

                /*
                 TODO
                if ($this->show_mencaoI) {
                    $row[] = $this->get_checkbox("mentions[]", $has_mencao_i, $this->cannot_submit);
                }
                if ($this->show_fi) {
                    $row[] = $this->get_checkbox("insufficientattendances[{$matricula}]", $student['frequencia'] == 'FI', true);
                }
                */

                if (empty($student['nota']) || empty($student['dataAtualizacao'])) {
                    $sent_date = get_string('never_sent', 'gradereport_wsexport');
                } else {
                    $sent_date = date($this->data_format, strtotime($student['dataAtualizacao']));
                }

                $row = array_merge($row, array($grade_in_remote, $sent_date, ''));
                $rows[] = $row;
            }
        }
        return $rows;
    }

    private function fill_table_not_in_remote() {

        $rows = array();
        $this->statistics['not_in_remote'] = sizeof($this->not_in_remote_students);

        foreach ($this->not_in_remote_students as $student) {

            $row = array(fullname($student), $this->moodle_grades[$student->id]);

            /*
            TODO
            if ($this->show_mencaoI) {
                $row[] = $this->get_checkbox("mentions[]", '', true);
            }
            if ($this->show_fi) {
                $row[] = $this->get_checkbox("insufficientattendances[{$student->username}]", false, true);
            }
            */

            $rows[] = $row;
        }
        return $rows;
    }

    private function get_grade_and_mencao_i($st) {

        $i = false;

        if (!empty($st['mencao']) && $st['mencao'] != ' ') {
            $grade = "(I)"; // Se o aluno tem mencao I, entao a nota eh zero.
            $i = true;
        } else {
            $grade = $st['nota'];
        }
        return array($i, $grade);
    }

    private function setup_and_fill_tables() {
        // The order here does not matter, only when filling.
        $this->setup_table_ok();
        $this->setup_table_not_in_remote();
        $this->setup_table_not_in_moodle();

        $this->fill_tables();
    }

    private function setup_table_not_in_remote() {

        $columns = $this->get_table_columns();

        $this->table_not_in_remote = new flexible_table('gradereport-wsexport-not_in_remote');
        $this->table_not_in_remote->define_columns($columns);
        $this->table_not_in_remote->define_headers($this->get_table_headers());
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

        $columns = $this->get_table_columns();

        $this->table_not_in_moodle = new flexible_table('gradereport-wsexport-not_in_moodle');
        $this->table_not_in_moodle->define_columns($columns);
        $this->table_not_in_moodle->define_headers($this->get_table_headers());
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

        $columns = $this->get_table_columns();

        $this->table_ok = new flexible_table('gradereport-wsexport-ok');
        $this->table_ok->define_columns($columns);
        $this->table_ok->define_headers($this->get_table_headers());
        $this->table_ok->define_baseurl($this->baseurl);

        $this->table_ok->set_attribute('cellspacing', '0');
        $this->table_ok->set_attribute('id', 'gradereport-wsexport-ok');
        $this->table_ok->set_attribute('class', 'boxaligncenter generaltable');

        foreach ($columns as $c) {
            $this->table_ok->column_class($c, $c);
        }

        $this->table_ok->setup();
    }

    private function get_table_headers() {

        $h = array(get_string('name'), get_string('moodle_grade', 'gradereport_wsexport'));

        /*
        TODO
        if ($this->show_mencaoI) {
            $h[] = get_string('mention', 'gradereport_wsexport');
        }
        if ($this->show_fi) {
            $h[] = get_string('fi', 'gradereport_wsexport');
        }
        */

        return array_merge($h, array(get_string('remote_grade', 'gradereport_wsexport'),
                                     get_string('sent_date', 'gradereport_wsexport'),
                                     get_string('alerts', 'gradereport_wsexport')));
    }

    private function get_table_columns() {
        $c = array('name', 'grade');
        /*
        TODO
        if ($this->show_mencaoI) {
            $c[] = 'mention';
        }
        if ($this->show_fi) {
            $c[] = 'fi';
        }
        */
        // Done this way because the order of columns.
        return array_merge($c, array('remote_grade', 'sent_date', 'alerts'));
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

    // Caso course esteja agrupado em um metacurso, retorna o id deste.
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

    private function get_grades_from_webservice() {
        global $DB, $CFG, $USER;

        $courseshortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));
        $url = $CFG->grade_report_wsexport_get_grades_url;
        $functionname = $CFG->grade_report_wsexport_get_grades_function_name;
        $params = array($CFG->grade_report_wsexport_get_grades_username_param => $USER->username,
                        $CFG->grade_report_wsexport_get_grades_course_param => $courseshortname);

        return $this->call_ws($url, $functionname, $params);
    }

    private function are_grades_valid($grades, $course_grade_item) {
        global $DB, $CFG, $USER;

        // TODO: should really check this?
        if ($course_grade_item->gradetype != GRADE_TYPE_VALUE) {
            $this->grades_format_status = 'invalid_grade_item_remote';
            return false;
        }

        if ($course_grade_item->display == 0) {
            // O displaytype do item não foi definido, então temos que pegar o displaytype do curso.
            if(!$display = $DB->get_field('grade_settings', 'value', array('courseid' => $course_grade_item->courseid, 'name' => 'displaytype'))) {
                $display = $CFG->grade_displaytype;
            }
        } else {
            $display = $course_grade_item->display;
        }

        // TODO: should really check this?
        if ($display != GRADE_DISPLAY_TYPE_REAL) {
            $this->grades_format_status = 'invalid_grade_item_remote';
            return false;
        }

        $courseshortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));
        $url = $CFG->grade_report_wsexport_are_grades_valid_url;
        $functionname = $CFG->grade_report_wsexport_are_grades_valid_function_name;
        $params = array($CFG->grade_report_wsexport_are_grades_valid_username_param => $USER->username,
                        $CFG->grade_report_wsexport_are_grades_valid_course_param => $courseshortname,
                        $CFG->grade_report_wsexport_are_grades_valid_grades_param => $this->moodle_grades);

        try {
            $this->gradesvalidationresults = $this->call_ws($url, $functionname, $params); // TODO: create config for this.
            foreach ($this->gradesvalidationresults as $r) {
                if (!$r) {
                    $this->grades_format_status = 'unformatted_remotegrades';
                    return false;
                }
            }
        } catch (Exception $e) {
            // TODO: handle validation errors
            return true;
        }
        return true;
    }

    public function is_meta_course($courseid) {
        global $DB;
        $sql = "SELECT DISTINCT cm.id
                 FROM {course} cm
                 JOIN {enrol} e
                   ON (e.courseid = cm.id AND
                       e.enrol = 'meta')
                WHERE cm.id = ?";
        return $DB->record_exists_sql($sql, array($courseid));
    }

    private function get_checkbox($name, $checked, $disabled) {
        $check = $checked ? 'checked="checked"' : '';
        $dis = $disabled ? 'disabled="disabled"' : '';
        return '<input type="checkbox" name="'.$name.'" '.$check.' value="1" '.$dis.'/>';
    }

    public function lista_turmas_afiliadas($courseid) {
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
                            AND c.id = {$courseid}";
        $turmas = $DB->get_records_sql($sql);

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
    private function grades_differ($has_fi, $moodle_grade, $ca_grade) {
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
}
