<?php
require_once($CFG->dirroot.'/grade/report/lib.php');
require_once($CFG->libdir.'/tablelib.php');

class grade_report_transposicao extends grade_report {

    private $cagr_submission_date_range = null; // intervalo de envio de notas
    private $cagr_grades = array(); // um array com as notas vindas no CAGR
    private $klass; // um registro com disciplina, turma e periodo, vindo do middleware

    // um array com os alunos vindo do moodle - inicializado em fill_table()
    private $moodle_students = array();
    // um array com os alunos do moodle que nao estao no cagr - inicializado em fill_ok_table()
    private $not_in_cagr_students = array();

    // se as notas já foram enviadas para o histórico
    private $grades_in_history = null;

    // um array com as contagens de alunos por problema
    private $statistics = array('not_in_cagr' => 0, 'not_in_moodle' => 0, 'ok' => 0,
                                'grade_not_formatted' => 0, 'updated_on_cagr' => 0);

    private $cagr_db = null; // conexao com o sybase
    private $sybase_error = null; // warnings e erros do sybase
    private $send_results = array(); // um array (matricula => msg) com as msgs de erro de envio de notas

    private $show_fi = null; // from CFG, if must show the 'FI' column
    private $cagr_user = null; // CFG->cagr->user

    private $sp_cagr_params; // an array with sp_NotasMoodle params

    private $cannot_submit = false; // if there is something preventing grades sending, set it to true

    private $using_metacourse_grades = false; // if we retrieving grades from metacourse
    private $has_metacourse = false; // if courseid belongs to a metacourse

    private $data_format = "d/m/Y h:i"; // o formato da data mostrada na listagem

    function __construct($courseid, $gpr, $context, $page=null, $force_course_grades) {
        global $CFG, $USER;

        parent::grade_report($courseid, $gpr, $context, $page);

        $this->show_fi = (isset($CFG->grade_report_transposicao_show_fi) &&
                          $CFG->grade_report_transposicao_show_fi == true);

        $this->cagr_user = $CFG->cagr->user;

        if (isset($CFG->grade_report_transposicao_presencial) && $CFG->grade_report_transposicao_presencial == true) {
            $this->sp_cagr_params = array('send' => 11, 'history' => 12, 'logs' => 13, 'submission_range' => 14);
        } else {
            $this->sp_cagr_params = array('send' => 1, 'history' => 2, 'logs' => 3, 'submission_range' => 4);
        }

        $context = get_context_instance(CONTEXT_COURSE, $this->courseid);
        $this->moodle_students = get_role_users(get_field('role', 'id', 'shortname', 'student'), $context, false, '', 'u.firstname, u.lastname');

        $this->get_course_grade_item($force_course_grades);

        if (isset($USER->send_results)) {
            unset($USER->send_results);
        }
    }

    function __destruct() {
        if (!is_null($this->cagr_db)) {
            $this->disconnect_from_cagr();
        }
    }
    
    function initialize_cagr_data() {
        $this->connect_to_cagr();
        $this->get_klass_from_actual_courseid();
        $this->get_submission_date_range();
        $this->is_grades_already_in_history();
        $this->get_cagr_grades();
        return true;
    }

    function setup_table() {

        return ($this->setup_ok_table() &&
                $this->setup_not_in_cagr_table() &&
                $this->setup_not_in_moodle_table());
    }

    private function get_course_grade_item($force_course_grades = false) {
        
        if ($id_course_grade = get_field('course_meta', 'parent_course', 'child_course', $this->courseid)) {
            $this->has_metacourse = true;
            $this->using_metacourse_grades = true;

            if ($force_course_grades) {
                $this->using_metacourse_grades = false;
                $id_course_grade = $this->courseid;
            }
        } else {
            $id_course_grade = $this->courseid;
        }

        $this->course_grade_item_id = get_field('grade_items', 'id', 'itemtype', 'course', 'courseid', $id_course_grade);
    }


    function fill_table() {

        $this->fill_ok_table();
        $this->fill_not_in_moodle_table();
        $this->fill_not_in_cagr_table();
        return true;
    }

    function print_header() {

        $this->msg_grade_send_dates();
        $this->msg_grade_not_formatted();
        $this->msg_grade_already_in_history();
        $this->msg_grade_updated_on_cagr();
        $this->msg_using_metacourse_grades();

        echo "<form method=\"post\" action=\"confirm.php?id={$this->courseid}\">";
    }

