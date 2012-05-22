<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid =             required_param('id', PARAM_INT);// course id
$force_course_grades =  optional_param('force_course_grades', 0, PARAM_INT);
$group =                optional_param('group', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/grade/report/transposicao/index.php', array('id'=>$courseid)));

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('gradereport/transposicao:view', $context);

grade_regrade_final_grades($courseid);//first make sure we have proper final grades

$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));// return tracking object
$report = new grade_report_transposicao($courseid, $gpr, $context, $force_course_grades, $group, null);// Initialise the grader report object

/// Print header
print_grade_page_head($COURSE->id, 'report', 'transposicao',
                      get_string('modulename', 'gradereport_transposicao') .
                      $OUTPUT->help_icon('transposicao', 'gradereport_transposicao'));

echo "<form method=\"post\" action=\"confirm.php?id={$courseid}\">";//gambi: método report->fill_table() já imprime a tabela no Moodle 2.x. TODO:fix

if ($report->setup_table() && $report->fill_table()) {
    echo $report->print_group_selector(),
         $report->print_header(),
         $report->print_tables(),
         $report->print_footer();
} else {
    print_error('cannot_populate_tables', 'gradereport_transposicao');
}

echo $OUTPUT->footer();
?>
