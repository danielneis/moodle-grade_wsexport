<?php

require_once('controle_academico.php');

class TransposicaoCAPG extends ControleAcademico {

    private $valid_display_types = array(GRADE_DISPLAY_TYPE_LETTER, GRADE_DISPLAY_TYPE_REAL_LETTER,
                                         GRADE_DISPLAY_TYPE_LETTER_REAL, GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE,
                                         GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER);

    function __construct($klass, $courseid) {
        global $CFG;

        parent::__construct($klass, $courseid, 'capg');

        if (isset($CFG->grade_report_transposicao_presencial) && $CFG->grade_report_transposicao_presencial == true) {
            $this->sp_params = array('send' => 11, 'notas_enviadas' => 2, 'logs' => 3);
        } else {
            $this->sp_params = array('send' => 1, 'notas_enviadas' => 2, 'logs' => 3);
        }

        $this->klass->ano = substr($this->klass->periodo, 0, 4);
        $this->klass->periodo = substr($this->klass->periodo, 4, 1);
    }

    // a transposicao pode ser feitas para turmas do ano passado para frente
    function get_submission_date_range() {
        return date('Y') - 1;
    }

    function in_submission_date_range() {

        if ($this->klass->ano < $this->get_submission_date_range()) {
            $this->submission_date_status  = 'send_date_not_in_period_capg';
            return false;
        }

        $this->submission_date_status = 'send_date_ok_capg';
        return true;
    }

    function is_grades_in_history() {
    }

    function get_grades() {

        $sql = "SELECT convert(char(9),alu.nu_matric_alu) as matricula,
                       ltrim(sel.nm_aluno_sel) as nome,
                       cd_concei_cto as nota,
                       '' as mencao,
                       '' as usuario,
                       dt_atualizacao_mat as dataAtualizacao,
                       cd_frequencia_mat  as frequencia
                  FROM capg..vi_alu alu
                  JOIN capg..vi_mat mat
                    ON (mat.nu_matric_alu = alu.nu_matric_alu)
                  JOIN capg..vi_sel sel
                    ON (sel.nu_cpf_sel = alu.nu_cpf_sel)
                 WHERE nu_ano_per =  {$this->klass->ano}
                   AND mat.nu_period_per =  {$this->klass->periodo}
                   AND mat.cd_discip_dis = '{$this->klass->disciplina}'
                   AND mat.cd_curso_cur = {$this->klass->curso}
                   AND alu.cd_sitalu_sit in (1,4,10,11,13,14,15,16,12)
                   AND (alu.en_eletro_alu is not null and alu.en_eletro_alu <> ' ')
                   AND mat.cd_sitmat_mtd <> 22";

        return $this->db->GetAssoc($sql);
    }

    function send_grades($grades, $mention, $fi) {
        global $USER;

        $msgs = array();
        $this->send_results = array();
        foreach ($grades as $matricula => $grade) {

            if (isset($fi[$matricula])) {
                $f = 'I';
                if ($grade != 'NULL') $grade = '0';
            } else {
                $f = 'S';
            }

            if (empty($grade)){
                $grade = "NULL";
            } else {
                $grade = "'{$grade}'";
            }

            $sql = "EXEC sp_ConceitoMoodleCAPG {$this->sp_params['send']} ,
                    {$this->klass->ano}, {$this->klass->periodo}, '{$this->klass->disciplina}',
                    {$matricula}, {$grade}, '{$f}', {$USER->username}";

            $result = $this->db->Execute($sql);

            $log_info = "matricula: {$matricula}; nota: {$grade}; frequência: {$f}";

            if (!$result) {
                $this->send_results[$matricula] = $this->db->ErrorMsg() . "consulta: {$sql}";
                $log_info .= ' ERRO: '.$this->send_results[$matricula];
            }
            add_to_log($this->courseid, 'grade', 'transposicao', 'send.php', $log_info);
        }
        $this->send_email_with_errors();
        $USER->send_results = $this->send_results;
    }

    function grades_format_status($grades, $course_grade_item) {
        global $CFG;

        if ($course_grade_item->gradetype == GRADE_TYPE_VALUE) {

            if ($course_grade_item->display == 0) {
                // o displaytype do item não foi definido, então temos que pegar o displaytype do curso
                $display = get_field('grade_settings', 'value', 'courseid', $course_grade_item->courseid, 'name', 'displaytype');
            } else {
                $display = $course_grade_item->display;
            }

            // não está utilizando letras
            if (!in_array($display, $this->valid_display_types)) {
                return 'unformatted_grades_capg_not_using_letters';
            }

            // o item de nota (ou o curso) está usando letras
            // devemos verificar se elas são as mesmas definidas no site
            $course_letters = grade_get_letters(get_context_instance(CONTEXT_COURSE, $course_grade_item->courseid));
            $site_letters = grade_get_letters(get_context_instance(CONTEXT_SYSTEM));

            if (array_values($site_letters) != array_values($course_letters)) {
                return 'unformatted_grades_capg_invalid_letters';
            }

        } else if ($course_grade_item->gradetype == GRADE_TYPE_SCALE)  {

            if (!isset($CFG->grade_report_transposicao_escala_pg) ||
                $CFG->grade_report_transposicao_escala_pg == 0) {
                print_error("escala_pg_nao_definida");
            }
            $pg_scale = new grade_scale(array('id' => $CFG->grade_report_transposicao_escala_pg));
            $course_scale = new grade_scale(array('id' => $course_grade_item->scaleid));

            if ($pg_scale->scale != $course_scale->scale) {
                return 'unformatted_grades_capg_invalid_scale';
            }
        }
        return 'all_grades_formatted';
    }

    function get_displaytype() {
        return GRADE_DISPLAY_TYPE_LETTER;
    }
}
?>
