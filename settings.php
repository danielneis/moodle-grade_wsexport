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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    /*
    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_mention_param',
                    get_string('send_grades_mention_param_nome', 'gradereport_wsexport'),
                    get_string('send_grades_mention_param_msg', 'gradereport_wsexport'), ''));
    */

    // Permission checking - general user and course checking.
    $settings->add(new admin_setting_heading('grade_report_wsexport_can_user_send_for_course',
                    get_string('can_user_send_for_course_heading', 'gradereport_wsexport'),
                    get_string('can_user_send_for_course_info', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_can_user_send_for_course_url',
                    get_string('can_user_send_for_course_url_nome', 'gradereport_wsexport'),
                    get_string('can_user_send_for_course_url_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_can_user_send_for_course_function_name',
                    get_string('can_user_send_for_course_function_name_nome', 'gradereport_wsexport'),
                    get_string('can_user_send_for_course_function_name_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_can_user_send_for_course_username_param',
                    get_string('can_user_send_for_course_username_param_nome', 'gradereport_wsexport'),
                    get_string('can_user_send_for_course_username_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_can_user_send_for_course_course_param',
                    get_string('can_user_send_for_course_course_param_nome', 'gradereport_wsexport'),
                    get_string('can_user_send_for_course_course_param_msg', 'gradereport_wsexport'), ''));

    // Permission checking - general user and course checking.
    $settings->add(new admin_setting_heading('grade_report_wsexport_are_grades_valid',
                    get_string('are_grades_valid_heading', 'gradereport_wsexport'),
                    get_string('are_grades_valid_info', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_are_grades_valid_url',
                    get_string('are_grades_valid_url_nome', 'gradereport_wsexport'),
                    get_string('are_grades_valid_url_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_are_grades_valid_function_name',
                    get_string('are_grades_valid_function_name_nome', 'gradereport_wsexport'),
                    get_string('are_grades_valid_function_name_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_are_grades_valid_username_param',
                    get_string('are_grades_valid_username_param_nome', 'gradereport_wsexport'),
                    get_string('are_grades_valid_username_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_are_grades_valid_course_param',
                    get_string('are_grades_valid_course_param_nome', 'gradereport_wsexport'),
                    get_string('are_grades_valid_course_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_are_grades_valid_grades_param',
                    get_string('are_grades_valid_grades_param_nome', 'gradereport_wsexport'),
                    get_string('are_grades_valid_grades_param_msg', 'gradereport_wsexport'), ''));


    // Get grades call.
    $settings->add(new admin_setting_heading('grade_report_wsexport_get_grades',
                    get_string('get_grades_heading', 'gradereport_wsexport'),
                    get_string('get_grades_info', 'gradereport_wsexport'), ''));

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

    // Get grades return.
    $settings->add(new admin_setting_heading('grade_report_wsexport_get_grades_return',
                    get_string('get_grades_return_heading', 'gradereport_wsexport'),
                    get_string('get_grades_return_info', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_get_grades_return_grade',
                    get_string('get_grades_return_grade_nome', 'gradereport_wsexport'),
                    get_string('get_grades_return_grade_msg', 'gradereport_wsexport'), ''));


    // Send grades.
    $settings->add(new admin_setting_heading('grade_report_wsexport_send_grades',
                    get_string('send_grades_heading', 'gradereport_wsexport'),
                    get_string('send_grades_info', 'gradereport_wsexport'), ''));

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

    $settings->add(new admin_setting_configtext('grade_report_wsexport_send_grades_grades_param',
                    get_string('send_grades_grades_param_nome', 'gradereport_wsexport'),
                    get_string('send_grades_grades_param_msg', 'gradereport_wsexport'), ''));

    // Send grades.
    $settings->add(new admin_setting_heading('grade_report_wsexport_grade_items',
                    get_string('grade_items_heading', 'gradereport_wsexport'),
                    get_string('grade_items_info', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_grade_items_coursetotal_param',
                    get_string('grade_items_coursetotal_param_nome', 'gradereport_wsexport'),
                    get_string('grade_items_coursetotal_param_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configcheckbox('grade_report_wsexport_grade_items_multiplegrades',
                    get_string('grade_items_multipleitems_nome', 'gradereport_wsexport'),
                    get_string('grade_items_multipleitems_msg', 'gradereport_wsexport'), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_grade_items_gradeitem1_param',
                    get_string('grade_items_gradeitem_param_nome', 'gradereport_wsexport', 1),
                    get_string('grade_items_gradeitem_param_msg', 'gradereport_wsexport', 1), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_grade_items_gradeitem1_name',
                    get_string('grade_items_gradeitem_name_nome', 'gradereport_wsexport', 1),
                    get_string('grade_items_gradeitem_name_msg', 'gradereport_wsexport', 1), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_grade_items_gradeitem2_param',
                    get_string('grade_items_gradeitem_param_nome', 'gradereport_wsexport', 2),
                    get_string('grade_items_gradeitem_param_msg', 'gradereport_wsexport', 2), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_grade_items_gradeitem2_name',
                    get_string('grade_items_gradeitem_name_nome', 'gradereport_wsexport', 2),
                    get_string('grade_items_gradeitem_name_msg', 'gradereport_wsexport', 2), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_grade_items_gradeitem3_param',
                    get_string('grade_items_gradeitem_param_nome', 'gradereport_wsexport', 3),
                    get_string('grade_items_gradeitem_param_msg', 'gradereport_wsexport', 3), ''));

    $settings->add(new admin_setting_configtext('grade_report_wsexport_grade_items_gradeitem3_name',
                    get_string('grade_items_gradeitem_name_nome', 'gradereport_wsexport', 3),
                    get_string('grade_items_gradeitem_name_msg', 'gradereport_wsexport', 3), ''));
}
