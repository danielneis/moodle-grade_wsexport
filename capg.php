<?php

class TransposicaoCAPG {

    private $valid_display_types = array(GRADE_DISPLAY_TYPE_LETTER, GRADE_DISPLAY_TYPE_REAL_LETTER,
                                         GRADE_DISPLAY_TYPE_LETTER_REAL, GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE,
                                         GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER);

    function __construct() {
    }

    function get_submission_date_range() {
        return (object) array('periodo' => 20101,
                              'dtFinal' => '05/05/2010',
                              'dtInicial' => '05/03/2010',
                              'periodo_with_slash' => '2010/1'
                              );
    }

    function is_grades_in_history() {
        return false;
    }

    function get_grades() {
        return array();
    }

    function send_grades() {
        return false;
    }

    function check_grades($grades, $course_grade_item) {
        global $CFG;

        if ($course_grade_item->gradetype == GRADE_TYPE_VALUE) {

            return !in_array($course_grade_item->display, $this->valid_display_types);

        } else if ($course_grade_item->gradetype == GRADE_TYPE_SCALE)  {

            if (!isset($CFG->grade_report_transposicao_escala_pg) ||
                $CFG->grade_report_transposicao_escala_pg == 0) {
                print_error("escala_pg_nao_definida");
            }
            $pg_scale = new grade_scale(array('id' => $CFG->grade_report_transposicao_escala_pg));
            $course_scale = new grade_scale(array('id' => $course_grade_item->scaleid));

            return ($pg_scale->scale != $course_scale->scale);

        } else {
            return true;
        }
    }
}

?>
