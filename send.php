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

if (!is_numeric($USER->username)) {
    print_error('invalidusername', 'gradereport_transposicao', $CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$courseid);
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:send', $context);

/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));

// Initialise the grader report object
$report = new grade_report_transposicao($courseid, $gpr, $context);

if ($report->initialize_cagr_data()) {
    $report->send_grades($grades, $mention, $fi);
    redirect($CFG->wwwroot.'/grade/report/transposicao/results.php?id='.$courseid);
} else {
    print_error("could not connect to cagr");
}

?>