    function print_table() {
        ob_start();

        if ($this->statistics['not_in_moodle'] > 0) {
            echo '<h2 class="table_title">',
                 get_string('students_not_in_moodle', 'gradereport_transposicao'),
                 ' - ', $this->statistics['not_in_moodle'], get_string('students', 'gradereport_transposicao'),
                 get_string('wont_be_sent', 'gradereport_transposicao'),
                 '</h2>';
            $this->table_not_in_moodle->print_html();
        }

        if ($this->statistics['not_in_cagr'] > 0) {
            echo '<h2 class="table_title">',
                 get_string('students_not_in_cagr', 'gradereport_transposicao'),
                 ' - ', $this->statistics['not_in_cagr'], get_string('students', 'gradereport_transposicao'),
                 get_string('wont_be_sent', 'gradereport_transposicao'),
                 '</h2>';
            $this->table_not_in_cagr->print_html();
        }

        echo '<h2 class="table_title">', get_string('students_ok', 'gradereport_transposicao');
        if ($this->statistics['ok'] > 0) {
            echo ' - ', $this->statistics['ok'], get_string('students', 'gradereport_transposicao');
        }
        echo '</h2>';
        $this->table_ok->print_html();

        return ob_get_clean();
    }

    function print_footer() {

        echo '<div class="report_footer">';

        $this->msg_grade_send_dates();
        $this->msg_grade_not_formatted();
        $this->msg_grade_already_in_history();
        $this->msg_grade_updated_on_cagr();
        $this->select_overwrite_grades();

        $str_submit_button = get_string('submit_button', 'gradereport_transposicao');
        $dis = ($this->cannot_submit == true) ? 'disabled="disabled"' : '';

        echo '<input type="submit" value="',$str_submit_button , '" ', $dis,' />', '</div></form>';
    }

    function send_grades($grades, $mention, $fi) {
        global $USER;

        $msgs = array();
        foreach ($grades as $matricula => $grade) {

            if (isset($mention[$matricula])) {
                $i = "'I'";
                $grade = 'NULL';
            } else {
                $i = 'NULL';
            }

            if (isset($fi[$matricula])) {
                $f = 'FI';
                if ($grade != 'NULL') $grade = '0';
            } else {
                $f = 'FS';
            }

            if ($grade == '-') {
                $grade = "NULL";
            }

            $sql = "EXEC sp_NotasMoodle {$this->sp_cagr_params['send']},
                    {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}',
                    {$matricula}, {$grade}, {$i}, '{$f}', {$USER->username}";

            $this->cagr_db->Execute($sql);

            $log_info = "matricula: {$matricula}; nota: {$grade}; mencao: {$i}; frequência: {$f}";

            if (!is_null($this->sybase_error)) {
                $this->send_results[$matricula] = utf8_encode($this->sybase_error);
                $log_info .= ' ERRO: '.$this->send_results[$matricula];
            }
            add_to_log($this->courseid, 'grade', 'transposicao', 'send.php', $log_info);
        }
        $this->send_email_with_errors();
        $USER->send_results = $this->send_results;
    }

    function sybase_error_handler($msgnumber, $severity, $state, $line, $text) {
        if ($text == 'ok') {
            $this->sybase_error = null;
        } else {
            $this->sybase_error = $text;
        }
    }

    private function get_moodle_grade($st_id) {
        $g = get_field('grade_grades', 'finalgrade',
                       'itemid', $this->course_grade_item_id,
                       'userid', $st_id);
        if ($g != false) {
            return $this->format_grade($g);
        } else {
            return '-';
        }
    }

