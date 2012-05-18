<?php
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    global $DB;

    $settings->add(new admin_setting_configcheckbox('grade_report_transposicao_show_fi',
                                                    get_string('config_show_fi', 'gradereport_transposicao'),
                                                    get_string('desc_show_fi', 'gradereport_transposicao'), 0));

    $settings->add(new admin_setting_configcheckbox('grade_report_transposicao_presencial',
                                                    get_string('config_presencial', 'gradereport_transposicao'),
                                                    get_string('desc_presencial', 'gradereport_transposicao'), 0));

    $scales_options = array(get_string('none'));
    if ($scales = $DB->get_records('scale', array('courseid' => 0), 'id', 'id, name')) {
        foreach($scales as $obj){
            $scales_options[$obj->id] = $obj->name;
        }
    }
    $settings->add(new admin_setting_configselect('grade_report_transposicao_escala_pg',
                                                  get_string('config_escala_pg', 'gradereport_transposicao'),
                                                  get_string('desc_escala_pg', 'gradereport_transposicao'),
                                                  0,
                                                  $scales_options));

    $settings->add(new admin_setting_configtext('grade_report_transposicao_mid_dbname',
                    get_string('mid_dbname_nome', 'gradereport_transposicao'),
                    get_string('mid_dbname_msg', 'gradereport_transposicao'), 'middleware'));

    $settings->add(new admin_setting_configtext('grade_report_transposicao_cagr_host',
                    get_string('cagr_host_nome', 'gradereport_transposicao'),
                    get_string('cagr_host_msg', 'gradereport_transposicao'), ''));

    $settings->add(new admin_setting_configtext('grade_report_transposicao_cagr_base',
                    get_string('cagr_base_nome', 'gradereport_transposicao'),
                    get_string('cagr_base_msg', 'gradereport_transposicao'), ''));

    $settings->add(new admin_setting_configtext('grade_report_transposicao_cagr_user',
                    get_string('cagr_user_nome', 'gradereport_transposicao'),
                    get_string('cagr_user_msg', 'gradereport_transposicao'), ''));

    $settings->add(new admin_setting_configtext('grade_report_transposicao_cagr_pass',
                    get_string('cagr_pass_nome', 'gradereport_transposicao'),
                    get_string('cagr_pass_msg', 'gradereport_transposicao'), ''));
}
?>
