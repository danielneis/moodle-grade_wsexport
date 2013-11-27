<?php
require_once($CFG->dirroot.'/grade/report/lib.php');
require_once($CFG->dirroot.'/grade/report/transposicao/weblib.php');
require_once($CFG->libdir.'/tablelib.php');

require_once($CFG->dirroot.'/local/academico/lib.php');

class grade_report_transposicao extends grade_report {

    private $klass; // um registro (curso, disciplina, turma, periodo) da tabela Turmas - inicializado em get_class_from_middleware
    private $moodle_students = array(); // um array com os alunos vindos do moodle - inicializado em fill_table()
    private $moodle_grades = array(); // um array com as notas dos alunos do moodle - inicializado em get_moodle_grades()
    private $grades_to_send = array(); // um array com as notas dos alunos do moodle a serem enviadas para o CAGR/CAPG - inicializado em get_moodle_grades()
    private $not_in_cagr_students = array(); // um array com os alunos do moodle que nao estao no cagr - inicializado em fill_ok_table()

    // um array com as contagens de alunos por problema
    private $statistics = array('not_in_cagr' => 0, 'not_in_moodle' => 0, 'ok' => 0,
                                'unformatted_grades' => 0, 'updated_on_cagr' => 0);

    private $send_results = array(); // um array (matricula => msg) com as msgs de erro de envio de notas

    private $show_fi = null; // from CFG, if must show the 'FI' column
    private $show_mencaoI = null;

    private $is_grades_in_history = false; // if the grades were already in student's history
    private $cannot_submit = false; // if there is something preventing grades sending, set it to true

    private $using_metacourse_grades = false; // if we retrieving grades from metacourse
    private $has_metacourse = false; // if courseid belongs to a metacourse

    private $data_format = "d/m/Y H:i"; // o formato da data mostrada na listagem

    private $grades_format_status = 'all_grades_formatted'; // o estado da notas quanto à sua formatação (fracionamento, escala, letra, etc)

    private $group = null; //if not selected group

    function __construct($courseid, $gpr, $context, $force_course_grades, $group=null, $page=null) {
        global $CFG, $USER, $DB;

        $dbname = academico::dbname();
        if (empty($dbname)) {
            $url = "{$CFG->wwwroot}/grade/report/grader/index.php?id={$courseid}";
            print_error('not_configured_contact_admin', $url);
        }

        parent::__construct($courseid, $gpr, $context, $page);

        if (isset($USER->send_results)) {
            unset($USER->send_results);
        }

        $this->show_fi = (isset($CFG->grade_report_transposicao_show_fi) &&
                          $CFG->grade_report_transposicao_show_fi == true);

        $this->group = $group;

        $context = get_context_instance(CONTEXT_COURSE, $this->courseid);
        $this->moodle_students = get_role_users($DB->get_field('role', 'id', array('shortname' => 'student')),
                                                $context, false, '', 'u.firstname, u.lastname', null, $this->group);

        $this->get_course_grade_item($force_course_grades);
        $this->get_class_from_middleware();

        if ($this->klass->modalidade == 'GR') {

            require_once('cagr.php');
            $this->controle_academico = new TransposicaoCAGR($this->klass, $this->courseid);
            $this->show_mencaoI = true;

        } else if (in_array($this->klass->modalidade, array('ES', 'ME', 'MP', 'DO'))) {

            require_once('capg.php');
            $this->controle_academico = new TransposicaoCAPG($this->klass, $this->courseid);
            $this->show_mencaoI = false;

        } else {
            $url = "{$CFG->wwwroot}/grade/report/grader/index.php?id={$this->courseid}";
            print_error('modalidade_not_grad_nor_pos', 'gradereport_transposicao', $url);
        }

        $this->get_moodle_grades();
        $this->controle_academico_grades = $this->controle_academico->get_grades();

        $this->is_grades_in_history = $this->controle_academico->is_grades_in_history();

        if ($this->is_grades_in_history == true) {
            $this->cannot_submit = true;
        }

        $this->in_submission_date_range = $this->controle_academico->in_submission_date_range();

        if (!$this->in_submission_date_range) {
            $this->cannot_submit = true;
        }

        $this->grades_format_status = $this->controle_academico->grades_format_status($this->moodle_grades, $this->course_grade_item);

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
        $this->setup_table();
        $this->print_group_selector();
        $this->print_header();
        $this->print_tables();
        $this->print_footer();
    }

