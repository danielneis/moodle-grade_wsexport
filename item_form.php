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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to select which grade items to send.
 *
 */
class grade_report_wsexport_item_form extends moodleform {

    public function definition() {
        global $DB, $CFG;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        $items = grade_item::fetch_all(array('courseid' => $courseid));
        $gradeitems = array();
        foreach ($items as $i) {
            $gradeitems[$i->id] = $i->get_name();
        }

        for ($i = 1; $i < 4; $i++) {
            $courseitemparam = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_course'.$courseid;
            $itemnamecfg  = 'grade_report_wsexport_grade_items_gradeitem'.$i.'_name';
            $itemname = $CFG->{$itemnamecfg};
            $mform->addElement('select', $courseitemparam, $itemname, $gradeitems);
        }

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('submit', 'submit', get_string('update'));

    }

    /**
     * Validates form data
     */
    public function validation($data, $files) {
        global $OUTPUT;
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
