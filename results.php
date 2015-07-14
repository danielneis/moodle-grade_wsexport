<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    gradereport_wsexport
 */

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');

$courseid = required_param('id', PARAM_INT); // Course id.

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

if (empty($USER->gradereportwsexportsendresults)) {

    echo '<p>', get_string('all_grades_was_sent', 'gradereport_wsexport'), '</p>';

} else {

    $names = $DB->get_records_select('user', 'username IN ('.implode(',', array_keys($USER->gradereportwsexportsendresults)) . ')',
                                     null,'firstname,lastname', 'username,firstname');

    echo '<p>', get_string('some_grades_not_sent', 'gradereport_wsexport'), '</p>',
         '<ul class="gradereportwsexportsendresults">';
    foreach ($USER->gradereportwsexportsendresults as $matricula => $msg) {
        echo '<li>',$names[$matricula]->firstname, ' (', $matricula, '): ', $msg, '</li>';
    }
    echo '</ul>';
}

echo '<a href="'.$CFG->wwwroot.'/grade/report/wsexport/index.php?id='.$course->id.'">',
       get_string('return_to_index', 'gradereport_wsexport'),
     '</a>',
     $OUTPUT->footer();
