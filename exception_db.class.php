<?php

/**
 * Esta classe declara metodos que facilitam o debug de consultas SQL.
 *
 * @author Antonio Carlos Mariani
 * @author Andre Fabiano Dyck
 * @author Caio Moritz Ronchi
 * @package sql
 */
class ExceptionDB extends Exception {

    /**
     * @var string $sql Uma consulta SQL completa.
     */
    var $sql;

    /**
     * Construtor da classe.
     *
     * @todo Documentar adequadamente
     * @param string $message ?
     * @param string $sqlQuery ?
     * @param int $code ?
     */
    public function __construct($message, $sqlQuery, $code = 0) {
        parent::__construct($message, $code);
    	$this->sql = $sqlQuery;
    }

    /**
     * Uma representacao literal do erro que ocorreu internamente ao SGBD apos
     * a execucao de uma consulta.
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message} {$this->sql}\n";
    }

    /**
     * Metodo Getter.
     *
     * @return string O valor do atributo $sql.
     */
    public function getSQL() {
        return $this->sql;
    }
}
?>
