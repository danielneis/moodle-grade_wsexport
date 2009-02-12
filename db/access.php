<?php
$gradereport_transposicao_capabilities = array(
        'gradereport/transposicao:view' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'legacy' => array(
                'student' => CAP_PROHIBIT,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'admin' => CAP_ALLOW
                )
            ),
        );
?>
