<?php
/************************************************************************ 
CLASSE TSYBASE 
------------------------------------------------------------------------- 
Objetivo.....: Facilitar a interface do script com o banco de dados 
Autor........: Carlos Henrique Righetto Moreira (rigs@mailbr.com.br) 
Adaptações:..: Antonio Carlos Mariani (a.c.mariani@inf.ufsc.br) 
Criado em....: 10/JUN/2000 
Versao.......: 1.1 
------------------------------------------------------------------------- 
Como utilizar: 
-> Crie um objeto e passe como parametros o host/ip da maquina, o nome da 
base de dados, o usuario e a senha. Exemplo: 
	$dbase = new TMySQL("127.0.0.1","minhabase","joao","senha"); 

-> Faca uma query passando como parametro a string da query. Exemplo: 
	$dbase->query("select * from tabela"); 

-> Se for um select como no exemplo, utilize as funcoes de navegacao 
para obter os resultados ou um loop para listar todos. Exemplo: 
	for ($i=0;$i<$dbase->count;$i++) { 
	  $coluna1 = $dbase->result["coluna1"]; 
	  $dbase->next(); 
	} 
-> Ha algums atributos interssantes 
	$dbase->count é a qtde de linhas encontrados 
	$dbase->results é um array indexado pelo nome da coluna com o seu valor 
************************************************************************/ 

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

    //****************************** CONECTA NO BANCO 
    function connect($charset=null) {
        if ($charset) {
            $this->socket = sybase_connect($this->host,$this->user,$this->pass,$charset);
        } else {
            $this->socket = sybase_connect($this->host,$this->user,$this->pass);
        }

        if (!$this->socket) { 
            echo "<br />nao rolou a conexao <br/>";
            //throw new ExceptionDB(sybase_get_last_message(), "Connect");
        } else { 
            if (!sybase_select_db($this->dbase,$this->socket)) { 
                throw new ExceptionDB(sybase_get_last_message(), "Select_db");
            } 
        } 
    } 

    //****************************** QUERY 
    function get_operador($query_str) {
        $partes = preg_split("/[^a-z]+/i", $query_str, -1, PREG_SPLIT_NO_EMPTY);
        if (count($partes) > 0)
            return strtolower($partes[0]);
        else
            return "desconhecido";
    }

    function query ($query_str, $index = null) {
        $this->first();
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

    //****************************** MOVIMENTACAO
    function seek($id) { 
        if (!sybase_data_seek($this->intquery, $id)) {
            throw new ExceptionDB(sybase_get_last_message(), "Seek");
        } else { 
            $this->result = sybase_fetch_array($this->intquery); 
            $this->index = $id; 
        } 
    } 

    function first() { 
        if ($this->index!=0) { 
            $this->seek(0); 
            $this->index=0; 
        } 
    } 

    function previous() { 
        if ($this->index-1>0) { 
            $this->seek($this->index-1); 
        } 
    } 

    function next() { 
        if ($this->index+1<$this->count) { 
            $this->seek($this->index+1); 
        } 
    }

    function last() { 
        if ($this->index!=$this->count) { 
            $this->seek($this->count); 
            $this->index=$this->count; 
        } 
    }

    //****************************** LINHAS AFETADAS PELA QUERY 
    function linhasAfetadas() { 
        return sybase_affected_rows($this->socket); 
    } 

} 
?>
