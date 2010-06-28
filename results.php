<?php

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/transposicao/lib.php');

$courseid = required_param('id', PARAM_INT); // course id

if (!$course = get_record('course', 'id', $courseid)) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/transposicao:send', $context);

// END "BOOT" LOGIG, STARTING INTERFACE
$str_grades = get_string('grades');
$str_transposition = get_string('modulename', 'gradereport_transposicao');

$navigation = grade_build_nav(__FILE__, $str_transposition, $course->id);

/// Print header
print_grade_page_head($COURSE->id, 'report', 'transposicao',
                      get_string('modulename', 'gradereport_transposicao') .
                      helpbutton('transposicao', 'Transposição', 'gradereport_transposicao', true, false, '', true));


if (empty($USER->send_results)) {
    echo '<p>', get_string('all_grades_was_sent', 'gradereport_transposicao'), '</p>';
} else {
    $names = get_records_select('user', 'username IN ('.implode(',', array_keys($USER->send_results)) . ')',
                                'firstname,lastname', 'username,firstname');

    echo '<p>', get_string('some_grades_not_sent', 'gradereport_transposicao'), '</p>',
         '<ul class="send_results">';
    foreach ($USER->send_results as $matricula => $msg) {
        echo '<li>',$names[$matricula]->firstname, ' (', $matricula, '): ', $msg, '</li>'; 
    }
    echo '</ul>';
}

echo '<a href="'.$CFG->wwwroot.'/grade/report/transposicao/index.php?id='.$course->id.'">',
     get_string('return_to_index', 'gradereport_transposicao'),
     '</a>';

print_footer($course);
