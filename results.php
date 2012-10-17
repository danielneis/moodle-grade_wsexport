<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');

$courseid = required_param('id', PARAM_INT); // course id
$PAGE->set_url(new moodle_url('/grade/report/transposicao/results.php', array('id'=>$courseid)));

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('gradereport/transposicao:send', $context);

$navigation = grade_build_nav(__FILE__, get_string('modulename', 'gradereport_transposicao'), $course->id);

print_grade_page_head($COURSE->id, 'report', 'transposicao',
                      get_string('modulename', 'gradereport_transposicao') .
                      $OUTPUT->help_icon('transposicao', 'gradereport_transposicao'));

if (empty($USER->send_results)) {

    echo '<p>', get_string('all_grades_was_sent', 'gradereport_transposicao'), '</p>';

} else {

    $names = $DB->get_records_select('user', 'username IN ('.implode(',', array_keys($USER->send_results)) . ')',
                                     null,'firstname,lastname', 'username,firstname');

    echo '<p>', get_string('some_grades_not_sent', 'gradereport_transposicao'), '</p>',
         '<ul class="send_results">';
    foreach ($USER->send_results as $matricula => $msg) {
        echo '<li>',$names[$matricula]->firstname, ' (', $matricula, '): ', $msg, '</li>';
    }
    echo '</ul>';
}

echo '<a href="'.$CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$course->id.'">',
       get_string('return_to_index', 'gradereport_transposicao'),
     '</a>',
     $OUTPUT->footer();
