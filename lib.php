<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/grade/report/lib.php');
require_once($CFG->dirroot.'/grade/report/wsexport/weblib.php');
require_once($CFG->libdir.'/tablelib.php');

class grade_report_wsexport extends grade_report {

    private $moodle_students = array(); // um array com os alunos vindos do moodle - inicializado em fill_table()
    private $moodle_grades = array(); // um array com as notas dos alunos do moodle - inicializado em get_moodle_grades()
    private $grades_to_send = array(); // um array com as notas dos alunos do moodle a serem enviadas para o CAGR/CAPG - inicializado em get_moodle_grades()
    private $not_in_remote_students = array(); // um array com os alunos do moodle que nao estao no remote - inicializado em fill_ok_table()

    // um array com as contagens de alunos por problema
    private $statistics = array('not_in_remote' => 0, 'not_in_moodle' => 0, 'ok' => 0,
                                'unformatted_grades' => 0, 'updated_on_remote' => 0);

    private $send_results = array(); // um array (matricula => msg) com as msgs de erro de envio de notas

    private $show_fi = null; // from CFG, if must show the 'FI' column
    private $show_mencaoI = null;

    private $are_grades_in_history = false; // if the grades were already in student's history
    private $cannot_submit = false; // if there is something preventing grades sending, set it to true

    private $using_metacourse_grades = false; // if we retrieving grades from metacourse
    private $has_metacourse = false; // if courseid belongs to a metacourse

    private $data_format = "d/m/Y H:i"; // o formato da data mostrada na listagem

    private $grades_format_status = 'all_grades_formatted'; // o estado da notas quanto à sua formatação (fracionamento, escala, letra, etc)

    private $group = null; //if not selected group

    function __construct($courseid, $gpr, $context, $force_course_grades, $group=null, $page=null) {
        global $CFG, $USER, $DB;

        parent::__construct($courseid, $gpr, $context, $page);

        if (isset($USER->send_results)) {
            unset($USER->send_results);
        }

        $this->show_fi = (isset($CFG->grade_report_wsexport_show_fi) &&
                          $CFG->grade_report_wsexport_show_fi == true);

        $this->group = $group;

        $coursecontext = context_course::instance($this->courseid);
        $this->moodle_students = get_role_users($DB->get_field('role', 'id', array('shortname' => 'student')),
                                                $coursecontext, false, '', 'u.firstname, u.lastname', null, $this->group);

        $this->get_course_grade_item($force_course_grades);

        $this->show_mention = isset($CFG->grade_report_wsexport_show_mention);

        $this->get_moodle_grades();
        $this->remote_grades = $this->get_grades_from_webservice();

        $this->are_grades_in_history = false;//TODO: this test should be done by webservice on is_allowed_to_send_grades().
        if ($this->are_grades_in_history == true) {
            $this->cannot_submit = true;
        }

        $this->in_submission_date_range = true;//TODO: this test should be done by webservice on is_allowed_to_send_grades().
        if (!$this->in_submission_date_range) {
            $this->cannot_submit = true;
        }

        $this->grades_format_status = $this->grades_format_status($this->moodle_grades, $this->course_grade_item);
        if ($this->grades_format_status != 'all_grades_formatted') {
            $this->cannot_submit = true;
        }

        $this->pbarurl = 'index.php?id='.$this->courseid;

        $this->setup_groups($group);

        if (!empty($this->group) && !is_null($this->group)) {
            $this->cannot_submit = true;
        }
    }

    function process_data($data){//TODO?
    }

    function process_action($target, $action){//TODO?
    }

    public function show() {

        $this->setup_ok_table();
        $this->setup_not_in_remote_table();
        $this->setup_not_in_moodle_table();

        echo $this->group_selector . '<br/>';

        $this->is_allowed_to_send_grades();

        $this->msg_using_metacourse_grades();

        $this->msg_unformatted_grades();
        $this->msg_grade_updated_on_remote();
        $this->msg_groups();

        echo "<form method=\"post\" action=\"confirm.php?id={$this->courseid}\">";

        $this->print_tables();

        echo '<div class="report_footer">';

        $this->is_allowed_to_send_grades();

        $this->msg_unformatted_grades();
        $this->msg_grade_updated_on_remote();
        $this->msg_groups();

        $this->select_overwrite_grades();

        $str_submit_button = get_string('submit_button', 'gradereport_wsexport');
        $dis = ($this->cannot_submit == true) ? 'disabled="disabled"' : '';
        echo '<input type="submit" value="',$str_submit_button , '" ', $dis,' />',
             '</div></form>';
    }

