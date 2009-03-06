<?php

require('../../../config.php');
require('locallib.php');

$courseid = required_param('id', PARAM_INT);// course id
$grades   = required_param('grades');// grades that was hidden in form
$send_yes = optional_param('yes');// send grades
$grades   = optional_param('no');// do not send

$klass = transposicao_get_class_from_courseid($courseid);

?>
