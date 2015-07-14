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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    global $DB;

    $settings->add(new admin_setting_configcheckbox('grade_report_wsexport_show_fi',
                                                    get_string('config_show_fi', 'gradereport_wsexport'),
                                                    get_string('desc_show_fi', 'gradereport_wsexport'), 0));

    $settings->add(new admin_setting_configcheckbox('grade_report_wsexport_show_mention',
                                                    get_string('config_show_mention', 'gradereport_wsexport'),
                                                    get_string('desc_show_mention', 'gradereport_wsexport'), 0));


    $scales_options = array(get_string('none'));
    if ($scales = $DB->get_records('scale', array('courseid' => 0), 'id', 'id, name')) {
        foreach($scales as $obj){
            $scales_options[$obj->id] = $obj->name;
        }
    }
    $settings->add(new admin_setting_configselect('grade_report_wsexport_escala_pg',
                                                  get_string('config_escala_pg', 'gradereport_wsexport'),
                                                  get_string('desc_escala_pg', 'gradereport_wsexport'),
                                                  0,
                                                  $scales_options));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_url',
                    get_string('send_grades_url_nome', 'gradereport_wsexport'),
                    get_string('send_grades_url_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_function_name',
                    get_string('send_grades_function_name_nome', 'gradereport_wsexport'),
                    get_string('send_grades_function_name_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_username_param',
                    get_string('send_grades_username_param_nome', 'gradereport_wsexport'),
                    get_string('send_grades_username_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_course_param',
                    get_string('send_grades_course_param_nome', 'gradereport_wsexport'),
                    get_string('send_grades_course_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_grade_param',
                    get_string('send_grades_grade_param_nome', 'gradereport_wsexport'),
                    get_string('send_grades_grade_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_attendance_param',
                    get_string('send_grades_attendance_param_nome', 'gradereport_wsexport'),
                    get_string('send_grades_attendance_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_mention_param',
                    get_string('send_grades_mention_param_nome', 'gradereport_wsexport'),
                    get_string('send_grades_mention_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_is_allowed_to_send_url',
                    get_string('is_allowed_to_send_url_nome', 'gradereport_wsexport'),
                    get_string('is_allowed_to_send_url_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_is_allowed_to_send_function_name',
                    get_string('is_allowed_to_send_function_name_nome', 'gradereport_wsexport'),
                    get_string('is_allowed_to_send_function_name_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_is_allowed_to_send_username_param',
                    get_string('is_allowed_to_send_username_param_nome', 'gradereport_wsexport'),
                    get_string('is_allowed_to_send_username_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_is_allowed_to_send_course_param',
                    get_string('is_allowed_to_send_course_param_nome', 'gradereport_wsexport'),
                    get_string('is_allowed_to_send_course_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_get_grades_url',
                    get_string('get_grades_url_nome', 'gradereport_wsexport'),
                    get_string('get_grades_url_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_get_grades_function_name',
                    get_string('get_grades_function_name_nome', 'gradereport_wsexport'),
                    get_string('get_grades_function_name_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_get_grades_username_param',
                    get_string('get_grades_username_param_nome', 'gradereport_wsexport'),
                    get_string('get_grades_username_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_get_grades_course_param',
                    get_string('get_grades_course_param_nome', 'gradereport_wsexport'),
                    get_string('get_grades_course_param_msg', 'gradereport_wsexport'), ''));
}
