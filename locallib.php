<?php

function transposicao_get_class_from_courseid($id) {
    global $CFG;

    if (!isset($CFG->mid) || !isset($CFG->mid->base)) {
        print_error('Erro ao conectar ao middleware');
    }

    $sql = "SELECT disciplina, turma, periodo, modalidade
        FROM {$CFG->mid->base}.ViewTurmasAtivas
        WHERE idCursoMoodle = {$id}";

    return get_record_sql($sql);
}
?>
