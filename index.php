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

$baseurl = new moodle_url('/grade/report/wsexport/index.php', array('id'=>$courseid));

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'gradereport_wsexport'));
$PAGE->set_heading(get_string('pluginname', 'gradereport_wsexport'));

print_grade_page_head($COURSE->id, 'report', 'wsexport',
                      get_string('modulename', 'gradereport_wsexport') .
                      $OUTPUT->help_icon('wsexport', 'gradereport_wsexport'));

$sql = "SELECT DISTINCT cm.id
         FROM {course} cm
         JOIN {enrol} e
           ON (e.courseid = cm.id AND
               e.enrol = 'meta')
        WHERE cm.id = {$courseid}";

if ($DB->get_record_sql($sql)) { // metacourse

    lista_turmas_afiliadas($courseid);

} else {

    grade_regrade_final_grades($courseid);//first make sure we have proper final grades

    $gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));// return tracking object
    $report = new grade_report_wsexport($courseid, $gpr, $context, $force_course_grades, $group, null);// Initialise the grader report object

    $report->show();
}

echo $OUTPUT->footer();
