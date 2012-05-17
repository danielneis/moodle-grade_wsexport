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
        $scales_options = array_merge($scales_options, $scales);//TODO:saida deve ser [0]=>nome de escala
    }

    $settings->add(new admin_setting_configselect('grade_report_transposicao_escala_pg',
                                                  get_string('config_escala_pg', 'gradereport_transposicao'),
                                                  get_string('desc_escala_pg', 'gradereport_transposicao'),
                                                  0,
                                                  $scales_options));
}
?>
