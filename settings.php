<?php

$settings->add(new admin_setting_configcheckbox('grade_report_transposicao_show_fi',
                                                get_string('config_show_fi', 'gradereport_transposicao'),
                                                get_string('desc_show_fi', 'gradereport_transposicao'),
                                                0, PARAM_INT));

$settings->add(new admin_setting_configcheckbox('grade_report_transposicao_presencial',
                                                get_string('config_presencial', 'gradereport_transposicao'),
                                                get_string('desc_presencial', 'gradereport_transposicao'),
                                                0, PARAM_INT));

?>
