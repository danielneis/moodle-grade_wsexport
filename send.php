<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid = required_param('id', PARAM_INT); // course id
$grades   = required_param_array('grades', PARAM_INT);// grades that was hidden in form
$mention  = optional_param_array('mention', array(), PARAM_RAW); // mencao i
$fi       = optional_param_array('fi', array(), PARAM_RAW); // mencao i
$send_yes = optional_param('send_yes', null, PARAM_RAW); // send grades
$send_no  = optional_param('send_no', null, PARAM_RAW); // do not send grades  TODO: avaliar se null causa problemas

if (is_null($send_yes)) {
    redirect($CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$courseid);
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

if (!is_numeric($USER->username)) {
    print_error('invalidusername', 'gradereport_transposicao', $CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$courseid);
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('gradereport/transposicao:send', $context);

$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));
$report = new grade_report_transposicao($courseid, $gpr, $context, 0, null, null);

$report->send_grades($grades, $mention, $fi);
redirect($CFG->wwwroot.'/grade/report/transposicao/results.php?id='.$courseid);

?>
