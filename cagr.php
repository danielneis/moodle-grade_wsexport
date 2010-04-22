<?php

class TransposicaoCAGR {

    private $cagr_db = null; // conexao com o sybase
    private $sybase_error = null; // warnings e erros do sybase

    private $submission_date_range = null; // intervalo de envio de notas
    private $grades = array(); // um array com as notas vindas no CAGR

    // se as notas já foram enviadas para o histórico
    private $grades_in_history = null;

    private $sp_params; // an array with sp_NotasMoodle params

    private $klass; // registro (disciplina,turma,periodo,modalidade) vindo do Middleware

    function __construct($klass) {
        global $CFG;

        if (isset($CFG->grade_report_transposicao_presencial) && $CFG->grade_report_transposicao_presencial == true) {
            $this->sp_params = array('send' => 11, 'history' => 12, 'logs' => 13, 'submission_range' => 14);
        } else {
            $this->sp_params = array('send' => 1, 'history' => 2, 'logs' => 3, 'submission_range' => 4);
        }

        $this->klass = $klass;

        $this->cagr_db = ADONewConnection('sybase');
        $this->cagr_db->charSet = 'cp850';
        sybase_set_message_handler(array($this, 'sybase_error_handler'));
        if(!$this->cagr_db->Connect($CFG->cagr->host, $CFG->cagr->user, $CFG->cagr->pass, $CFG->cagr->base)) {
            print_error('cagr_connection_error', 'gradereport_transposicao');
        }
    }

    function __destruct() {
        if (!is_null($this->cagr_db)) {
            $this->cagr_db->Disconnect();
        }
    }

    function get_submission_date_range() {
        $sql = "EXEC sp_NotasMoodle {$this->sp_params['submission_range']}";
        $date_range = $this->cagr_db->GetArray($sql);

        $range = new stdclass();
        $range->periodo   = $date_range[0]['periodo'];
        $range->dtInicial = $date_range[0]['dtInicial'];
        $range->dtFinal   = $date_range[0]['dtFinal'];

        // just eye candy
        $p = (string) $date_range[0]['periodo'];
        $p[5] = $p[4];
        $p[4] = "/";
        $range->periodo_with_slash = $p;

        return $range;
    }

    function get_grades() {

        $sql = "SELECT matricula, nome, nota, mencao, frequencia, usuario, dataAtualizacao
                  FROM vi_moodleEspelhoMatricula
                 WHERE periodo = {$this->klass->periodo}
                   AND disciplina = '{$this->klass->disciplina}'
                   AND turma = '{$this->klass->turma}'";

        return $this->cagr_db->GetAssoc($sql);
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

            $this->cagr_db->Execute($sql);

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

    function is_grades_already_in_history() {

        if (is_null($this->grades_in_history)) {

            $sql = "EXEC sp_NotasMoodle {$this->sp_params['logs']},
                    {$this->klass->periodo}, '{$this->klass->disciplina}', '{$this->klass->turma}'";

            $result = $this->cagr_db->GetArray($sql);

            $found = false;
            if (is_array($result)) {
                foreach ($result as $h)  {
                    if (!is_null($h['dtHistorico'])) {
                        $found = true;
                        $this->cannot_submit = true;
                        break;
                    }
                }
            }
            $this->grades_in_history = $found;
        }
        return $this->grades_in_history;
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
