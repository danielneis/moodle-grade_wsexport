<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');

$courseid = required_param('id', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = context_course::instance($course->id);
require_capability('gradereport/wsexport:send', $context);

$baseurl = new moodle_url('/grade/report/wsexport/results.php', array('id'=>$courseid));

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'gradereport_wsexport'));
$PAGE->set_heading(get_string('pluginname', 'gradereport_wsexport'));

$navigation = grade_build_nav(__FILE__, get_string('modulename', 'gradereport_wsexport'), $course->id);

print_grade_page_head($COURSE->id, 'report', 'wsexport',
                      get_string('modulename', 'gradereport_wsexport') .
                      $OUTPUT->help_icon('wsexport', 'gradereport_wsexport'));

if (empty($USER->send_results)) {

    echo '<p>', get_string('all_grades_was_sent', 'gradereport_wsexport'), '</p>';

} else {

    $names = $DB->get_records_select('user', 'username IN ('.implode(',', array_keys($USER->send_results)) . ')',
                                     null,'firstname,lastname', 'username,firstname');

    echo '<p>', get_string('some_grades_not_sent', 'gradereport_wsexport'), '</p>',
         '<ul class="send_results">';
    foreach ($USER->send_results as $matricula => $msg) {
        echo '<li>',$names[$matricula]->firstname, ' (', $matricula, '): ', $msg, '</li>';
    }
    echo '</ul>';
}

echo '<a href="'.$CFG->wwwroot.'/grade/report/wsexport/index.php?id='.$course->id.'">',
       get_string('return_to_index', 'gradereport_wsexport'),
     '</a>',
     $OUTPUT->footer();
