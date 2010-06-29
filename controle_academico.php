<?php

class ControleAcademico {

    protected $db = null; // conexao com o sybase
    protected $sybase_error = null; // warnings e erros do sybase

    protected $grades = array(); // um array com as notas vindas no CAGR

    protected $sp_params; // an array with sp_NotasMoodle params

    protected $klass; // registro (disciplina,turma,periodo,modalidade) vindo do Middleware
    protected $courseid; // o id do curso moodle para o qual o relatório é instanciado
    protected $submission_date_status = 'send_date_ok'; // o estado da data atual em relação ao intervalo de envio

    function __construct($klass, $courseid, $database) {
        global $CFG;

        $this->db = ADONewConnection('sybase');
        $this->db->charSet = 'cp850';
        if (function_exists('sybase_set_message_handler')) {
            sybase_set_message_handler(array($this, 'sybase_error_handler'));
        }

        if(!$this->db->Connect($CFG->cagr->host, $CFG->cagr->user, $CFG->cagr->pass, $database)) {
            print_error('cagr_connection_error', 'gradereport_transposicao');
        }

        $this->klass = $klass;
        $this->courseid = $courseid;
    }

    function __destruct() {
        if (!is_null($this->db)) {
            $this->db->Disconnect();
        }
    }

    function sybase_error_handler($msgnumber, $severity, $state, $line, $text) {
        if ($text == 'ok') {
            $this->sybase_error = null;
        } else {
            $this->sybase_error = $text;
        }
    }

    function submission_date_status() {
        return $this->submission_date_status;
    }

    protected function send_email_with_errors() {
        if (!empty($this->send_results)) {

            $course_name = get_field('course', 'fullname', 'id', $this->courseid);
            $admin = get_admin();
            $subject = 'Falha na transposicao de notas (CAGR) da disciplina '.$course_name;
            $body = '';

            $names = get_records_select('user', 'username IN ('.implode(',', array_keys($this->send_results)) . ')',
                                        'firstname,lastname', 'username,firstname');

            foreach ($this->send_results as $matricula => $error) {
                $body .= "Matricula: {$matricula}; {$names[$matricula]->firstname} ; Erro: {$error}\n";
            }
            email_to_user($admin, $admin, $subject, $body);
        }
    }
}

?>
