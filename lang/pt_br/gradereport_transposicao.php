<?php
$string['alerts'] = 'Alertas';
$string['all_grades_formatted'] = '';
$string['all_grades_sent_except_these'] = 'As notas foram transpostas para o CAGR, exceto as dos estudantes listados abaixo:';
$string['all_grades_sent'] = 'Todas as notas foram transpostas para o CAGR';
$string['all_grades_was_sent'] = 'Todas as notas foram transpostas.';
$string['cagr_base_nome'] = 'Base BD CAGR';
$string['cagr_base_msg'] = 'Base de Dados do Sistema Acadêmico';
$string['cagr_db_not_set'] = 'Parâmetros de comunicação com o CAGR/CAPG não definidos, informe o administrador.';
$string['cagr_connection_error'] = 'Não foi possível conectar ao CAGR/CAPG.';
$string['cagr_dates'] = 'O CAGR (sistema acadêmico da graduação) está configurado para permitir transposição de notas do período {$a->periodo_with_slash}, de {$a->dtInicial} e {$a->dtFinal}.';
$string['cagr_host_nome'] = 'Host BD CAGR';
$string['cagr_host_msg'] = 'Host do Banco de Dados do Sistema Academico';
$string['cagr_grade'] = 'Nota CAGR/CAPG';
$string['cagr_pass_nome'] = 'Password BD CAGR';
$string['cagr_pass_msg'] = 'Password para acesso ao Banco de Dados do Sistema Acadêmico';
$string['cagr_user_nome'] = 'User BD CAGR';
$string['cagr_user_msg'] = 'Username para acesso ao Banco de Dados do Sistema Acadêmico';
$string['class_not_in_middleware'] = 'Não é possível transpor as notas desta turma pois esta turma não é mais sincronizada com o sistema acadêmico.';
$string['config_escala_pg'] = 'Escala de notas da Pós-graduação';
$string['config_show_fi'] = 'Mostrar coluna FI';
$string['config_presencial'] = 'Presencial';
$string['desc_presencial'] = 'Se a transposicação de notas está sendo feita para o presencial. Isso afeta os parâmetros das stored procedures que são utilizadas para transpor e buscar notas do CAGR/CAPG.';
$string['confirm_notice'] = 'Você tem certeza que deseja enviar as notas para o Sistema de Controle Acadêmico (CAGR/CAPG)?';
$string['desc_escala_pg'] = 'Essa é a escala que deve ser utilizada pelos cursos da Pós-graduação.';
$string['desc_show_fi'] = 'Se a coluna FI deve ser mostrada. Caso negativo, as notas são sempre enviadas como FS';
$string['dont_use_metacourse_grades'] = 'Clique aqui caso queira usar as notas deste curso ao invés das notas do agrupamento.';
$string['error_on_middleware_connection'] = 'Erro ao conectar ao middleware';
$string['fi'] = 'Frequência Insuficiente';
$string['grades_in_history'] = 'As notas desta turma não podem ser transpostas pois já foram incorporadas ao histórico escolar do estudante.';
$string['grades_selected_by_group'] = 'A transposição de notas não pode ser feita pois você está visualizando apenas um grupo. Para transpor as notas é preciso visualizar todos os participantes. Para isso, defina o campo \"Grupos visíveis\" como \"Todos os participantes\" no topo deste relatório e clique no botão \"Transpor notas para o CAGR/CAPG\".';
$string['grades_updated_on_cagr'] = 'Atenção: há notas que foram digitadas diretamente no CAGR (veja a coluna \"Alertas\"). Para transpor as notas desses alunos, substituindo as existentes, é necessário marcar a opção \"sobrepor\" que encontra-se no final deste relatório.';
$string['grade_updated_on_cagr'] = 'Nota alterada diretamente no CAGR/CAPG.';
$string['invalidusername'] = 'Para transpor as notas, você deve ser um professor. Nenhuma nota foi transposta.';
$string['is_metacourse_error'] = 'Este é um agrupamento de turmas. Neste caso, a transposição de notas deve ser realizada nas turmas afiliadas, uma a uma. Não é necessário desagrupar nem executar qualquer outra ação adicional. Basta acessar a turma e seguir os mesmos passos indicados para uma turma não agrupada. Não estranhe caso não haja conteúdo nem notas registradas na turma afiliada. Ao selecionar o relatório de transposição de notas, as notas são automaticamente buscadas no agrupamento.';
$string['mention'] = 'Menção I';
$string['mid_dbname_nome'] = 'Nome BD Middleware';
$string['mid_dbname_msg'] = 'Nome do Banco de Dados do Middleware';
$stirng['modalidade_not_grad_nor_pos'] = 'Não é possível transpor as notas desta turma pois a modalidade dela é diferente de Graduação e Pós-Graduação';
$string['modulename'] = 'Transposição de notas Moodle-CAGR/CAPG';
$string['moodle_grade'] = 'Nota Moodle';
$string['never_sent'] = 'Nunca';
$string['not_cagr_course'] = 'Transposição indisponível para turmas do CAPG.';
$string['overwrite_all_grades'] = 'Sobrepor notas que foram digitadas diretamente no CAGR (veja a coluna \"Alertas\")';
$string['pluginname'] = 'Transposição de notas Moodle-CAGR/CAPG';
$string['prevent_grade_sent'] = ' Isto impede o envio de notas.';
$string['return_to_index'] = 'Voltar à relação de notas.';
$string['send_date_not_in_time'] = 'As notas desta turma não podem ser transpostas para o CAGR pois no momento a digitação de notas não está aberta para nenhum período. No momento, o CAGR está configurado para digitação de notas do período {$a->periodo_with_slash}, de {$a->dtInicial} até {$a->dtFinal}.';
$string['send_date_not_in_period'] = 'As notas desta turma não podem ser transpostas para o CAGR pois a digitação de notas está aberta para turmas do periodo {$a->periodo_with_slash}. No momento, o CAGR está configurado para digitação de notas do período {$a->periodo_with_slash}, de {$a->dtInicial} até {$a->dtFinal}.';
$string['send_date_not_in_period_capg'] = 'As notas desta turma não podem ser transpostas para o CAPG pois a digitação de notas está aberta para disciplinas a partir do ano {$a}.';
$string['send_date_ok_cagr'] = 'O envio de notas está aberto para as disciplinas de {$a->periodo_with_slash} e pode ser feito entre {$a->dtInicial} e {$a->dtFinal}.';
$string['send_date_ok_capg'] = 'O envio de notas está aberto para todas as disciplinas a partir do ano {$a}.';
$string['sent_date'] = 'Data de envio';
$string['some_grades_not_sent'] = 'As notas abaixo não foram transpostas.';
$string['students'] = ' aluno(s)';
$string['students_not_in_cagr'] = 'Alunos cadastrados no AVEA mas não no CAGR/CAPG';
$string['students_not_in_moodle'] = 'Alunos matriculados na turma do CAGR/CAPG mas não no Moodle';
$string['students_ok'] = 'Alunos cadastrados no AVEA e no CAGR/CAPG';
$string['submit_button'] = 'Transpor notas para o CAGR/CAPG';
$string['transposicao:send'] = 'Transpor notas para o CAGR/CAPG';
$string['transposicao:view'] = 'Visualizar notas a serem transpostas';
$string['transposicao'] = 'Transposição de notas Moodle-CAGR/CAPG';
$string['transposicao_help'] = 
    '<h2>Para transferir as notas para o CAGR é necessário que:</h2>

    <ul>
    <li>as notas de todos os alunos estejam de acordo com o expresso no artigo 71 da resolução nº 017/CUn/97: "Todas as avaliações serão expressas através de notas graduadas de 0 (zero) a 10 (dez), não podendo ser fracionadas aquém ou além de 0,5 (zero vírgula cinco).";</li>
    <li>estar dentro do prazo definido pelo calendário acadêmico para digitação das notas finais;</li>
    <li>que a nota de nenhum dos estudantes tenha ainda sido integrada definitivamente em seu histórico escolar.</li>
    </ul>

    <h2>O relatório de transposição de notas Moodle-CAGR/CAPG é subdividido em três seções (tabelas):</h2>

    <ol>
     <li>de alunos que estão regularmente matriculados na turma, mas não estão inscritos na turma correspondente no Moodle: em geral isto ocorre quando há algum problema com o cadastro do aluno no CAGR, a exemplo de email inválido;</li>
     <li>de alunos que estão inscritos na turma no Moodle, mas não estejam matriculados na turma correspondente do CAGR/CAPG: é caso de estudante que tenha sido inscrito pelo próprio professor na turma;</li>
     <li>de alunos que estão regularmente matriculados na turma do CAGR/CAPG e também na turma correspondente no Moodle.</li>
    </ol>

    <p>OBS: somente as notas dos alunos que estão nesta terceira seção é que serão efetivamente transpostas para o CAGR.</p>

    <p>As seções descritas acima incluem os seguintes dados:</p>

    <ol>
     <li><strong>Nome:</strong> o nome completo do aluno, seguido pelo seu número de matrícula.</li>
     <li><strong>Nota Moodle:</strong> a nota registrada na coluna de ‘Nota Final’ do relatório de notas do Moodle.</li>
     <li><strong>Menção I:</strong> possibilita atribuir a menção I ao aluno, de que trata o artigo 74 da resolução nº 017/CUn/97.</li>
     <li><strong>Frequência:</strong> possibilita registrar a frequência insuficiente do aluno, atribuindo-lhe nota 0 (zero).</li>
     <li><strong>Nota CAGR/CAPG:</strong> a nota que está atribuída ao aluno no CAGR</li>
     <li><strong>Data de envio:</strong> data em que a nota foi enviada ao CAGR.</li>
     <li><strong>Alertas:</strong> campo onde são apresentadas mensagens indicando a ocorrência de problemas ou falhas.</li>
    </ol>';//TODO: reformatar
