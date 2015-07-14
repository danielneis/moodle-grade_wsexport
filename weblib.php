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

function get_checkbox($name, $checked, $disabled) {
    $check = $checked ? 'checked="checked"' : '';
    $dis = $disabled ? 'disabled="disabled"' : '';
    return '<input type="checkbox" name="'.$name.'" '.$check.' value="1" '.$dis.'/>';
}

function lista_turmas_afiliadas($courseid){
    global $OUTPUT, $CFG, $DB;

    echo $OUTPUT->box_start('generalbox');
    echo get_string('is_metacourse_error','gradereport_wsexport');
    echo $OUTPUT->box_end();

    $sql = "SELECT DISTINCT e.customint1 as id, filha.fullname
                       FROM {course} c
                       JOIN {enrol} e
                         ON (e.courseid = c.id)
                       JOIN {course} filha
                         ON (e.customint1 = filha.id)
                      WHERE e.enrol = 'meta'
                        AND c.id = {$courseid}";
    $turmas = $DB->get_records_sql($sql);

    $turmas_professor = array();
    $turmas_outros = array();

    foreach ($turmas as $t) {
        $context = context_course::instance($t->id);
        if (has_capability('gradereport/wsexport:view', $context)) {
            $turmas_professor[] = "<a href='{$CFG->wwwroot}/grade/report/wsexport/index.php?id={$t->id}' target='_blank'> {$t->fullname} </a>";
        }else{
            $turmas_outros[] = $t->fullname;
        }
    }

    if (!empty($turmas_professor)) {
        echo "<br/><br/><h3>", get_string('turmas_prof', 'gradereport_wsexport'), '</h3><ul>';
        foreach($turmas_professor as $t){
            echo "<li>{$t}</li>";
        }
        echo '</ul>';
    }

    if (!empty($turmas_outros)) {
        echo "<br/><br/><h3>", get_string('turmas_outros', 'gradereport_wsexport'), '</h3><ul>';
        foreach($turmas_outros as $t){
            echo "<li>{$t}</li>";
        }
        echo '</ul>';
    }
}
