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
 * @copyright  2015 onwards Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');
require($CFG->dirroot.'/grade/report/wsexport/lib.php');

$courseid =             required_param('id', PARAM_INT);// course id
$force_course_grades =  optional_param('force_course_grades', 0, PARAM_INT);
$group =                optional_param('group', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_login($courseid);
$context = context_course::instance($courseid);
require_capability('gradereport/wsexport:view', $context);

$baseurl = new moodle_url('/grade/report/wsexport/index.php', array('id' => $courseid));

$gpr = new grade_plugin_return(array('type' => 'report', 'plugin'=> 'grader', 'courseid' => $courseid));
$report = new grade_report_wsexport($courseid, $gpr, $context, $force_course_grades, $group, null);

$report->check_grade_items();

$PAGE->set_url($baseurl);
$PAGE->set_context($context);

print_grade_page_head($COURSE->id, 'report', 'wsexport', '', false, false, true, 'wsexport', 'gradereport_wsexport');

if ($report->is_meta_course()) {
    $report->lista_turmas_afiliadas();
} else {
    grade_regrade_final_grades($courseid);// First make sure we have proper final grades.
    $report->show();
}
echo $OUTPUT->footer();
