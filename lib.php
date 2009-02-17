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

    function grade_report_transposicao($courseid, $gpr, $context, $page=null) {
        parent::grade_report($courseid, $gpr, $context, $page);

        $this->students_final_grades = array();

        $this->cagr_grades = array();
        $this->cagr_submission_date_range = null;
        $this->cagr_grades_already_on_history = null; 
        $this->sybase_error = null; 
    }
    
    function initialize_cagr_data() {
        $this->connect_to_cagr();
        $this->get_klass_from_actual_courseid();
        $this->get_submission_date_range();
        $this->get_cagr_grades();
        $this->disconnect_from_cagr();
        return true;
    }

    function get_submission_date_range() {
        $this->cagr_db->query("EXEC sp_NotasMoodle 4");
        $this->submission_date_range = $this->cagr_db->result[0];
    }

    function print_header() {
        echo get_string('submission_date_range', 'gradereport_transposicao',
                        $this->submission_date_range);
    }

    function setup_table() {
        $tablecolumns = array('fullname', 'grade', 'mention', 'cagr_grade');
        $tableheaders = array($this->get_lang_string('fullname'),
                              $this->get_lang_string('grade'),
                              $this->get_lang_string('mention', 'gradereport_transposicao'),
                              $this->get_lang_string('cagr_grade', 'gradereport_transposicao'));

        $this->table = new flexible_table('grade-report-transposicao');
        $this->table->define_columns($tablecolumns);
        $this->table->define_headers($tableheaders);
        $this->table->define_baseurl($this->baseurl);

        $this->table->set_attribute('cellspacing', '0');
        $this->table->set_attribute('id', 'transposition-grade');
        $this->table->set_attribute('class', 'boxaligncenter generaltable');

        $this->table->setup();
        return true;
    }

    function fill_table() {

        $course = get_record('course', 'id', $this->courseid);
        // Get the course final grade category
        $final_grade_item = get_record('grade_items', 'itemtype', 'course',
                                       'courseid', $this->courseid,
                                       '', '', 'id');

        $students = get_course_students($this->courseid);
        foreach ($students as $student) {
            // Get course grade_item
            $grade_item_id = get_field('grade_items', 'id', 'itemtype', 'course', 'courseid', $this->courseid);

            // Get the grade
            $finalgrade = get_field('grade_grades', 'finalgrade', 'itemid', $final_grade_item->id, 'userid', $student->id);

            $grade_in_cagr = '-';
            $current_student = $this->cagr_grades[$student->username];
            if (is_numeric($current_student->nota)) {
                $grade_in_cagr = $current_student->nota;
            }
            $this->table->add_data(array(fullname($student), $finalgrade,
                                         '<input type="checkbox" name="mention['.$student->id.']" value="1">',
                                          $grade_in_cagr));
            $this->students_final_grades[$student->id] = $finalgrade;
        }
        return true;
    }

    function print_table() {
        ob_start();
        $this->table->print_html();
        return ob_get_clean();
    }

    function include_grades_as_hidden_fields() {
        ob_start();
        foreach ($this->students_final_grades as $st_id => $grade ) {
            echo '<input type="hidden" name="grade['.$st_id.']" value="'.$grade.'">';
        }
        return ob_get_clean();
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
}
?>
