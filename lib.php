<?php
require_once($CFG->dirroot.'/grade/report/lib.php');
require_once($CFG->libdir.'/tablelib.php');
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

    private $submission_date_range; // intervalo de envio de notas
    private $klass; // um registro com disciplina, turma e periodo, vindo do middleware
    private $cagr_grades; // um array com as notas vindas no CAGR
    private $moodle_students = array(); // um array com os alunos vindo do moodle - inicializado em fill_table()
    private $not_in_cagr_students = array(); // um array com os alunos do moodle que nao estao no cagr - inicializado em fill_ok_table()
    
    private $statistics; // um array com as contagens de alunos por problema

    function grade_report_transposicao($courseid, $gpr, $context, $page=null) {
        global $CFG;

        parent::grade_report($courseid, $gpr, $context, $page);

        $this->students_final_grades = array();

        $this->cagr_grades = array();
        $this->cagr_submission_date_range = null;
        $this->cagr_grades_already_on_history = null; 
        $this->sybase_error = null; 

        $this->statistics['not_in_cagr'] = 0;
        $this->statistics['not_in_moodle'] = 0;
        $this->statistics['ok'] = 0;
        $this->statistics['grade_not_formatted'] = 0;
        $this->statistics['updated_on_cagr'] = 0;

        $this->show_fi = (isset($CFG->transposicao_show_fi) && $CFG->transposicao_show_fi == true);
        $this->cagr_user = $CFG->cagr->user;
    }
    
    function initialize_cagr_data() {
        $this->connect_to_cagr();
        $this->get_klass_from_actual_courseid();
        $this->get_submission_date_range();
        $this->get_cagr_grades();
        $this->disconnect_from_cagr();
        return true;
    }


    function print_header() {
        echo '<h2 class="main">',
             get_string('submission_date_range', 'gradereport_transposicao', $this->submission_date_range),
             '</h2>';

        if ($this->statistics['grade_not_formatted'] > 0) {
            echo '<p class="warning">',
                 get_string('grades_not_formatted', 'gradereport_transposicao', $this->statistics['grade_not_formatted']),
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


       echo '<form method="post" action="confirm.php">';
    }

    function print_footer() {


        $disable_submission = '';
        if ($this->statistics['grade_not_formatted'] > 0) {
            $disable_submission = 'disabled="disabled"';
        }

        if ($this->statistics['updated_on_cagr'] > 0) {
            $this->print_update_grades_selection();
        }

        echo '<div><input type="submit" value="', get_string('submit_button', 'gradereport_transposicao'), '" ',
             $disable_submission,' /></div>',
             '</form>';
    }

    private function print_update_grades_selection() {

        echo '<div class="must_update">',
             '<input type="checkbox" name="must_update" value="1">',
             get_string('must_update_grades', 'gradereport_transposicao'),
             '</div>';
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
        $mg = get_field('grade_grades', 'finalgrade',
                         'itemid', $this->course_grade_item_id,
                         'userid', $st_id);
        return $mg ? $mg : '-';
    }


    private function fill_ok_table() {

        foreach ($this->moodle_students as $student) {

            if (isset($this->cagr_grades[$student->username])) {
                // o estudante esta no cagr

                $current_student = $this->cagr_grades[$student->username];

                $this->statistics['ok']++;
                unset($this->cagr_grades[$student->username]);
                
                list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($current_student);

                // ultima data em que a nota foi enviada ao cagr
                $sent_date = $current_student->dataAtualizacao;

                $moodle_grade = $this->get_moodle_grade($student->id);
                $float_value = floatval($moodle_grade);

                // verifica se a nota estah no padrao ufsc
                if ( ($moodle_grade > 10) || (($float_value != 0) && ($float_value != 5))) {
                    $this->statistics['grade_not_formatted']++;
                }

                if (strtolower($current_student->usuario) != strtolower($this->cagr_user)) {
                    $this->statistics['updated_on_cagr']++;
                    $grade_updated_on_cagr = get_string('grade_updated_on_cagr', 'gradereport_transposicao');
                } else {
                    $grade_updated_on_cagr = '';
                }

                $moodle_grade = number_format($moodle_grade, 1);

                // montando a linha da tabela
                $row = array(fullname($student),
                             $moodle_grade . 
                             '<input type="hidden" name="grade['.$student->username.']" value="'.$moodle_grade.'"/>',
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
                         '', // the moodle grade doesn't exist
                         $this->get_checkbox_for_mencao_i(' ', $has_mencao_i, true));
            
            if ($this->show_fi) {
                $row[] = $this->get_checkbox_for_fi($student->matricula, $student->frequencia == 'FI', true);
            }

            $row = array_merge($row, array($grade_in_cagr, $student->dataAtualizacao, ''));

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

            $moodle_grade = number_format($this->get_moodle_grade($student->id), 1);

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

        echo '<h2 class="main"><a name="not_in_moodle"></a>', get_string('students_not_in_moodle', 'gradereport_transposicao');
        if ($this->statistics['not_in_moodle'] > 0) {
           echo ' (', $this->statistics['not_in_moodle'], get_string('students', 'gradereport_transposicao'), ')';
        }
        echo '</h2>';
        $this->table_not_in_moodle->print_html();

        echo '<h2 class="main"><a name="not_in_cagr"></a>', get_string('students_not_in_cagr', 'gradereport_transposicao');
        if ($this->statistics['not_in_cagr'] > 0) {
            echo '(', $this->statistics['not_in_cagr'], get_string('students', 'gradereport_transposicao'), ')';
        }
        echo '</h2>';
        $this->table_not_in_cagr->print_html();

        echo '<h2 class="main"><a name="ok"></a>', get_string('students_ok', 'gradereport_transposicao');
        if ($this->statistics['ok'] > 0) {
            echo ' (', $this->statistics['ok'], get_string('students', 'gradereport_transposicao'), ')';
        }
        echo '</h2>';
        $this->table_ok->print_html();

        return ob_get_clean();
    }

    function get_submission_date_range() {
        $this->cagr_db->query("EXEC sp_NotasMoodle 4");
        $this->submission_date_range = $this->cagr_db->result[0];
    }

    function save_grades($students, $mencao, $course) {
        global $USER;

        $msgs = array();
        foreach ($students as $matricula => $grade) {
            if (isset($mencao[$matricula])) {
                $i = "'I'"; $grade = "NULL";
            } else {
                $i = "NULL";
            }
            $sql = "EXEC sp_NotasMoodle 1, {$course->periodo}, '{$course->disciplina}', '{$course->turma}',
            {$matricula}, {$grade}, {$i}, 'FS', {$USER['username']}";
            $this->cagr_db->query($sql);
            if (!is_null($this->sybase_error)) {
                $msgs[$matricula] = $sybase_error;
            }
        }
        return $msgs;
    }

    function sybase_error_handler($msgnumber, $severity, $state, $line, $text) {
        if ($text == 'ok') {
            $this->sybase_error = null;
        } else {
            $this->sybase_error = $text;
            if ($severity > 10) {
                echo $msgnumber, '. severity: ', $severity, '. state: ', $state, '. line: ', $line, '. text: ', $text;
            }
        }
    }

    private function connect_to_cagr() {
        global $CFG;

        if (!isset($CFG->cagr)) {
            error(get_string('cagr_db_not_set', 'gradereport_transposicao'));
        }

        try {
            sybase_set_message_handler(array($this, 'sybase_error_handler'));
            $this->cagr_db = new TSYBASE($CFG->cagr->host, $CFG->cagr->base, $CFG->cagr->user,$CFG->cagr->pass);
        } catch (ExceptionDB $e) {
            error(get_string('cagr_db_not_set', 'gradereport_transposicao'));
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

    private function search_grades_in_history() {
        global $sybase_error;

        $sql = "EXEC sp_NotasMoodle 3, {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}'";

        $this->cagr_db->query($sql);

        if (is_array($this->cagr_db->result)) {
            foreach ($this->db_cagr->result as $log)  {
                if (is_string($log->dtHistorico)) {
                    $this->cagr_grades_already_on_history = true;
                    return;
                }
            }
        }
        $this->cagr_grades_already_on_history = false;
    }

    private function get_klass_from_actual_courseid() {
        global $CFG;

        if (!isset($CFG->mid) || !isset($CFG->mid->base)) {
            error('Erro ao conectar ao middleware');
        }

        $sql = "SELECT disciplina,turma,periodo
                  FROM {$CFG->mid->base}.Turmas
                 WHERE idCursoMoodle = {$this->courseid}";

        if(!$this->klass = get_record_sql($sql)) {
            error('Klass not in middleware');
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
            $grade = $st->nota;
        } else {
            // caso contrario, mostramos um "traço" -
            $grade = '-';
        }
        return array($i, $grade);
    }

    private function setup_not_in_cagr_table() {

        $this->table_not_in_cagr = new flexible_table('gradereport-transposicao-not_in_cagr');
        $this->table_not_in_cagr->define_columns($this->get_table_columns());
        $this->table_not_in_cagr->define_headers($this->get_table_headers());
        $this->table_not_in_cagr->define_baseurl($this->baseurl);

        $this->table_not_in_cagr->set_attribute('cellspacing', '0');
        $this->table_not_in_cagr->set_attribute('id', 'gradereport-transposicao-not_in_cagr');
        $this->table_not_in_cagr->set_attribute('class', 'boxaligncenter generaltable');

        $this->table_not_in_cagr->setup();
    }

    private function setup_not_in_moodle_table() {

        $this->table_not_in_moodle = new flexible_table('gradereport-transposicao-not_in_moodle');
        $this->table_not_in_moodle->define_columns($this->get_table_columns());
        $this->table_not_in_moodle->define_headers($this->get_table_headers());
        $this->table_not_in_moodle->define_baseurl($this->baseurl);

        $this->table_not_in_moodle->set_attribute('cellspacing', '0');
        $this->table_not_in_moodle->set_attribute('id', 'gradereport-transposicao-not_in_moodle');
        $this->table_not_in_moodle->set_attribute('class', 'boxaligncenter generaltable');

        $this->table_not_in_moodle->setup();
    }

    private function setup_ok_table() {

        $this->table_ok = new flexible_table('gradereport-transposicao-ok');
        $this->table_ok->define_columns($this->get_table_columns());
        $this->table_ok->define_headers($this->get_table_headers());
        $this->table_ok->define_baseurl($this->baseurl);

        $this->table_ok->set_attribute('cellspacing', '0');
        $this->table_ok->set_attribute('id', 'gradereport-transposicao-ok');
        $this->table_ok->set_attribute('class', 'boxaligncenter generaltable');

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
