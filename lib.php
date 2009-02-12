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
 //       $this->get_cagr_grades();
 //       $this->get_cagr_submission_date_range();
        $this->disconnect_from_cagr();
        return true;
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

            $this->table->add_data(array(fullname($student), $finalgrade,
                                         '<input type="checkbox" name="mention['.$student->id.']" value="1">',
                                          '', $this->cagr_grades[$student->id]));
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
        }
    }

    private function connect_to_cagr() {
        global $CFG;

        if (!isset($CFG->cagr)) {
            error(get_string('cagr_db_not_set', 'gradereport_transposicao'));
        }

        try {
            $this->cagr_db = new TSYBASE($CFG->cagr->host, $CFG->cagr->base, $CFG->cagr->user,$CFG->cagr->pass, 'CP850');
            sybase_set_message_handler(array($this, 'sybase_error_handler'));
        } catch (ExceptionDB $e) {
            error(get_string('cagr_db_not_set', 'gradereport_transposicao'));
        }
    }

    private function disconnect_from_cagr() {
        $this->cagr_db->close();
    }

    private function get_cagr_grades() {
        
        $turma = $this->get_klass_from_actual_courseid();

        $sql = "SELECT *
                  FROM vi_moodleEspelhoMatricula
                 WHERE periodo = '{$turma->periodo}'
                   AND disciplina = '{$turma->disciplina}'
                   AND turma = '{$turma->turma}'";

        $this->cagr_bd->query($sql, 'matricula');

        $this->cagr_grades = $this->cagr_bd->result;
    }

    private function get_cagr_submission_date_range() {

        $this->cagr_db->query("EXEC sp_NotasMoodle 4");
        $this->cagr_submission_date_range = $this->cagr_db->result[0];
    }

    private function search_grades_in_history($disciplina, $turma, $periodo) {
        global $sybase_error;

        $sql = "EXEC sp_NotasMoodle 3, {$periodo}, '{$disciplina}', '{$turma}'";

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
    }
}
?>