    public function send_grades($grades, $mentions, $fis) {
        $this->controle_academico->send_grades($grades, $mentions, $fis);
    }

    private function setup_table() {
        $this->setup_ok_table();
        $this->setup_not_in_cagr_table();
        $this->setup_not_in_moodle_table();
    }

    private function print_header() {
        $this->msg_submission_dates();
        $this->msg_unformatted_grades();
        $this->msg_grade_in_history();
        $this->msg_grade_updated_on_cagr();
        $this->msg_using_metacourse_grades();
        $this->msg_groups();
        echo "<form method=\"post\" action=\"confirm.php?id={$this->courseid}\">";
    }

    private function print_group_selector() {
        echo $this->group_selector . '<br>';
    }

    private function print_tables() {
        global $CFG;

        //funções "fill_" devem ser chamadas nesta ordem
        $rows_ok = $this->fill_ok_table();
        $rows_not_in_moodle = $this->fill_not_in_moodle_table();
        $rows_not_in_cagr = $this->fill_not_in_cagr_table();

        if(!($rows_ok && $rows_not_in_moodle && $rows_not_in_cagr)){
            $url = "{$CFG->wwwroot}/grade/report/grader/index.php?id={$this->courseid}";
            print_error('cannot_populate_tables', 'gradereport_transposicao', $url);
        }

        ob_start();
        $this->print_table_not_in_moodle($rows_not_in_moodle);
        $this->print_table_not_in_cagr($rows_not_in_cagr);
        $this->print_ok_table($rows_ok);
        ob_end_flush();
    }

    private function print_table_not_in_moodle($rows) {
        if(!empty($rows) && ($this->statistics['not_in_moodle']  > 0)){
            echo '<h2 class="table_title">',
                 get_string('students_not_in_moodle', 'gradereport_transposicao'),
                 ' - ', $this->statistics['not_in_moodle'], get_string('students', 'gradereport_transposicao'),
                 get_string('wont_be_sent', 'gradereport_transposicao'),
                 '</h2>';
            foreach($rows as $row){
                $this->table_not_in_moodle->add_data($row);
            }
            $this->table_not_in_moodle->print_html();//apenas finaliza tabela
        }
    }

    private function print_table_not_in_cagr($rows) {
        if (!empty($rows) && $this->statistics['not_in_cagr'] > 0) {
            echo '<h2 class="table_title">',
                 get_string('students_not_in_cagr', 'gradereport_transposicao'),
                 ' - ', $this->statistics['not_in_cagr'], get_string('students', 'gradereport_transposicao'),
                 get_string('wont_be_sent', 'gradereport_transposicao'),
                 '</h2>';
            foreach($rows as $row){
                $this->table_not_in_cagr->add_data(array_merge($row, array('', '', '')));//Moodle 2: já imprime
            }
            $this->table_not_in_cagr->print_html();//apenas finaliza tabela
        }
    }

    private function print_ok_table($rows) {
        if(!empty($rows)){//improvável: se for vazio já lançou exceção
            echo '<h2 class="table_title">', get_string('students_ok', 'gradereport_transposicao');
            if ($this->statistics['ok'] > 0) {
                echo ' - ', $this->statistics['ok'], get_string('students', 'gradereport_transposicao');
            }
            echo '</h2>';
            foreach($rows as $row){
                $this->table_ok->add_data($row);
            }
            $this->table_ok->print_html();
        }
    }

