<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid = required_param('id', PARAM_INT);// course id

if (!$course = get_record('course', 'id', $courseid)) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:view', $context);

/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));

//first make sure we have proper final grades
grade_regrade_final_grades($courseid);

// Initialise the grader report object
$report = new grade_report_transposicao($courseid, $gpr, $context);

// END "BOOT" LOGIG, STARTING INTERFACE
// Build navigation
$str_grades = get_string('grades');
$str_transposition = get_string('modulename', 'gradereport_transposicao');

$navigation = grade_build_nav(__FILE__, $str_transposition, $course->id);

/// Print header
print_header_simple($str_grades.':'.$str_transposition, ': '.$str_grades, $navigation, '', '', true);
print_grade_plugin_selector($courseid, 'report', 'transposicao');

print_heading($str_transposition, 'left', 1, 'page_title');

if ($report->initialize_cagr_data() && $report->setup_table() && $report->fill_table()) {
    echo $report->print_header(),
         $report->print_table(),
         $report->print_footer();
}

print_footer($course);
?>