    private function fill_ok_table() {

        if (!is_array($this->moodle_students)) {
            return; // nenhum estudante no moodle
        }

        // um primeiro loop para pegar a nota do moodle e verificar se existe alguma fora do padrao ufsc
        foreach ($this->moodle_students as $student) {
            $student->moodle_grade = $this->get_moodle_grade($student->id);
            if (is_numeric($student->moodle_grade)) {
                $decimal_value = explode('.', $student->moodle_grade);
                $decimal_value = $decimal_value[1];
            } else {
                $decimal_value = 0;
            }
            if ( ($student->moodle_grade > 10) || (($decimal_value != 0) && ($decimal_value != 5))) {
                $this->statistics['grade_not_formatted']++;
                $this->cannot_submit = true;
            }
        }

        // agora o loop que preenche a tabela
        foreach ($this->moodle_students as $student) {
            if (isset($this->cagr_grades[$student->username])) {
                // o estudante esta no cagr

                $current_student = $this->cagr_grades[$student->username];

                $this->statistics['ok']++;
                unset($this->cagr_grades[$student->username]);
                
                list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($current_student);
                $has_fi = $current_student['frequencia'] == 'FI';

                $sent_date = '';
                $alert = '';
                $grade_on_cagr_hidden = '';
                $usuario = strtolower($current_student['usuario']);

                if (is_null($current_student['nota'])) {
                    $sent_date = get_string('never_sent', 'gradereport_transposicao');
                } else {

                    $sent_date = date($this->data_format, strtotime($current_student['dataAtualizacao']));

                    if (!$this->is_grades_already_in_history() && $usuario != strtolower($this->cagr_user)) {

                        $this->statistics['updated_on_cagr']++;

                        $alert .= '<p>'.get_string('grade_updated_on_cagr', 'gradereport_transposicao').'</p>';

                        $grade_on_cagr_hidden = '<input type="hidden" name="grades_cagr['.$student->username.'] value="1"/>';
                    }
                }

                $grade_hidden =  '<input type="hidden" name="grades['.$student->username.']" value="'.$student->moodle_grade.'"/>';

                if ($this->grade_differ_on_cagr($has_fi, $student, $grade_in_cagr))  {

                    $grade_in_moodle = '<span class="diff_grade">'.
                                       $student->moodle_grade.$grade_hidden.$grade_on_cagr_hidden.
                                       '</span>';
                    $grade_in_cagr = '<span class="diff_grade">'.$grade_in_cagr.'</span>';
                    $alert = '<p class="diff_grade">'.get_string('warning_diff_grade', 'gradereport_transposicao').'</p>';

                } else {
                    $grade_in_moodle = $student->moodle_grade.$grade_hidden.$grade_on_cagr_hidden;
                }

                // montando a linha da tabela
                $row = array(fullname($student),
                             $grade_in_moodle,
                             $this->get_checkbox_for_mencao_i($student->username, $has_mencao_i, $this->cannot_submit)
                            );

                if ($this->show_fi) {
                    $row[] = $this->get_checkbox_for_fi($student->username, $has_fi, $this->cannot_submit);
                }

                $row = array_merge($row, array($grade_in_cagr, $sent_date, $alert));

                $this->table_ok->add_data($row);
            } else {
                // o aluno nao esta no cagr
                $this->not_in_cagr_students[] = $student;
            }
        }
    }

    private function fill_not_in_moodle_table() {
        // by now, $this->cagr_grade contains just the students that are not in moodle
        // this occurs 'cause this function is called after fill_ok_table()
        foreach  ($this->cagr_grades as $matricula => $student) {
            list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($student);

            $row = array($student['nome'] . ' (' . $matricula . ')',
                         '-', // the moodle grade doesn't exist
                         $this->get_checkbox_for_mencao_i(' ', $has_mencao_i, true));
            
            if ($this->show_fi) {
                $row[] = $this->get_checkbox_for_fi($matricula, $student['frequencia'] == 'FI', true);
            }

            if (is_null($student['nota']) && strtolower($student['usuario']) == 'cagr') {
                $sent_date = get_string('never_sent', 'gradereport_transposicao');
            } else {
                $sent_date = date($this->data_format, strtotime($student['dataAtualizacao']));
            }

            $row = array_merge($row, array($grade_in_cagr, $sent_date, ''));

            $this->table_not_in_moodle->add_data($row);
        }
        $this->statistics['not_in_moodle'] = sizeof($this->cagr_grades);
    }

    private function fill_not_in_cagr_table() {

        $this->statistics['not_in_cagr'] = sizeof($this->not_in_cagr_students);
        foreach ($this->not_in_cagr_students as $student) {
            $has_mencao_i = '';
            $grade_in_cagr = '';
            $sent_date = '';

            $moodle_grade = $this->get_moodle_grade($student->id);

            $row = array(fullname($student),
                         $moodle_grade,
                         $this->get_checkbox_for_mencao_i(' ', $has_mencao_i, true));

            if ($this->show_fi) {
                $row[] = $this->get_checkbox_for_fi($student->username, false, true);
            }
            $row = array_merge($row, array($grade_in_cagr, $sent_date, ''));

            $this->table_not_in_cagr->add_data($row);
        }
    }

