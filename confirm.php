<?php

require('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');

$courseid      = required_param('id', PARAM_INT);// course id
$grades        = required_param_array('grades', PARAM_RAW);// grades that was hidden in form
$mentions      = optional_param_array('mentions', array(), PARAM_RAW);// mencao i
$fis           = optional_param_array('fis', array(), PARAM_RAW);// frequencias insuficientes
$grades_remote = optional_param('grades_remote', array(), PARAM_RAW);// grades that was updated on remote, hidden in form
$overwrite_all = optional_param('overwrite_all', 0, PARAM_INT);// should overwrite grades updated directly on remote

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = context_course::instance($course->id);
require_capability('gradereport/wsexport:send', $context);

$baseurl = new moodle_url('/grade/report/wsexport/confirm.php', array('id'=>$courseid));

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'gradereport_wsexport'));
$PAGE->set_heading(get_string('pluginname', 'gradereport_wsexport'));

$str_grades        = get_string('grades');
$str_transposition = get_string('modulename', 'gradereport_wsexport');
$str_confirm       = get_string('confirm_notice', 'gradereport_wsexport');
$str_yes           = get_string('yes');
$str_no            = get_string('no');
$str_notice        = '';

if (!empty($grades_remote)) {
    if ($overwrite_all == 1) {
        $str_notice = get_string('will_overwrite_grades', 'gradereport_wsexport');
    } else {
        // remove grades that was updated on remote, if user did not want to overwrite
        $grades = array_diff_key($grades, $grades_remote);
        $str_notice = get_string('wont_overwrite_grades', 'gradereport_wsexport');
    }
}

$navigation = grade_build_nav(__FILE__, $str_transposition, $course->id);

print_grade_page_head($COURSE->id, 'report', 'wsexport',
                      get_string('modulename', 'gradereport_wsexport') .
                      $OUTPUT->help_icon('wsexport', 'gradereport_wsexport'));

echo '<form method="post" action="send.php?id='.$course->id.'">';

foreach ($grades as $matricula => $grade) {
    echo '<input type="hidden" name="grades['.$matricula.']" value="'.$grade.'"/>';
}

foreach ($mentions as $matricula => $mencao) {
    echo '<input type="hidden" name="mentions['.$matricula.']" value="1"/>';
}

foreach ($fis as $matricula => $fi) {
    echo '<input type="hidden" name="fis['.$matricula.']" value="1"/>';
}

echo '<p>', $str_notice, '</p><p>',$str_confirm, '</p>',
     '<p class="yes_no" ><input type="submit" name="send_yes" value="'.$str_yes.'" />',
     '<input type="submit" name="send_no" value="'.$str_no.'" /></p></form>';

echo $OUTPUT->footer();
