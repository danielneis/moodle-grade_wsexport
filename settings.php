<?php

$settings->add(new admin_setting_configcheckbox('grade_report_transposicao_show_fi',
                                                get_string('config_show_fi', 'gradereport_transposicao'),
                                                get_string('desc_show_fi', 'gradereport_transposicao'),
                                                0, PARAM_INT));

$settings->add(new admin_setting_configcheckbox('grade_report_transposicao_presencial',
                                                get_string('config_presencial', 'gradereport_transposicao'),
                                                get_string('desc_presencial', 'gradereport_transposicao'),
                                                0, PARAM_INT));

$scales_options = array(get_string('none'));
if ($scales = get_records('scale', 'courseid', 0, 'id', 'id, name')) {
    $scales_options = array_merge($scales_options, records_to_menu($scales, 'id', 'name'));
}

$settings->add(new admin_setting_configselect('grade_report_transposicao_escala_pg',
                                              get_string('config_escala_pg', 'gradereport_transposicao'),
                                              get_string('desc_escala_pg', 'gradereport_transposicao'),
                                              0,
                                              $scales_options));

?>