    public function send_grades($grades, $mentions, $fis) {
        $this->send_grades_to_webservice($grades, $mentions, $fis);
    }

    private function print_tables() {
        global $CFG;

        //funções "fill_" devem ser chamadas nesta ordem
        $rows_ok = $this->fill_ok_table();
        $rows_not_in_moodle = $this->fill_not_in_moodle_table();
        $rows_not_in_remote = $this->fill_not_in_remote_table();

        ob_start();
        $this->print_table_not_in_moodle($rows_not_in_moodle);
        $this->print_table_not_in_remote($rows_not_in_remote);
        $this->print_ok_table($rows_ok);
        ob_end_flush();
    }

    private function print_table_not_in_moodle($rows) {
        if(!empty($rows) && ($this->statistics['not_in_moodle']  > 0)){
            echo '<h2 class="table_title">',
                 get_string('students_not_in_moodle', 'gradereport_wsexport'),
                 ' - ', $this->statistics['not_in_moodle'], get_string('students', 'gradereport_wsexport'),
                 get_string('wont_be_sent', 'gradereport_wsexport'),
                 '</h2>';
            foreach($rows as $row){
                $this->table_not_in_moodle->add_data($row);
            }
            $this->table_not_in_moodle->print_html();//apenas finaliza tabela
        }
    }

    private function print_table_not_in_remote($rows) {
        if (!empty($rows) && $this->statistics['not_in_remote'] > 0) {
            echo '<h2 class="table_title">',
                 get_string('students_not_in_remote', 'gradereport_wsexport'),
                 ' - ', $this->statistics['not_in_remote'], get_string('students', 'gradereport_wsexport'),
                 get_string('wont_be_sent', 'gradereport_wsexport'),
                 '</h2>';
            foreach($rows as $row){
                $this->table_not_in_remote->add_data(array_merge($row, array('', '', '')));//Moodle 2: já imprime
            }
            $this->table_not_in_remote->print_html();//apenas finaliza tabela
        }
    }

