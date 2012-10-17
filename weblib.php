<?php

function get_checkbox($name, $checked, $disabled) {
    $check = $checked ? 'checked="checked"' : '';
    $dis = $disabled ? 'disabled="disabled"' : '';
    return '<input type="checkbox" name="'.$name.'" '.$check.' value="1" '.$dis.'/>';
}

function lista_turmas_afiliadas($courseid){
    global $OUTPUT, $CFG;

    echo $OUTPUT->box_start('generalbox block_cagr_centralizado');
    echo get_string('is_metacourse_error','gradereport_transposicao');
    echo $OUTPUT->box_end();

    $sql = "SELECT DISTINCT e.customint1 as id, filha.fullname
                       FROM {course} c
                       JOIN {enrol} e
                         ON (e.courseid = c.id)
                       JOIN {course} filha
                         ON (e.customint1 = filha.id)
                      WHERE e.enrol = 'meta'
                        AND c.id = {$id_metacurso}";
    $turmas = academico::get_records_sql($sql);

    $turmas_professor = array();
    $turmas_outros = array();

    foreach ($turmas as $t) {
        $context = get_context_instance(CONTEXT_COURSE, $t->id);

        if (has_capability('gradereport/transposicao:view', $context)) {
            $turmas_professor[] = "<a href='{$CFG->wwwroot}/grade/report/transposicao/index.php?id={$t->id}' target='_blank'> {$t->fullname} </a>";
        }else{
            $turmas_outros[] = $t->fullname;
        }
    }

    if (!empty($turmas_professor)) {
        echo "<br/><br/><h3>", get_string('turmas_prof', 'gradereport_transposicao'), '</h3><ul>';
        foreach($turmas_professor as $t){
            echo "<li>{$t}</li>";
        }
        echo '</ul>';
    }

    if (!empty($turmas_outros)) {
        echo "<br/><br/><h3>", get_string('turmas_outros', 'gradereport_transposicao'), '</h3><ul>';
        foreach($turmas_outros as $t){
            echo "<li>{$t}</li>";
        }
        echo '</ul>';
    }
}
