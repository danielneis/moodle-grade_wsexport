<?php

function get_checkbox($name, $checked, $disabled) {
    $check = $checked ? 'checked="checked"' : '';
    $dis = $disabled ? 'disabled="disabled"' : '';
    return '<input type="checkbox" name="'.$name.'" '.$check.' value="1" '.$dis.'/>';
}

?>
