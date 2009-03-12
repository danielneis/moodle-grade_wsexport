<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid = required_param('id', PARAM_INT); // course id
$grades   = required_param('grades'); // grades that was hidden in form
$mention  = optional_param('mention', array()); // mencao i
$fi       = optional_param('fi', array()); // mencao i
$send_yes = optional_param('send_yes'); // send grades
$send_no  = optional_param('send_no'); // do not send grades

if (is_null($send_yes)) {
    redirect($CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$courseid);
}

if (!$course = get_record('course', 'id', $courseid)) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:send', $context);

/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));

// Initialise the grader report object
$report = new grade_report_transposicao($courseid, $gpr, $context);

// END "BOOT" LOGIG, STARTING INTERFACE
$str_grades = get_string('grades');
$str_transposition = get_string('modulename', 'gradereport_transposicao');

$navigation = grade_build_nav(__FILE__, $str_transposition, $course->id);

/// Print header
print_header_simple($str_grades.':'.$str_transposition, ':'.$str_grades, $navigation, '', '', true);
print_grade_plugin_selector($courseid, 'report', 'transposicao');

print_heading($str_transposition, 'left', 1, 'page_title');

if ($report->initialize_cagr_data()) {
    $report->send_grades($grades, $mention, $fi);
    $report->print_send_results();
} else {
    print_error("could not connect to cagr");
}

echo '<a href="'.$CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$course->id.'">',
     get_string('return_to_index', 'gradereport_transposicao'),
     '</a>';

print_footer($course);
?>