    private function print_footer() {
        echo '<div class="report_footer">';
        $this->msg_submission_dates();
        $this->msg_unformatted_grades();
        $this->msg_grade_in_history();
        $this->msg_grade_updated_on_cagr();
        $this->msg_groups();
        $this->select_overwrite_grades();

        $str_submit_button = get_string('submit_button', 'gradereport_transposicao');
        $dis = ($this->cannot_submit == true) ? 'disabled="disabled"' : '';
        echo '<input type="submit" value="',$str_submit_button , '" ', $dis,' />',
             '</div></form>';
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
            $pg_scale = new grade_scale(array('id' => $CFG->grade_report_transposicao_escala_pg));
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
                                                                        $this->controle_academico->get_displaytype(), null);
                } else {
                    $this->moodle_grades[$st->id] = grade_format_gradevalue(null,
                                                                        $this->course_grade_item, true,
                                                                        $this->course_grade_item->get_displaytype(), null);
                    $this->grades_to_send[$st->id] = grade_format_gradevalue(0,
                                                                        $this->course_grade_item, false,
                                                                        $this->controle_academico->get_displaytype(), null);
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
            if (isset($this->controle_academico_grades[$student->username])) {
                // o estudante esta no cagr

                $current_student = $this->controle_academico_grades[$student->username];

                $this->statistics['ok']++;
                unset($this->controle_academico_grades[$student->username]);

                list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($current_student);

                $has_fi = ($current_student['frequencia'] == 'FI');
                $sent_date = '';
                $alert = '';
                $grade_on_cagr_hidden = '';
                $usuario = trim(strtolower($current_student['usuario']));

                if (empty($current_student['nota']) && $current_student['nota'] != '0') {
                    $sent_date = get_string('never_sent', 'gradereport_transposicao');
                } else {
                    if (empty($current_student['dataAtualizacao'])) {
                        $sent_date = get_string('never_sent', 'gradereport_transposicao');
                    } else {
                        $sent_date = date($this->data_format, strtotime($current_student['dataAtualizacao']));
                    }

                    if (!$this->is_grades_in_history && !empty($usuario) && $usuario != strtolower($CFG->grade_report_transposicao_cagr_user)) {

                        $this->statistics['updated_on_cagr']++;

                        $alert .= '<p>'.get_string('grade_updated_on_cagr', 'gradereport_transposicao').'</p>';

                        $grade_on_cagr_hidden = '<input type="hidden" name="grades_cagr['.$student->username.']" value="1"/>';
                    }
                }
                if (is_null($student->moodle_grade) || $student->moodle_grade == '-') {
                    $alert .='<p class="null_grade">'.get_string('warning_null_grade', 'gradereport_transposicao', $this->grades_to_send[$student->id]).'</p>';
                }

                $grade_hidden =  '<input type="hidden" name="grades['.$student->username.']" value="'.$this->grades_to_send[$student->id].'"/>';

                $grade_in_cagr_formatted = str_replace('.', ',', (string)$grade_in_cagr);

                if ($this->controle_academico->grade_differ($has_fi, $this->grades_to_send[$student->id], $grade_in_cagr))  {
                    $grade_in_cagr = str_replace('.', ',', (string)$grade_in_cagr);

                    $grade_in_moodle = '<span class="diff_grade">'.
                                       $student->moodle_grade.$grade_hidden.$grade_on_cagr_hidden.
                                       '</span>';
                    $grade_in_cagr = '<span class="diff_grade">'.$grade_in_cagr_formatted.'</span>';
                    $alert .= '<p class="diff_grade">'.get_string('warning_diff_grade', 'gradereport_transposicao').'</p>';

                } else {
                    $grade_in_moodle = $student->moodle_grade . $grade_hidden . $grade_on_cagr_hidden;
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

                $row = array_merge($row, array($grade_in_cagr_formatted, $sent_date, $alert));
                $rows[] = $row;

                //$this->table_ok->add_data($row); //Moodle 2: imprime tabela
            } else {
                // o aluno nao esta no cagr
                $this->not_in_cagr_students[] = $student;
            }
        }
        $this->statistics['not_in_moodle'] = sizeof($this->controle_academico_grades);//os que estão no moodle foram removidos
        return !empty($rows) ? $rows : false;
    }

    private function fill_not_in_moodle_table() {
        $rows = array();

        // caso a seleção seja feita por algum grupo é desconsiderado os alunos que estão no CAGR e não estão no Moodle
        if (empty($this->group)) {

            // agora, $this->controle_academico_grade contém apenas os estudantes que não estão no moodle
            // isso ocorre por que essa função é chamada após fill_ok_table()
            foreach  ($this->controle_academico_grades as $matricula => $student) {
                list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($student);

                $row = array($student['nome'] . ' (' . $matricula . ')',
                             ''); // the moodle grade doesn't exist

                if ($this->show_mencaoI) {
                    $row[] = get_checkbox("mentions[]", $has_mencao_i, $this->cannot_submit);
                }
                if ($this->show_fi) {
                    $row[] = get_checkbox("fis[{$matricula}]", $student['frequencia'] == 'FI', true);
                }

                if (empty($student['nota']) || empty($student['dataAtualizacao'])) {
                    $sent_date = get_string('never_sent', 'gradereport_transposicao');
                } else {
                    $sent_date = date($this->data_format, strtotime($student['dataAtualizacao']));
                }

                $row = array_merge($row, array($grade_in_cagr, $sent_date, ''));
                $rows[] = $row;
            }
        }
        return !empty($rows) ? $rows : true; //ok se for vazio
    }

    private function fill_not_in_cagr_table() {
        $rows = array();
        $this->statistics['not_in_cagr'] = sizeof($this->not_in_cagr_students);
        foreach ($this->not_in_cagr_students as $student) {

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
        return !empty($rows) ? $rows : true; //ok se for vazio
    }

    private function get_class_from_middleware() {
        global $CFG, $DB;

        $shortname = $DB->get_field('course', 'shortname', array('id' => $this->courseid));
        $sql = "SELECT curso, disciplina, turma, periodo, modalidade
                  FROM {geral_Turmas_Transposicao_Notas}
                 WHERE shortname = '{$shortname}'
                 LIMIT 1";
        if (!$this->klass = academico::get_record_sql($sql)) {
            $url = "{$CFG->wwwroot}/grade/report/grader/index.php?id={$this->courseid}";
            print_error('class_not_in_middleware', 'gradereport_transposicao', $url);
        }
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

    private function setup_not_in_cagr_table() {

        $columns = $this->get_table_columns();

        $this->table_not_in_cagr = new flexible_table('gradereport-transposicao-not_in_cagr');
        $this->table_not_in_cagr->define_columns($columns);
        $this->table_not_in_cagr->define_headers($this->get_table_headers());
        $this->table_not_in_cagr->define_baseurl($this->baseurl);

        $this->table_not_in_cagr->set_attribute('cellspacing', '0');
        $this->table_not_in_cagr->set_attribute('id', 'gradereport-transposicao-not_in_cagr');
        $this->table_not_in_cagr->set_attribute('class', 'boxaligncenter generaltable');

        foreach ($columns as $c) {
            $this->table_not_in_cagr->column_class($c, $c);
        }

        $this->table_not_in_cagr->setup();
        return true;
    }

    private function setup_not_in_moodle_table() {

        $columns = $this->get_table_columns();

        $this->table_not_in_moodle = new flexible_table('gradereport-transposicao-not_in_moodle');
        $this->table_not_in_moodle->define_columns($columns);
        $this->table_not_in_moodle->define_headers($this->get_table_headers());
        $this->table_not_in_moodle->define_baseurl($this->baseurl);

        $this->table_not_in_moodle->set_attribute('cellspacing', '0');
        $this->table_not_in_moodle->set_attribute('id', 'gradereport-transposicao-not_in_moodle');
        $this->table_not_in_moodle->set_attribute('class', 'boxaligncenter generaltable');

        foreach ($columns as $c) {
            $this->table_not_in_moodle->column_class($c, $c);
        }

        $this->table_not_in_moodle->setup();
        return true;
    }

    private function setup_ok_table() {

        $columns = $this->get_table_columns();

        $this->table_ok = new flexible_table('gradereport-transposicao-ok');
        $this->table_ok->define_columns($columns);
        $this->table_ok->define_headers($this->get_table_headers());
        $this->table_ok->define_baseurl($this->baseurl);

        $this->table_ok->set_attribute('cellspacing', '0');
        $this->table_ok->set_attribute('id', 'gradereport-transposicao-ok');
        $this->table_ok->set_attribute('class', 'boxaligncenter generaltable');

        foreach ($columns as $c) {
            $this->table_ok->column_class($c, $c);
        }

        $this->table_ok->setup();

        return true;
    }

    private function get_table_headers() {

        $h = array(get_string('name'),
                   get_string('moodle_grade', 'gradereport_transposicao')
                  );

        if ($this->show_mencaoI) {
            $h[] = get_string('mention', 'gradereport_transposicao');
        }
        if ($this->show_fi) {
            $h[] = get_string('fi', 'gradereport_transposicao');
        }

        return array_merge($h, array(get_string('cagr_grade', 'gradereport_transposicao'),
                                     get_string('sent_date', 'gradereport_transposicao'),
                                     get_string('alerts', 'gradereport_transposicao'),
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
        return array_merge($c, array('cagr_grade', 'sent_date', 'alerts'));
    }

    private function select_overwrite_grades() {

        if ($this->statistics['updated_on_cagr'] > 0) {

            $dis = $this->cannot_submit == true ? 'disable="disable"' : '';
            echo '<p class="overwrite_all">',
                 '<input type="checkbox" id="overwrite_all" name="overwrite_all" value="1"', $dis, ' />',
                 '<label for="overwrite_all">',get_string('overwrite_all_grades', 'gradereport_transposicao'), '</label>',
                 '</p>';
        }

    }

    private function msg_unformatted_grades() {
        global $CFG;

        $url = "{$CFG->wwwroot}/grade/edit/tree/category.php?courseid={$this->course_grade_item->courseid}&id={$this->course_grade_item->iteminstance}";
        echo '<p class="error prevent">', get_string($this->grades_format_status, 'gradereport_transposicao', $url), '</p>';
    }

    private function msg_grade_in_history() {
        if ($this->is_grades_in_history) {
            echo '<p class="warning prevent">', get_string('grades_in_history', 'gradereport_transposicao'), '</p>';
        }
    }

    private function msg_grade_updated_on_cagr() {
        if ($this->statistics['updated_on_cagr'] > 0) {
            echo '<p class="warning">', get_string('grades_updated_on_cagr', 'gradereport_transposicao'), '</p>';
        }
    }

    private function msg_submission_dates() {

        $status     = $this->controle_academico->submission_date_status();
        $date_range =  $this->controle_academico->get_submission_date_range();
        $class = '';
        if (($status != 'send_date_ok_cagr') && ($status != 'send_date_ok_capg')) {
            $class =  'warning prevent';
        }

        echo '<p class="grade_range ', $class, '">', get_string($status, 'gradereport_transposicao',$date_range), '</p>';
    }

    private function msg_groups() {
        if (!empty($this->group) && !is_null($this->group)) {
            echo '<p class="warning prevent">', get_string('grades_selected_by_group', 'gradereport_transposicao'), '</p>';
        }
    }

    private function msg_using_metacourse_grades() {
        global $CFG;
        if ($this->using_metacourse_grades) {
            echo '<p class="warning">',
                 get_string('using_metacourse_grades', 'gradereport_transposicao'),
                 ' <a href="'.$CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$this->courseid.'&force_course_grades=1">',
                 get_string('dont_use_metacourse_grades', 'gradereport_transposicao'),
                 '</a></p>';
        } else if ($this->has_metacourse) {
            echo '<p class="warning">',
                 get_string('using_course_grades', 'gradereport_transposicao'),
                 ' <a href="'.$CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$this->courseid.'&force_course_grades=0">',
                 get_string('use_metacourse_grades', 'gradereport_transposicao'),
                 '</a></p>';
        }
    }

    //Caso course esteja agrupado em um metacurso, retorna o id deste
    private function get_parent_meta_id(){
        global $DB, $CFG;

        $sql = "SELECT cm.id
                 FROM {$CFG->dbname}.{$CFG->prefix}course c
                 JOIN {$CFG->dbname}.{$CFG->prefix}enrol e
                   ON (e.customint1 = c.id)
                 JOIN {$CFG->dbname}.{$CFG->prefix}course cm
                   ON (cm.id = e.courseid)
                WHERE e.enrol = 'meta'
                  AND c.id = {$this->courseid}";
        if ($course = academico::get_record_sql($sql)) {
            return $course->id;
        } else {
            return false;
        }
    }
}