    private function connect_to_cagr() {

        if (!$config = get_config('sincronizacao')) {
            print_error('cagr_db_not_set', 'gradereport_transposicao');
        }

        $this->cagr_db = ADONewConnection('sybase');
        $this->cagr_db->charSet = 'cp850';
        sybase_set_message_handler(array($this, 'sybase_error_handler'));
        if(!$this->cagr_db->Connect($config->cagr_host, $config->cagr_user, $config->cagr_pwd, $config->cagr_dbname)) {
            print_error('cagr_connection_error', 'gradereport_transposicao');
        }
    }

    private function disconnect_from_cagr() {
        $this->cagr_db->Disconnect();
    }

    private function get_cagr_grades() {
        
        $sql = "SELECT matricula, nome, nota, mencao, frequencia, usuario, dataAtualizacao
                  FROM vi_moodleEspelhoMatricula
                 WHERE periodo = {$this->klass->periodo}
                   AND disciplina = '{$this->klass->disciplina}'
                   AND turma = '{$this->klass->turma}'";

        $this->cagr_grades = $this->cagr_db->GetAssoc($sql);
    }

    private function is_grades_already_in_history() {

        if (is_null($this->grades_in_history)) {

            $sql = "EXEC sp_NotasMoodle {$this->sp_cagr_params['logs']},
                    {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}'";

            $result = $this->cagr_db->GetArray($sql);

            $found = false;
            if (is_array($result)) {
                foreach ($result as $h)  {
                    if (!is_null($h['dtHistorico'])) {
                        $found = true;
                        $this->cannot_submit = true;
                        break;
                    }
                }
            }
            $this->grades_in_history = $found;
        }
        return $this->grades_in_history;
    }

    private function get_klass_from_actual_courseid() {
        global $CFG;

        if (!property_exists($CFG, 'mid_dbname')) {
            print_error('error_on_middleware_connection', 'gradereport_transposicao');
        }

        $sql = "SELECT disciplina, turma, periodo, modalidade
                  FROM {$CFG->mid_dbname}.ViewTurmasAtivas
                 WHERE idCursoMoodle = {$this->courseid}";

        if (!$this->klass = get_record_sql($sql)) {
            print_error('class_not_in_middleware', 'gradereport_transposicao');
        } else if ($this->klass->modalidade != 'GR') {
            print_error('not_cagr_course', 'gradereport_transposicao');
        }
    }

    private function get_grade_and_mencao_i($st) {

        //inicialmente nao temos mencao i
        $i = false; 
        
        if (!is_null($st['mencao'])) {
            // se o aluno tem mencao I, entao a nota eh zero
            $grade = "I";
            $i = true;
        } else if (is_numeric($st['nota'])) {
            // caso contrario, caso tenha nota no CAGR, ela deve ser mostrada
            $grade = $this->format_grade($st['nota']);
        } else {
            // caso contrario, mostramos um "traço" -
            $grade = '-';
        }
        return array($i, $grade);
    }

    private function format_grade($grade) {
        if (!is_numeric($grade)) {
            return $grade;
        }
        return sprintf("%03.1f", $grade);
    }

