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
        parent::grade_report($courseid, $gpr, $context, $page);

        $this->students_final_grades = array();

        $this->cagr_grades = array();
        $this->cagr_submission_date_range = null;
        $this->cagr_grades_already_on_history = null; 
        $this->sybase_error = null; 

        $this->statistics['not_in_cagr'] = 0;
        $this->statistics['not_in_moodle'] = 0;
        $this->statistics['ok'] = 0;
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
        print_heading(get_string('submission_date_range', 'gradereport_transposicao', $this->submission_date_range));
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
        return get_field('grade_grades', 'finalgrade',
                         'itemid', $this->course_grade_item_id,
                         'userid', $st_id);
    }


    private function fill_ok_table() {

        foreach ($this->moodle_students as $student) {

            if ($current_student = $this->cagr_grades[$student->username]) { // o estudante esta no cagr

                $this->statistics['ok']++;
                unset($this->cagr_grades[$student->username]);
                
                list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($current_student);

                // ultima data em que a nota foi enviada ao cagr
                $sent_date = $current_student->dataAtualizacao;
                
                // montando a linha da tabela
                $row = array(fullname($student),
                             $this->get_moodle_grade($student->id),
                             $this->get_checkbox_for_mencao_i($student->id, $has_mencao_i),
                             $grade_in_cagr . 
                             '<input type="hidden" name="grade['.$student->username.']" value="'.$grade_in_cagr.'">',
                             $sent_date,
                            );

                $this->table_ok->add_data($row);
            } else {
                $this->not_in_cagr_students[] = $student;
            }
        }
    }

    private function get_checkbox_for_mencao_i($st_id, $has_mencao_i, $disable = false) {
        $dis = $disable ? 'disable' : 'disabled="false"';
        return '<input type="checkbox" name="mention['.$st_id.']" '.$has_mencao_i.' value="1" '.$disable.'>';
    }

    private function fill_not_in_moodle_table() {
        // by now, $this->cagr_grade contains just the students that are not in moodle
        // this occurs 'cause this function is called after fill_ok_table()
        foreach  ($this->cagr_grades as $student) {
            list($has_mencao_i, $grade_in_cagr) = $this->get_grade_and_mencao_i($student);

            $row = array($student->nome,
                         '', // the moodle grade doesn't exist
                         $this->get_checkbox_for_mencao_i(' ', $has_mencao_i, true), // pass a blank id
                         $grade_in_cagr,
                         $student->dataAtualizacao);

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

                $row = array(fullname($student),
                             $this->get_moodle_grade($student->id),
                             $this->get_checkbox_for_mencao_i(' ', $has_mencao_i, true), // pass a blank id
                             $grade_in_cagr . 
                             '<input type="hidden" name="grade['.$student->username.']" value="'.$grade_in_cagr.'">',
                             $sent_date,
                            );
                $this->table_not_in_cagr->add_data($row);
        }
    }

    function print_table() {
        ob_start();
        print_heading(get_string('not_in_moodle_table', 'gradereport_transposicao') . 
                      " ({$this->statistics['not_in_moodle']})", 'left');
        $this->table_not_in_moodle->print_html();

        print_heading(get_string('not_in_cagr_table', 'gradereport_transposicao') .
                      " ({$this->statistics['not_in_cagr']})", 'left');
        $this->table_not_in_cagr->print_html();

        print_heading(get_string('ok_table', 'gradereport_transposicao') .
                      " ({$this->statistics['ok']})", 'left');
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
        $i = '';
        
        if (!is_null($st->mencao)) {
            // se o aluno tem mencao I, entao a nota eh zero
            $grade = "I";
            $i = 'checked';
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

        $this->table_not_in_cagr = new flexible_table('grade-report-transposicao-not_in_cagr');
        $this->table_not_in_cagr->define_columns($this->get_table_columns());
        $this->table_not_in_cagr->define_headers($this->get_table_headers());
        $this->table_not_in_cagr->define_baseurl($this->baseurl);

        $this->table_not_in_cagr->set_attribute('cellspacing', '0');
        $this->table_not_in_cagr->set_attribute('id', 'transposition-grade');
        $this->table_not_in_cagr->set_attribute('class', 'boxaligncenter generaltable');

        $this->table_not_in_cagr->setup();
    }

    private function setup_not_in_moodle_table() {

        $this->table_not_in_moodle = new flexible_table('grade-report-transposicao-not_in_moodle');
        $this->table_not_in_moodle->define_columns($this->get_table_columns());
        $this->table_not_in_moodle->define_headers($this->get_table_headers());
        $this->table_not_in_moodle->define_baseurl($this->baseurl);

        $this->table_not_in_moodle->set_attribute('cellspacing', '0');
        $this->table_not_in_moodle->set_attribute('id', 'transposition-grade');
        $this->table_not_in_moodle->set_attribute('class', 'boxaligncenter generaltable');

        $this->table_not_in_moodle->setup();
    }

    private function setup_ok_table() {

        $this->table_ok = new flexible_table('grade-report-transposicao-ok');
        $this->table_ok->define_columns($this->get_table_columns());
        $this->table_ok->define_headers($this->get_table_headers());
        $this->table_ok->define_baseurl($this->baseurl);

        $this->table_ok->set_attribute('cellspacing', '0');
        $this->table_ok->set_attribute('id', 'transposition-grade');
        $this->table_ok->set_attribute('class', 'boxaligncenter generaltable');

        $this->table_ok->setup();
    }

    private function get_table_headers() {
        return array($this->get_lang_string('name'),
                     $this->get_lang_string('grade'),
                     $this->get_lang_string('mention', 'gradereport_transposicao'),
                     $this->get_lang_string('cagr_grade', 'gradereport_transposicao'),
                     $this->get_lang_string('sent_date', 'gradereport_transposicao'),
                    );
    }

    private function get_table_columns() {
        return array('name', 'grade', 'mention', 'cagr_grade', 'sent_date');
    }
}
?>
