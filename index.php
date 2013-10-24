<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid =             required_param('id', PARAM_INT);// course id
$force_course_grades =  optional_param('force_course_grades', 0, PARAM_INT);
$group =                optional_param('group', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/grade/report/transposicao/index.php', array('id'=>$courseid)));

if (!isset($CFG->grade_report_transposicao_cagr_host) || empty($CFG->grade_report_transposicao_cagr_host) ||
        !isset($CFG->grade_report_transposicao_escala_pg) || empty($CFG->grade_report_transposicao_escala_pg)) {
    $url = "{$CFG->wwwroot}/grade/report/grader/index.php?id={$courseid}";
    print_error('report_not_set', 'gradereport_transposicao', $url);
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_login($courseid);

$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:view', $context);

print_grade_page_head($COURSE->id, 'report', 'transposicao',
                      get_string('modulename', 'gradereport_transposicao') .
                      $OUTPUT->help_icon('transposicao', 'gradereport_transposicao'));

$sql = "SELECT DISTINCT cm.id
         FROM {course} cm
         JOIN {enrol} e
           ON (e.courseid = cm.id AND
               e.enrol = 'meta')
        WHERE cm.id = {$courseid}";

if (academico::get_record_sql($sql)) { // metacourse

    lista_turmas_afiliadas($courseid);

} else {

    grade_regrade_final_grades($courseid);//first make sure we have proper final grades

    $gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));// return tracking object
    $report = new grade_report_transposicao($courseid, $gpr, $context, $force_course_grades, $group, null);// Initialise the grader report object

    $report->show();

}
echo $OUTPUT->footer();
