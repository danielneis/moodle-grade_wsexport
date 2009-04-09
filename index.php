<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid = required_param('id', PARAM_INT);// course id

if (!$course = get_record('course', 'id', $courseid)) {
    print_error(get_string('invalidcourseid'));
}

require_login($course->id);

if ($course->metacourse > 0) {
    print_error(get_string('is_or_in_metacourse', 'gradereport_transposicao'));
}

$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:view', $context);

grade_regrade_final_grades($courseid);//first make sure we have proper final grades

$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));// return tracking object
$report = new grade_report_transposicao($courseid, $gpr, $context);// Initialise the grader report object

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