    private function print_ok_table($rows) {

        echo '<h2 class="table_title">', get_string('students_ok', 'gradereport_wsexport');
        echo ' - ', $this->statistics['ok'], get_string('students', 'gradereport_wsexport');
        echo '</h2>';
        foreach($rows as $row){
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
                                                                        $this->get_displaytype(), null);
                } else {
                    $this->moodle_grades[$st->id] = grade_format_gradevalue(null,
                                                                        $this->course_grade_item, true,
                                                                        $this->course_grade_item->get_displaytype(), null);
                    $this->grades_to_send[$st->id] = grade_format_gradevalue(0,
                                                                        $this->course_grade_item, false,
                                                                        $this->get_displaytype(), null);
                }
            }
        }
    }

    private function fill_ok_table() {
        global $CFG;

        $rows = array();
        if (!is_array($this->moodle_students)) {
            return false; // nenhum estudante no moodle
        }
        foreach ($this->moodle_students as $student) {
            $student->moodle_grade = $this->moodle_grades[$student->id];
            if (isset($this->remote_grades[$student->username])) {
                // o estudante esta no remote

                $current_student = $this->remote_grades[$student->username];

                $this->statistics['ok']++;
                unset($this->remote_grades[$student->username]);

                list($has_mencao_i, $grade_in_remote) = $this->get_grade_and_mencao_i($current_student);

                $has_fi = $current_student['frequencia'] == 'FI' || $current_student['frequencia'] == 'I';
                $sent_date = '';
                $alert = '';
                $grade_on_remote_hidden = '';
                $usuario = trim(strtolower($current_student['usuario']));

                if (empty($current_student['nota']) && $current_student['nota'] != '0') {
                    $sent_date = get_string('never_sent', 'gradereport_wsexport');
                } else {
                    if (empty($current_student['dataAtualizacao'])) {
                        $sent_date = get_string('never_sent', 'gradereport_wsexport');
                    } else {
                        $sent_date = date($this->data_format, strtotime($current_student['dataAtualizacao']));
                    }

                    if (!$this->are_grades_in_history && !empty($usuario) && $usuario != strtolower($CFG->grade_report_wsexport_remote_user)) {

                        $this->statistics['updated_on_remote']++;

                        $alert .= '<p>'.get_string('grade_updated_on_remote', 'gradereport_wsexport').'</p>';

                        $grade_on_remote_hidden = '<input type="hidden" name="grades_remote['.$student->username.']" value="1"/>';
                    }
                }
                if (is_null($student->moodle_grade) || $student->moodle_grade == '-') {
                    $alert .='<p class="null_grade">'.get_string('warning_null_grade', 'gradereport_wsexport', $this->grades_to_send[$student->id]).'</p>';
                }

                $grade_hidden =  '<input type="hidden" name="grades['.$student->username.']" value="'.$this->grades_to_send[$student->id].'"/>';

                $grade_in_remote_formatted = str_replace('.', ',', (string)$grade_in_remote);

                if ($this->grade_differ($has_fi, $this->grades_to_send[$student->id], $grade_in_remote))  {
                    $grade_in_remote = str_replace('.', ',', (string)$grade_in_remote);

                    $grade_in_moodle = '<span class="diff_grade">'.
                                       $student->moodle_grade.$grade_hidden.$grade_on_remote_hidden.
                                       '</span>';
                    $grade_in_remote = '<span class="diff_grade">'.$grade_in_remote_formatted.'</span>';
                    $alert .= '<p class="diff_grade">'.get_string('warning_diff_grade', 'gradereport_wsexport').'</p>';

                } else {
                    $grade_in_moodle = $student->moodle_grade . $grade_hidden . $grade_on_remote_hidden;
                }

                // montando a linha da tabela
                $row = array(fullname($student),
                             $grade_in_moodle
                            );

                if ($this->show_mencaoI) {
                    $row[] = get_checkbox("mentions[{$student->username}]", $has_mencao_i, $this->cannot_submit);
                }
                if ($this->show_fi) {
                    $row[] = get_checkbox("fis[{$student->username}]", $has_fi, $this->cannot_submit);
                }

                $row = array_merge($row, array($grade_in_remote_formatted, $sent_date, $alert));
                $rows[] = $row;

                //$this->table_ok->add_data($row); //Moodle 2: imprime tabela
            } else {
                // o aluno nao esta no remote
                $this->not_in_remote_students[] = $student;
            }
        }
        $this->statistics['not_in_moodle'] = sizeof($this->remote_grades);//os que estão no moodle foram removidos
        return $rows;
    }

    private function fill_not_in_moodle_table() {
        $rows = array();

        // caso a seleção seja feita por algum grupo é desconsiderado os alunos que estão no CAGR e não estão no Moodle
        if (empty($this->group)) {

            // agora, $this->remote_grades contém apenas os estudantes que não estão no moodle
            // isso ocorre por que essa função é chamada após fill_ok_table()
            foreach  ($this->remote_grades as $matricula => $student) {
                list($has_mencao_i, $grade_in_remote) = $this->get_grade_and_mencao_i($student);

                $row = array($student['nome'] . ' (' . $matricula . ')',
                             ''); // the moodle grade doesn't exist

                if ($this->show_mencaoI) {
                    $row[] = get_checkbox("mentions[]", $has_mencao_i, $this->cannot_submit);
                }
                if ($this->show_fi) {
                    $row[] = get_checkbox("fis[{$matricula}]", $student['frequencia'] == 'FI', true);
                }

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

    private function fill_not_in_remote_table() {
        $rows = array();
        $this->statistics['not_in_remote'] = sizeof($this->not_in_remote_students);
        foreach ($this->not_in_remote_students as $student) {

            $row = array(fullname($student),
                         $this->moodle_grades[$student->id]);

            if ($this->show_mencaoI) {
                $row[] = get_checkbox("mentions[]", '', true);
            }
            if ($this->show_fi) {
                $row[] = get_checkbox("fis[{$student->username}]", false, true);
            }

            $rows[] = $row;
        }
        return $rows;
    }

    private function get_grade_and_mencao_i($st) {

        //inicialmente nao temos mencao i
        $i = false;

        if (!empty($st['mencao']) && $st['mencao'] != ' ') {
            $grade = "(I)"; // se o aluno tem mencao I, entao a nota eh zero
            $i = true;
        } else {
            $grade = $st['nota'];
        }
        return array($i, $grade);
    }

    private function setup_not_in_remote_table() {

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
        return true;
    }

    private function setup_not_in_moodle_table() {

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
        return true;
    }

    private function setup_ok_table() {

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

        return true;
    }

    private function get_table_headers() {

        $h = array(get_string('name'),
                   get_string('moodle_grade', 'gradereport_wsexport')
                  );

        if ($this->show_mencaoI) {
            $h[] = get_string('mention', 'gradereport_wsexport');
        }
        if ($this->show_fi) {
            $h[] = get_string('fi', 'gradereport_wsexport');
        }

        return array_merge($h, array(get_string('remote_grade', 'gradereport_wsexport'),
                                     get_string('sent_date', 'gradereport_wsexport'),
                                     get_string('alerts', 'gradereport_wsexport'),
                                    )
                          );
    }

    private function get_table_columns() {
        $c = array('name', 'grade');
        if ($this->show_mencaoI) {
            $c[] = 'mention';
        }
        if ($this->show_fi) {
            $c[] = 'fi';
        }
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

    private function msg_unformatted_grades() {
        global $CFG;

        $url = "{$CFG->wwwroot}/grade/edit/tree/category.php?courseid={$this->course_grade_item->courseid}&id={$this->course_grade_item->iteminstance}";
        echo '<p class="error prevent">', get_string($this->grades_format_status, 'gradereport_wsexport', $url), '</p>';
    }

    private function msg_grade_updated_on_remote() {
        if ($this->statistics['updated_on_remote'] > 0) {
            echo '<p class="warning">', get_string('grades_updated_on_remote', 'gradereport_wsexport'), '</p>';
        }
    }

    private function msg_groups() {
        if (!empty($this->group) && !is_null($this->group)) {
            echo '<p class="warning prevent">', get_string('grades_selected_by_group', 'gradereport_wsexport'), '</p>';
        }
    }

    private function msg_using_metacourse_grades() {
        global $CFG;
        if ($this->using_metacourse_grades) {
            echo '<p class="warning">',
                 get_string('using_metacourse_grades', 'gradereport_wsexport'),
                 ' <a href="'.$CFG->wwwroot.'/grade/report/wsexport/index.php?id='.$this->courseid.'&force_course_grades=1">',
                 get_string('dont_use_metacourse_grades', 'gradereport_wsexport'),
                 '</a></p>';
        } else if ($this->has_metacourse) {
            echo '<p class="warning">',
                 get_string('using_course_grades', 'gradereport_wsexport'),
                 ' <a href="'.$CFG->wwwroot.'/grade/report/wsexport/index.php?id='.$this->courseid.'&force_course_grades=0">',
                 get_string('use_metacourse_grades', 'gradereport_wsexport'),
                 '</a></p>';
        }
    }

    //Caso course esteja agrupado em um metacurso, retorna o id deste
    private function get_parent_meta_id(){
        global $DB, $CFG;

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
        // TODO: call webservice and get grades based on course shortname.
        return array('06232009' => array(
            'matricula' => '06232009',
            'nome' => 'Daniel Neis',
            'nota' => 10,
            'mencao' => '',
            'frequencia' => 'FS',
            'usuario' => 'alguém',
            'dataAtualizacao' => '2015-07-06 15:15:00'
        ));
    }

    function get_displaytype() {
        // TODO: get this from webservice?
        return GRADE_DISPLAY_TYPE_REAL;
    }

    // TODO: make it pluggable.
    function grades_format_status($grades, $course_grade_item) {
        global $DB, $CFG;

        if ($course_grade_item->gradetype != GRADE_TYPE_VALUE) {
            return 'invalid_grade_item_remote';
        }
        if ($course_grade_item->display == 0) {
            // o displaytype do item não foi definido, então temos que pegar o displaytype do curso
            if(!$display = $DB->get_field('grade_settings', 'value', array('courseid' => $course_grade_item->courseid, 'name' => 'displaytype'))) {
                $display = $CFG->grade_displaytype;
            }
        } else {
            $display = $course_grade_item->display;
        }
        if($display != GRADE_DISPLAY_TYPE_REAL) {
            return 'invalid_grade_item_remote';
        }
        if($course_grade_item->grademax != 10 || $course_grade_item->grademin != 0) {
            return 'invalid_grade_item_remote';
        }

        $unformatted_grades = 0;
        foreach ($grades as $userid => $grade) {
            $grade = str_replace(',', '.', $grade);
            if (is_numeric($grade)) {
                $decimal_value = $grade - (int)$grade;
                if ($decimal_value != 0 && $decimal_value != 0.5) {
                    return 'unformatted_grades_remote';
                } else if ($grade < 0 || $grade > 10) {
                    return 'unformatted_grades_remote';
                }
            } else if($grade != '-') {
                return 'unformatted_grades_remote';
            }
        }
        return 'all_grades_formatted';
    }

    private function grade_differ($has_fi, $moodle_grade, $ca_grade) {
        return ($ca_grade != '(I)') &&
               ($ca_grade != null) &&
               (($moodle_grade != null) && !$has_fi) &&
               (($moodle_grade != $ca_grade) && !$has_fi);
    }

    private function is_allowed_to_send_grades() {
        // TODO: call webservice and get a message about the grades.
        // Webservice must inform :
        // i) if grades cannot be sent, why?
        // ii) if grande can be sent, any details wanted.
        return array(true, 'Grades can be sent until August 1st.');
    }
}
