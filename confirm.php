<?php

include_once('../../../config.php');
require_once($CFG->libdir .'/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');

$courseid      = required_param('id', PARAM_INT);// course id
$grades        = required_param('grades');// grades that was hidden in form
$overwrite_all = optional_param('overwrite_all', PARAM_INT);// should overwrite grades updated directly on cagr
$grades_cagr   = optional_param('grades_cagr');// grades that was updated on cagr, hidden in form

if (!$course = get_record('course', 'id', $courseid)) {
    print_error('invalidcourseid');
}

require_login($course->id);

$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:send', $context);

$str_grades = get_string('grades');
$str_transposition = get_string('modulename', 'gradereport_transposicao');
$str_confirm =  get_string('confirm_notice', 'gradereport_transposicao');
$str_yes = get_string('yes');
$str_no = get_string('no');

if ($overwrite_all) {
    $str_notice = get_string('will_overwrite_grades', 'gradereport_transposicao');
} else {
    // remove grades that was updated on cagr, if user did not want to overwrite
    $grades = array_diff_key($grades, $grades_cagr);
    $str_notice = get_string('wont_overwrite_grades', 'gradereport_transposicao');
}

// START INTERFACE
$navigation = grade_build_nav(__FILE__, $str_transposition, $course->id);

/// Print header
print_header_simple($str_grades.': '.$str_transposition, ': '.$str_grades, $navigation, '', '', true);
print_grade_plugin_selector($courseid, 'report', 'transposicao');

print_heading($str_transposition, 'left', 1, 'page_title');

echo '<form method="post" action="send.php">';

foreach ($grades as $matricula => $grade) {
    echo '<input type="hidden" name="grade['.$matricula.']" value="'.$grade.'"/>';
}

echo '<p>', $str_notice, '</p><p>',$str_confirm, '</p>',
     '<p class="yes_no" ><input type="submit" name="yes" value="'.$str_yes.'" />',
     '<input type="submit" name="no" value="'.$str_no.'" /></p></form>';

print_footer($course);
?>