$string['unformatted_grades_cagr'] = 'Há pelo menos uma nota fora do padrão da UFSC. Isto impede o envio de notas.
\"Todas as avaliações serão expressas através de notas graduadas de 0 (zero) a 10 (dez),
não podendo ser fracionadas aquém ou além de 0,5 (zero vírgula cinco).\" (Resolução Nº017/CUn/97)';
$string['unformatted_grades_capg_not_using_letters'] = 'Você não está utilizando letras ou escalas.';
$string['unformatted_grades_capg_invalid_letters'] = 'O curso está utilizando letras fora do padrão UFSC.';
$string['unformatted_grades_capg_invalid_scale'] = 'O curso está utilizando uma escala de notas fora do padrão UFSC.';
$string['use_metacourse_grades'] = 'Clique aqui caso queira usar as notas do agrupamento ao invés das notas deste curso.';
$string['using_course_grades'] = 'As notas apresentadas foram trazidas deste curso.';
$string['using_metacourse_grades'] = 'As notas apresentadas foram trazidas do agrupamento.';
$string['warning_diff_grade'] = 'As notas no Moodle e no CAGR/CAPG diferem';
$string['warning_null_grade'] = 'Este(a) aluno(a) não tem nota atribuída no Moodle. Será transposta a nota 0 (zero).';
$string['will_be_sent'] = '(Essas notas serão transpostas)';
$string['will_overwrite_grades'] = 'Você optou por substituir as notas de todos os alunos transpostas anteriormente.';
$string['wont_be_sent'] = ' <span class=\"wont_be_sent\">Atenção: Essas notas não serão transpostas</span>';
$string['wont_overwrite_grades'] = 'Você optou por substituir as notas apenas dos alunos que não tiveram sua nota alterada diretamente no CAGR/CAPG.';
?>
