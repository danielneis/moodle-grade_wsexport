<?php

include_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid = required_param('id', PARAM_INT);                   // course id

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
$strgrades = get_string('grades');
$strtransposition = get_string('modulename', 'gradereport_transposicao');

$navigation = grade_build_nav(__FILE__, $strtransposition, $course->id);

/// Print header
print_header_simple($strgrades.':'.$strtransposition, ':'.$strgrades, $navigation, '', '', true);
print_grade_plugin_selector($courseid, 'report', 'outcomes');

print_heading($strtransposition);

if ($report->initialize_cagr_data() && $report->setup_table() && $report->fill_table()) {
    echo '<form method="POST" action="confirm.php">',
         $report->print_table(),
         '<input type="submit" value="Enviar">',
         $report->include_grades_as_hidden_fields(),
         '</form>';
}

print_footer($course);
?>
