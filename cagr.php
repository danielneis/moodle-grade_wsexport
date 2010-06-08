<?php

class TransposicaoCAGR {

    private $db = null; // conexao com o sybase
    private $sybase_error = null; // warnings e erros do sybase

    private $submission_date_status = 'send_date_ok'; // o estado da data atual em relação ao intervalo de envio
    private $grades = array(); // um array com as notas vindas no CAGR

    private $sp_params; // an array with sp_NotasMoodle params

    private $klass; // registro (disciplina,turma,periodo,modalidade) vindo do Middleware

    function __construct($klass) {
        global $CFG;

        if (isset($CFG->grade_report_transposicao_presencial) && $CFG->grade_report_transposicao_presencial == true) {
            $this->sp_params = array('send' => 11, 'history' => 12, 'logs' => 3, 'submission_range' => 14);
        } else {
            $this->sp_params = array('send' => 1, 'history' => 2, 'logs' => 3, 'submission_range' => 4);
        }

        $this->klass = $klass;

        $this->db = ADONewConnection('sybase');
        $this->db->charSet = 'cp850';
        if (function_exists('sybase_set_message_handler')) {
            sybase_set_message_handler(array($this, 'sybase_error_handler'));
        }
        if(!$this->db->Connect($CFG->cagr->host, $CFG->cagr->user, $CFG->cagr->pass, $CFG->cagr->base)) {
            print_error('cagr_connection_error', 'gradereport_transposicao');
        }
    }

    function __destruct() {
        if (!is_null($this->db)) {
            $this->db->Disconnect();
        }
    }

    function get_submission_date_range() {
        $sql = "EXEC sp_NotasMoodle {$this->sp_params['submission_range']}";
        $date_range = $this->db->Execute($sql);
        $date_range = $date_range->GetArray();

        $range = new stdclass();
        $range->periodo   = $date_range[0]['periodo'];
        $range->dtInicial = $date_range[0]['dtInicial'];
        $range->dtFinal   = $date_range[0]['dtFinal'];

        // just eye candy
        $p = (string) $date_range[0]['periodo'];
        $p = $p . $p[4];
        $p[4] = '/';
        $range->periodo_with_slash = $p;

        return $range;
    }

    function in_submission_date_range() {

        $range = get_submission_date_range();
        $now   = time();
        $start = explode('/', $range->dtInicial);
        $end   = explode('/', $range->dtFinal);

        if (!(strtotime("{$start[1]}/{$start[0]}/{$start[2]} 00:00:00") <= $now) ||
            !($now <= strtotime("{$end[1]}/{$end[0]}/{$end[2]} 23:59:59"))) {

            $this->submission_date_status = 'send_date_not_in_time';
            return false;
        }

        $period = $range->periodo;
        if ($this->klass->periodo != $period) {
            $this->cannot_submit = true;

            $this->submission_date_status  = 'send_date_not_in_period';
            return false;
        }

        $this->submission_date_status = 'send_date_ok';

        return true;
    }

    function submission_date_status() {
        return $this->submit_date_status;
    }

    function get_grades() {

        $sql = "SELECT matricula, nome, nota, mencao, frequencia, usuario, dataAtualizacao
                  FROM vi_moodleEspelhoMatricula
                 WHERE periodo = {$this->klass->periodo}
                   AND disciplina = '{$this->klass->disciplina}'
                   AND turma = '{$this->klass->turma}'";

        $result = $this->db->Execute($sql);
        return $result->GetAssoc();
    }

    function send_grades($grades, $mention, $fi) {
        global $USER;

        $msgs = array();
        foreach ($grades as $matricula => $grade) {

            if (isset($mention[$matricula])) {
                $i = "'I'";
                $grade = 'NULL';
            } else {
                $i = 'NULL';
            }

            if (isset($fi[$matricula])) {
                $f = 'FI';
                if ($grade != 'NULL') $grade = '0';
            } else {
                $f = 'FS';
            }

            if ($grade == '-') {
                $grade = "NULL";
            }

            $sql = "EXEC sp_NotasMoodle {$this->sp_params['send']},
                    {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}',
                    {$matricula}, {$grade}, {$i}, '{$f}', {$USER->username}";

            $this->db->Execute($sql);

            $log_info = "matricula: {$matricula}; nota: {$grade}; mencao: {$i}; frequência: {$f}";

            if (!is_null($this->sybase_error)) {
                $this->send_results[$matricula] = utf8_encode($this->sybase_error);
                $log_info .= ' ERRO: '.$this->send_results[$matricula];
            }
            add_to_log($this->courseid, 'grade', 'transposicao', 'send.php', $log_info);
        }
        $this->send_email_with_errors();
        $USER->send_results = $this->send_results;
    }

    function is_grades_in_history() {

        $sql = "EXEC sp_NotasMoodle {$this->sp_params['logs']},
            {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}'";

        $result = $this->db->Execute($sql);
        $result = $result->GetArray();

        if (is_array($result)) {
            foreach ($result as $h)  {
                if (!is_null($h['dtHistorico'])) {
                    return true;
                }
            }
        }
        return false;
    }

    function count_unformatted_grades($grades, $course_grade_item) {

        $unformatted_grades = 0;
        foreach ($grades as $userid => $grade) {
            if (is_numeric($grade)) {
                $decimal_value = explode('.', $grade);
                $decimal_value = $decimal_value[1];
            } else {
                $decimal_value = 0;
            }
            if ( ($grade > 10) || (($decimal_value != 0) && ($decimal_value != 5))) {
                $unformatted_grades++;
            }
        }
        return $unformatted_grades;
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
