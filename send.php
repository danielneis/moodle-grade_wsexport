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

$courseid  = required_param('id', PARAM_INT);
$grades    = optional_param_array('grades', array(), PARAM_RAW);// Grades that was hidden in form.
$mentions  = optional_param_array('mentions', array(), PARAM_RAW); // mencao i.
$insufficientattendances = optional_param_array('insufficientattendances', array(), PARAM_RAW);
$send_yes  = optional_param('send_yes', null, PARAM_RAW); // Do send grades.
$send_no   = optional_param('send_no', null, PARAM_RAW); // Do NOT send grades  TODO: avaliar se null causa problemas.

if (is_null($send_yes)) {
    redirect($CFG->wwwroot.'/grade/report/wsexport/index.php?id='.$courseid);
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_login($course->id);
$context = context_course::instance($course->id);
require_capability('gradereport/wsexport:send', $context);

$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid));
$report = new grade_report_wsexport($courseid, $gpr, $context, 0, null, null);

$report->send_grades($grades, $mentions, $insufficientattendances);
redirect($CFG->wwwroot.'/grade/report/wsexport/results.php?id='.$courseid);
