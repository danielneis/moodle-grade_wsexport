<?php

class TransposicaoCAPG {

    private $valid_display_types = array(GRADE_DISPLAY_TYPE_LETTER, GRADE_DISPLAY_TYPE_REAL_LETTER,
                                         GRADE_DISPLAY_TYPE_LETTER_REAL, GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE,
                                         GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER);

    private $unformatted_status = 'ok'; // o motivo da eventual falha na in_submission_grade_range
    private $submission_date_status = 'send_date_ok'; // o estado da data atual em relação ao intervalo de envio

    function __construct($klass) {
        global $CFG;

        if (isset($CFG->grade_report_transposicao_presencial) && $CFG->grade_report_transposicao_presencial == true) {
            $this->sp_params = array('send' => 11, 'notas_enviadas' => 2, 'logs' => 3);
        } else {
            $this->sp_params = array('send' => 1, 'notas_enviadas' => 2, 'logs' => 3);
        }

        $this->klass = $klass;

        $this->db = ADONewConnection('sybase');
        $this->db->charSet = 'cp850';
        if (function_exists('sybase_set_message_handler')) {
            sybase_set_message_handler(array($this, 'sybase_error_handler'));
        }
        if(!$this->db->Connect($CFG->cagr->host, $CFG->cagr->user, $CFG->cagr->pass, 'capg')) {
            print_error('cagr_connection_error', 'gradereport_transposicao');
        }

    }

    // a transposicao pode ser feitas para turmas do ano passado para frente
    function get_submission_date_range() {
 //       return $this->klass->ano < (date('m') - 1);
        return (object) array('periodo' => 20101,
                              'dtFinal' => '05/05/2010',
                              'dtInicial' => '05/03/2010',
                              'periodo_with_slash' => '2010/1'
                              );
    }

    function submission_date_status() {
        return 'send_date_ok';
    }

    function in_submission_date_range() {
        return true;
    }

    function __destruct() {
        if (!is_null($this->db)) {
            $this->db->Disconnect();
        }
    }

    function is_grades_in_history() {
    }

    function get_grades() {

        $ano = substr($this->klass->periodo, 0, 4);
        $periodo = substr($this->klass->periodo, 4, 1);
        $sql = "EXEC sp_ConceitoMoodleCAPG {$this->sp_params['notas_enviadas']}, {$ano}, {$periodo}, '{$this->klass->disciplina}'";
        $result = $this->db->Execute($sql);

        $sql = "EXEC sp_ConceitoMoodleCAPG {$this->sp_params['logs']} , {$ano}, {$periodo}, '{$this->klass->disciplina}";
        if ($log = $this->db->Execute($sql)) {
            $log = $dataAtualizacao->GetArray();
            $dataAtualizacao = $log[0]['dtMoodle'];
        } else {
            $dataAtualizacao = '';
        }

        if ($result = $result->GetArray($sql)) {
            $alunos = array();

            foreach ($result as $r) {
                $r['nota'] = $r['conceito'];
                $r['mencao'] = '';
                $r['frequencia'] = '';
                $r['usuario'] = '';
                $r['dataAtualizacao'] = '';
                $alunos[$r['matricula']] = $r;
            }
            return $alunos;
        }
        return $result;
    }

    function send_grades() {
        return false;
    }

    function count_unformatted_grades($grades, $course_grade_item) {
        global $CFG;

        if ($course_grade_item->gradetype == GRADE_TYPE_VALUE) {

            if ($course_grade_item->display == 0) {
                // o displaytype do item não foi definido, então temos que pegar o displaytype do curso
                $display = get_field('grade_settings', 'value', 'courseid', $course_grade_item->courseid, 'name', 'displaytype');
            } else {
                $display = $course_grade_item->display;
            }

            if (!in_array($display, $this->valid_display_types)) {
                return 1;
            }

            // o item de nota (ou o curso) está usando letras
            // devemos verificar se elas são as mesmas definidas no site
            $course_letters = grade_get_letters(get_context_instance(CONTEXT_COURSE, $course_grade_item->courseid));
            $site_letters = grade_get_letters(get_context_instance(CONTEXT_SYSTEM));

            if (array_values($site_letters) != array_values($course_letters)) {
                return 1;
            }

            return 0;

        } else if ($course_grade_item->gradetype == GRADE_TYPE_SCALE)  {

            if (!isset($CFG->grade_report_transposicao_escala_pg) ||
                $CFG->grade_report_transposicao_escala_pg == 0) {
                print_error("escala_pg_nao_definida");
            }
            $pg_scale = new grade_scale(array('id' => $CFG->grade_report_transposicao_escala_pg));
            $course_scale = new grade_scale(array('id' => $course_grade_item->scaleid));

            if ($pg_scale->scale != $course_scale->scale) {
                return 1;
            }

            return 0;

        } else {
            return 1;
        }
    }

    function sybase_error_handler($msgnumber, $severity, $state, $line, $text) {
        if ($text == 'ok') {
            $this->sybase_error = null;
        } else {
            $this->sybase_error = $text;
        }
    }
}

?>
