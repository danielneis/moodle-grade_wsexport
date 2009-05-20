<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid = required_param('id', PARAM_INT);// course id
$force_course_grades = optional_param('force_course_grades', 0);

if (!$course = get_record('course', 'id', $courseid)) {
    print_error('invalidcourseid');
}

require_login($course->id);

if ($course->metacourse > 0) {
    print_error(get_string('is_metacourse_error', 'gradereport_transposicao'));
}

$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:view', $context);

grade_regrade_final_grades($courseid);//first make sure we have proper final grades

$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));// return tracking object
$report = new grade_report_transposicao($courseid, $gpr, $context, null, $force_course_grades);// Initialise the grader report object

/// Print header
print_grade_page_head($COURSE->id, 'report', 'transposicao');

if ($report->initialize_cagr_data() && $report->setup_table() && $report->fill_table()) {
    echo $report->print_header(),
         $report->print_table(),
         $report->print_footer();
}

print_footer($course);
?>
