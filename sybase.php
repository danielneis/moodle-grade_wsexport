<?php
require_once($CFG->dirroot.'/grade/report/transposicao/exception_db.class.php');

class TSYBASE { 
    var $host;     // qual o servidor 
    var $dbase;    // qual a base 
    var $user;     // qual o username 
    var $pass;     // qual a senha 
    var $socket;   // socket da conexao com o banco 
    var $intquery; // int representando o resultado da query 
    var $result;   // fetch_array de $intquery 
    var $count;    // qtde de linhas encontradas 
    var $index;    // indice do vetor $result 

    //****************************** CONSTRUTOR 
    function TSYBASE( $host, $dbase, $user, $pass, $charset=null) {
        $this->host = $host; 
        $this->dbase = $dbase; 
        $this->user = $user; 
        $this->pass = $pass; 
        $this->connect($charset);
    } 

    function close() { 
        return sybase_close($this->socket);
    } 

    function connect($charset=null) {
        if ($charset) {
            $this->socket = sybase_connect($this->host,$this->user,$this->pass,$charset);
        } else {
            $this->socket = sybase_connect($this->host,$this->user,$this->pass);
        }

        if (!$this->socket) { 
            throw new ExceptionDB(sybase_get_last_message(), "Connect");
        } else { 
            if (!sybase_select_db($this->dbase,$this->socket)) { 
                throw new ExceptionDB(sybase_get_last_message(), "Select_db");
            } 
        } 
    } 


    function query ($query_str, $index = null) {
        $this->result = array();
        if ($this->intquery = sybase_query($query_str,$this->socket)) {
            if ($index) {
                while ($row = sybase_fetch_object($this->intquery)) {
                    $this->result[$row->$index] = $row;
                }
            } else {
                while ($row = sybase_fetch_object($this->intquery)) {
                    $this->result[] = $row;
                }
            }
            $this->count = sybase_num_rows($this->intquery);
        } else {
            throw new ExceptionDB(sybase_get_last_message(), $query_str);
        }
    }


    function linhasAfetadas() { 
        return sybase_affected_rows($this->socket); 
    } 

} 
?>