    private function send_email_with_errors() {
        if (!empty($this->send_results)) {

            $course_name = get_field('course', 'fullname', 'id', $this->courseid);
            $admin = get_admin();
            $subject = 'Falha na transposicao de notas da disciplina '.$course_name;
            $body = '';

            $names = get_records_select('user', 'username IN ('.implode(',', array_keys($this->send_results)) . ')',
                                        'firstname,lastname', 'username,firstname');

            foreach ($this->send_results as $matricula => $error) {
                $body .= "Matricula: {$matricula}; {$names[$matricula]->firstname} ; Erro: {$error}\n";
            }
            email_to_user($admin, $admin, $subject, $body);
        }
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
                   get_string('moodle_grade', 'gradereport_transposicao'),
                   get_string('mention', 'gradereport_transposicao')
                  );

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
        $c = array('name', 'grade', 'mention',);
        if ($this->show_fi) {
            $c[] = 'fi';
        }
        return array_merge($c, array('cagr_grade', 'sent_date', 'alerts'));
    }

    private function get_checkbox_for_mencao_i($st_username, $has_mencao_i, $disable = false) {
        $dis = $disable ? 'disabled="disabled"' : '';
        $check = $has_mencao_i ? 'checked="checked"' : '';
        return '<input type="checkbox" name="mention['.$st_username.']" '.$check.' value="1" '.$dis.'/>';
    }

    private function get_checkbox_for_fi($st_username, $has_fi, $disable = false) {
        $dis = $disable ? 'disabled="disabled"' : '';
        $check = $has_fi ? 'checked="checked"' : '';
        return '<input type="checkbox" name="fi['.$st_username.']" '.$check.' value="1" '.$dis.'/>';
    }

    private function about_send_dates() {

        $now = time();
        $start_date = explode('/', $this->cagr_submission_date_range->dtInicial);
        $end_date = explode('/', $this->cagr_submission_date_range->dtFinal);

        if (!(strtotime("{$start_date[1]}/{$start_date[0]}/{$start_date[2]} 00:00:00") <= $now) ||
            !($now <= strtotime("{$end_date[1]}/{$end_date[0]}/{$end_date[2]} 23:59:59"))) {
            return 'send_date_not_in_time';
            $this->cannot_submit = true;
        }

        $period = $this->cagr_submission_date_range->periodo;
        if ($this->klass->periodo != $period) {
            return 'send_date_not_in_period';
            $this->cannot_submit = true;
        }

        return 'send_date_ok';
    }

    private function select_overwrite_grades() {

        if ($this->statistics['updated_on_cagr'] > 0) {

            $dis = $this->cannot_submit == true ? 'disable="disable"' : '';
            echo '<p class="overwrite_all">',
                 '<input type="checkbox" id="overwrite_all" name="overwrite_all" value="1"', $dis, '>',
                 '<label for="overwrite_all">',get_string('overwrite_all_grades', 'gradereport_transposicao'), '</label>',
                 '</p>';
        }

    }

    private function get_submission_date_range() {
        $sql = "EXEC sp_NotasMoodle {$this->sp_cagr_params['submission_range']}";
        $date_range = $this->cagr_db->GetArray($sql);
        $date_range = $date_range[0];

        $this->cagr_submission_date_range = new stdclass();
        $this->cagr_submission_date_range->periodo   = $date_range['periodo'];
        $this->cagr_submission_date_range->dtInicial = $date_range['dtInicial'];
        $this->cagr_submission_date_range->dtFinal   = $date_range['dtFinal'];
        
        $p = (string) $date_range['periodo'];// just eye candy 
        $p[5] = $p[4];
        $p[4] = "/";
        $this->cagr_submission_date_range->periodo_with_slash = $p;
    }

    private function msg_grade_not_formatted() {
        if ($this->statistics['grade_not_formatted'] > 0) {
            echo '<p class="warning prevent">',
                 get_string('grades_not_formatted', 'gradereport_transposicao', $this->statistics['grade_not_formatted']),
                 '</p>';
        }
    }

    private function msg_grade_already_in_history() {
        if ($this->is_grades_already_in_history()) {
            echo '<p class="warning prevent">',
                 get_string('grades_already_in_history', 'gradereport_transposicao'),
                 '</p>';
        }
    }

    private function msg_grade_updated_on_cagr() {
        if ($this->statistics['updated_on_cagr'] > 0) {
            echo '<p class="warning">',
                 get_string('grades_updated_on_cagr', 'gradereport_transposicao'),
                 '</p>';
        }
    }

    private function msg_grade_send_dates() {
        $about  = $this->about_send_dates();
        $class = '';
        if ($about != 'send_date_ok') {
            $class = ' warning prevent';
            $this->cannot_submit = true;
        }
        echo '<p class="grade_range', $class, '">',
             get_string($about, 'gradereport_transposicao', $this->cagr_submission_date_range),
             '</p>';
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

    private function grade_differ_on_cagr($has_fi, $student, $grade_in_cagr) {
        return ($grade_in_cagr != '-') &&
               (($student->moodle_grade != '-') && !$has_fi) &&
               (($student->moodle_grade != $grade_in_cagr) && !$has_fi);
    }
}
?>
