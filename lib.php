<?php
require($CFG->dirroot.'/grade/report/lib.php');
require($CFG->libdir.'/tablelib.php');
require($CFG->dirroot.'/grade/report/transposicao/sybase.php');

/**
 *
 * primeiro parametro da stored procedure sp_NotasMoodle
 * 1 - inclui
 * 2 - busca notas (deprecated - substituido pela vi_moodleEspelhoMatricula)
 * 3 - busca logs
 * 4 - busca período de digitacao de notas EAD
 */

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

    function grade_report_transposicao($courseid, $gpr, $context, $page=null) {
        global $CFG;

        parent::grade_report($courseid, $gpr, $context, $page);

        $this->show_fi = (isset($CFG->transposicao_show_fi) && $CFG->transposicao_show_fi == true);
        $this->cagr_user = $CFG->cagr->user;
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

    function print_header() {

        echo '<p class="grade_range',
             $this->is_in_time_to_send_grades() ? '' : ' warning prevent' , // add clases if not in submission range
             '">',
             get_string('submission_date_range', 'gradereport_transposicao', $this->cagr_submission_date_range),
             $this->is_in_time_to_send_grades() ? '' : get_string('prevent_grade_sent', 'gradereport_transposicao'),
             '</p>';

        if ($this->statistics['grade_not_formatted'] > 0) {
            echo '<p class="warning prevent">',
                 get_string('grades_not_formatted', 'gradereport_transposicao', $this->statistics['grade_not_formatted']),
                 '</p>';
        }

        if ($this->is_grades_already_in_history()) {
            echo '<p class="warning prevent">',
                 get_string('grades_already_in_history', 'gradereport_transposicao'),
                 '</p>';
        }

        echo '<p><a href="#not_in_moodle">',
              get_string('students_not_in_moodle', 'gradereport_transposicao'),
             '</a> ', get_string('wont_be_sent', 'gradereport_transposicao'), '</p>',
             '<p><a href="#not_in_cagr">',
             get_string('students_not_in_cagr', 'gradereport_transposicao'),
             '</a> ', get_string('wont_be_sent', 'gradereport_transposicao'),'</p>',
             '<p><a href="#ok">',
             get_string('students_ok', 'gradereport_transposicao'),
             '</a> ', get_string('will_be_sent', 'gradereport_transposicao'),'</p>';


       echo "<form method=\"post\" action=\"confirm.php?id={$this->courseid}\">";
    }

    function print_footer() {

        $disable_submission = '';
        $in_time_to_send = true;
        $str_submit_button = get_string('submit_button', 'gradereport_transposicao');

        if (!$this->is_in_time_to_send_grades()) {
            $disable_submission = 'disabled="disabled"';
            $in_time_to_send = false;
            $str_not_in_time_to_send = " ". get_string('submission_date_range',
                                                        'gradereport_transposicao',
                                                        $this->cagr_submission_date_range) .
                                       " ". get_string('prevent_grade_sent', 'gradereport_transposicao');
        }

        if (($this->statistics['grade_not_formatted'] > 0) ||
            $this->is_grades_already_in_history()) {
            $disable_submission = 'disabled="disabled"';
        }


        echo '<div class="report_footer">';

        if ($this->is_grades_already_in_history()) {
            echo '<p class="warning prevent">',
                 get_string('grades_already_in_history', 'gradereport_transposicao'),
                 '</p>';
        }

        if (!$in_time_to_send) {
            echo '<p class="warning prevent">', $str_not_in_time_to_send , '</p>';
        }

        $this->print_update_grades_selection();

        //echo '<input type="submit" value="',$str_submit_button , '" ', $disable_submission,' />', '</div></form>';
        echo '<input type="submit" value="',$str_submit_button , '" ', ' />', '</div></form>';
    }

    private function is_in_time_to_send_grades() {
        $now = time();
        $start_date = explode('/', $this->cagr_submission_date_range->dtInicial);
        $end_date = explode('/', $this->cagr_submission_date_range->dtFinal);
        $period = $this->cagr_submission_date_range->periodo;

        return ($this->klass->periodo == $period) &&
               (strtotime("{$start_date[1]}/{$start_date[0]}/{$start_date[2]}") <= $now) &&
               ($now <= strtotime("{$end_date[1]}/{$end_date[0]}/{$end_date[2]} 23:59:59"));
    }

    private function print_update_grades_selection() {

        echo '<p class="overwrite_all">',
             '<input type="checkbox" id="overwrite_all" name="overwrite_all" value="1">',
             '<label for="overwrite_all">',get_string('overwrite_all_grades', 'gradereport_transposicao'), '</label>',
             '</p>';
    }

    function setup_table() {

        $this->setup_ok_table();
        $this->setup_not_in_cagr_table();
        $this->setup_not_in_moodle_table();

        $context = get_context_instance(CONTEXT_COURSE, $this->courseid);
        $this->moodle_students = get_role_users(get_field('role', 'id', 'shortname', 'student'), $context, false, '', 'u.firstname, u.lastname');

        // Get course grade_item
        $this->course_grade_item_id = get_field('grade_items', 'id', 'itemtype', 'course', 'courseid', $this->courseid);

        return true;
    }


    function fill_table() {

        $this->fill_ok_table();
        $this->fill_not_in_moodle_table();
        $this->fill_not_in_cagr_table();
        return true;
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

        foreach ($this->moodle_students as $student) {

            if (isset($this->cagr_grades[$student->username])) {
                // o estudante esta no cagr

                $current_student = $this->cagr_grades[$student->username];

                $this->statistics['ok']++;
                unset($this->cagr_grades[$student->username]);
                
                list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($current_student);

                $moodle_grade = $this->get_moodle_grade($student->id);
                if (is_numeric($moodle_grade)) {
                    $decimal_value = explode('.', $moodle_grade);
                    $decimal_value = $decimal_value[1];
                } else {
                    $decimal_value = 0;
                }

                // verifica se a nota estah no padrao ufsc
                if ( ($moodle_grade > 10) || (($decimal_value != 0) && ($decimal_value != 5))) {
                    $this->statistics['grade_not_formatted']++;
                }

                $usuario = strtolower($current_student->usuario);

                $grade_updated_on_cagr = '';
                $grade_on_cagr_hidden = '';
                if (($usuario != strtolower($this->cagr_user)) &&
                    $usuario != 'cagr') {

                    $this->statistics['updated_on_cagr']++;

                    $grade_updated_on_cagr = get_string('grade_updated_on_cagr', 'gradereport_transposicao');

                    $grade_on_cagr_hidden = '<input type="hidden" name="grades_cagr['.$student->username.'] value="1"/>';
                }

                if (is_null($current_student->nota) && $cagr_user == 'cagr') {
                    $sent_date = get_string('never_sent', 'gradereport_transposicao');
                } else {
                    $sent_date = $current_student->dataAtualizacao;
                }

                $grade_hidden =  '<input type="hidden" name="grades['.$student->username.']" value="'.$moodle_grade.'"/>';

                // montando a linha da tabela
                $row = array(fullname($student),
                             $moodle_grade.  $grade_hidden . $grade_on_cagr_hidden,
                             $this->get_checkbox_for_mencao_i($student->username, $has_mencao_i)
                            );

                if ($this->show_fi) {
                    $row[] = $this->get_checkbox_for_fi($student->username, $current_student->frequencia == 'FI');
                }

                $row = array_merge($row, array($grade_in_cagr, $sent_date, $grade_updated_on_cagr));

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
        foreach  ($this->cagr_grades as $student) {
            list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($student);

            $row = array($student->nome,
                         '-', // the moodle grade doesn't exist
                         $this->get_checkbox_for_mencao_i(' ', $has_mencao_i, true));
            
            if ($this->show_fi) {
                $row[] = $this->get_checkbox_for_fi($student->matricula, $student->frequencia == 'FI', true);
            }

            if (is_null($student->nota) && strtolower($student->usuario) == 'cagr') {
                $sent_date = get_string('never_sent', 'gradereport_transposicao');
            } else {
                $sent_date = $student->dataAtualizacao;
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

    function print_table() {
        ob_start();

        echo '<h2 class="table_title"><a name="not_in_moodle"></a>', get_string('students_not_in_moodle', 'gradereport_transposicao');
        if ($this->statistics['not_in_moodle'] > 0) {
           echo ' - ', $this->statistics['not_in_moodle'], get_string('students', 'gradereport_transposicao');
        }
        echo '</h2>';
        $this->table_not_in_moodle->print_html();

        echo '<h2 class="table_title"><a name="not_in_cagr"></a>', get_string('students_not_in_cagr', 'gradereport_transposicao');
        if ($this->statistics['not_in_cagr'] > 0) {
            echo ' - ', $this->statistics['not_in_cagr'], get_string('students', 'gradereport_transposicao');
        }
        echo '</h2>';
        $this->table_not_in_cagr->print_html();

        echo '<h2 class="table_title"><a name="ok"></a>', get_string('students_ok', 'gradereport_transposicao');
        if ($this->statistics['ok'] > 0) {
            echo ' - ', $this->statistics['ok'], get_string('students', 'gradereport_transposicao');
        }
        echo '</h2>';
        $this->table_ok->print_html();

        return ob_get_clean();
    }

    private function get_submission_date_range() {
        $this->cagr_db->query("EXEC sp_NotasMoodle 4");
        $this->cagr_submission_date_range = $this->cagr_db->result[0];
    }

    function send_grades($grades, $mention, $fi) {
        global $USER;

        $msgs = array();
        foreach ($grades as $matricula => $grade) {

            if (isset($mention[$matricula])) {
                $i = "'I'";
                $grade = "NULL";
            } else {
                $i = "NULL";
            }

            if (isset($fi[$matricula])) {
                $grade = 0;
                $f = 'FI';
            } else {
                $f = 'FS';
            }

            if ($grade == '-') {
                $grade = "NULL";
            }

            $sql = "EXEC sp_NotasMoodle 1,
                    {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}',
                    {$matricula}, {$grade}, {$i}, {$f}, {$USER->username}";

            //echo $sql, "<hr/>";
            $this->cagr_db->query($sql);

            if (!is_null($this->sybase_error)) {
                $this->send_results[$matricula] = $this->sybase_error;
            }
        }
    }

    function sybase_error_handler($msgnumber, $severity, $state, $line, $text) {
        if ($text == 'ok') {
            $this->sybase_error = null;
        } else {
            $this->sybase_error = $text;
        }
    }

    function print_send_results() {
        if (empty($this->send_results)) {
            echo '<p>', get_string('all_grades_was_sent', 'gradereport_transposicao'), '</p>';
        } else {
            echo '<p>', get_string('some_grades_not_sent', 'gradereport_transposicao'), '</p>',
                 '<ul class="send_results">';
            foreach ($this->send_results as $matricula => $msg) {
                echo '<li>', $matricula, ': ', $msg, '</li>'; 
            }
            echo '</ul>';
        }
    }

    private function connect_to_cagr() {
        global $CFG;

        if (!isset($CFG->cagr)) {
            print_error(get_string('cagr_db_not_set', 'gradereport_transposicao'));
        }

        try {
            sybase_set_message_handler(array($this, 'sybase_error_handler'));
            $this->cagr_db = new TSYBASE($CFG->cagr->host, $CFG->cagr->base, $CFG->cagr->user,$CFG->cagr->pass);
        } catch (ExceptionDB $e) {
            print_error(get_string('cagr_db_not_set', 'gradereport_transposicao'));
        }
    }

    private function disconnect_from_cagr() {
        $this->cagr_db->close();
    }

    private function get_cagr_grades() {
        
        $sql = "SELECT *
                  FROM vi_moodleEspelhoMatricula
                 WHERE periodo = {$this->klass->periodo}
                   AND disciplina = '{$this->klass->disciplina}'
                   AND turma = '{$this->klass->turma}'";

        $this->cagr_db->query($sql, 'matricula');
        $this->cagr_grades = $this->cagr_db->result;
    }

    private function is_grades_already_in_history() {
        global $sybase_error;

        if (is_null($this->grades_in_history)) {

            $sql = "EXEC sp_NotasMoodle 3, {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}'";

            $this->cagr_db->query($sql);

            $found = false;
            if (is_array($this->cagr_db->result)) {
                foreach ($this->cagr_db->result as $log)  {
                    if (is_string($log->dtHistorico)) {
                        $found = true;
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

        if (!isset($CFG->mid) || !isset($CFG->mid->base)) {
            print_error('Erro ao conectar ao middleware');
        }

        $sql = "SELECT disciplina, turma, periodo, modalidade
                  FROM {$CFG->mid->base}.ViewTurmasAtivas
                 WHERE idCursoMoodle = {$this->courseid}";

        if (!$this->klass = get_record_sql($sql)) {
            print_error(get_string('class_not_in_middleware', 'gradereport_transposicao'));
        } else if ($this->klass->modalidade != 'GR') {
            print_error(get_string('not_cagr_course', 'gradereport_transposicao'));
        }
    }

    private function get_grade_and_mencao_i($st) {

        //inicialmente nao temos mencao i
        $i = false; 
        
        if (!is_null($st->mencao)) {
            // se o aluno tem mencao I, entao a nota eh zero
            $grade = "I";
            $i = true;
        } else if (is_numeric($st->nota)) {
            // caso contrario, caso tenha nota no CAGR, ela deve ser mostrada
            $grade = $this->format_grade($st->nota);
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
    }

    private function get_table_headers() {

        $h = array(get_string('name'),
                   get_string('grade'),
                   get_string('mention', 'gradereport_transposicao')
                  );

        if ($this->show_fi) {
            $h[] = get_string('fi', 'gradereport_transposicao');
        }

        return array_merge($h, array(get_string('cagr_grade', 'gradereport_transposicao'),
                                     get_string('sent_date', 'gradereport_transposicao'),
                                     get_string('alerts', 'gradereport_transposicao'),
                                    ));
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
}
?>
