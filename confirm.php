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

require('../../../config.php');
require($CFG->libdir .'/gradelib.php');
require($CFG->dirroot.'/grade/lib.php');

$courseid                = required_param('id', PARAM_INT);// Course id.
$grades                  = required_param_array('grades', PARAM_RAW);// Grades that was hidden in form.
$mentions                = optional_param_array('mentions', array(), PARAM_RAW);// mencao i.
$insufficientattendances = optional_param_array('insufficientattendances', array(), PARAM_RAW);// Frequencias insuficientes.
$remotegrades            = optional_param('remotegrades', array(), PARAM_RAW);// Grades that was updated on remote, hidden in form.
$overwrite_all           = optional_param('overwrite_all', 0, PARAM_INT);// Should overwrite grades updated directly on remote.

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

$strgrades        = get_string('grades');
$strtransposition = get_string('modulename', 'gradereport_wsexport');
$strconfirm       = get_string('confirm_notice', 'gradereport_wsexport');
$stryes           = get_string('yes');
$strno            = get_string('no');
$strnotice        = '';

if (!empty($remotegrades)) {
    if ($overwrite_all == 1) {
        $strnotice = get_string('will_overwrite_grades', 'gradereport_wsexport');
    } else {
        // Remove grades that was updated on remote, if user did not want to overwrite.
        $grades = array_diff_key($grades, $remotegrades);
        $strnotice = get_string('wont_overwrite_grades', 'gradereport_wsexport');
    }
}

$navigation = grade_build_nav(__FILE__, $strtransposition, $course->id);

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

foreach ($insufficientattendances as $matricula => $fi) {
    echo '<input type="hidden" name="insufficientattendances['.$matricula.']" value="1"/>';
}

echo '<p>', $strnotice, '</p><p>',$strconfirm, '</p>',
     '<p class="yes_no" ><input type="submit" name="send_yes" value="'.$stryes.'" />',
     '<input type="submit" name="send_no" value="'.$strno.'" /></p></form>';

echo $OUTPUT->footer();
