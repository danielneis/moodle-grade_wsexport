<?php
$capabilities = array(
        'gradereport/transposicao:view' => array(
            'riskbitmask' => '',
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'student' => CAP_PREVENT,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'admin' => CAP_ALLOW
                )
            ),
        'gradereport/transposicao:send' => array(
            'riskbitmask' => '',
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'student' => CAP_PROHIBIT,
                'teacher' => CAP_PROHIBIT,
                'editingteacher' => CAP_ALLOW,
                'admin' => CAP_ALLOW
                )
            ),
        );
?>
