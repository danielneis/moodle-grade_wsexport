<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    gradereport_wsexport
 */

$string['alerts'] = 'Alertas';
$string['all_grades_formatted'] = '';
$string['all_grades_sent_except_these'] = 'As notas foram transpostas para o controle acadêmico, exceto as dos estudantes listados abaixo:';
$string['all_grades_sent'] = 'Todas as notas foram transpostas para o controle acadêmico';
$string['all_grades_was_sent'] = 'Todas as notas foram transpostas.';
$string['remote_base_nome'] = 'Base BD controle acadêmico';
$string['remote_base_msg'] = 'Base de Dados do Sistema Acadêmico';
$string['remote_connection_error'] = 'Não foi possível conexão com o controle acadêmico.';
$string['remote_dates'] = 'O controle acadêmico (sistema acadêmico da graduação) está configurado para permitir transposição de notas do período {$a->periodo_with_slash}, de {$a->dtInicial} e {$a->dtFinal}.';
$string['remote_host_nome'] = 'Host BD controle acadêmico';
$string['remote_host_msg'] = 'Host do Banco de Dados do Sistema Academico';
$string['remote_grade'] = 'Nota controle acadêmico';
$string['remote_pass_nome'] = 'Password BD controle acadêmico';
$string['remote_pass_msg'] = 'Password para acesso ao Banco de Dados do Sistema Acadêmico';
$string['remote_user_nome'] = 'User BD controle acadêmico';
$string['remote_user_msg'] = 'Username para acesso ao Banco de Dados do Sistema Acadêmico';
$string['cannot_populate_tables'] = 'Não foi possível obter dados válidos sobre estudantes e suas notas válidas no Moodle. Por favor, contacte a administração do Moodle.';
$string['class_not_in_middleware'] = 'Não é possível transpor as notas desta turma pois esta turma não é mais sincronizada com o sistema acadêmico.';
$string['config_escala_pg'] = 'Escala de notas da Pós-graduação';
$string['config_show_fi'] = 'Mostrar coluna FI';
$string['config_show_mention'] = 'Mostrar coluna menção';
$string['config_presencial'] = 'Presencial';
$string['desc_presencial'] = 'Se a transposicação de notas está sendo feita para o presencial. Isso afeta os parâmetros das stored procedures que são utilizadas para transpor e buscar notas do controle acadêmico.';
$string['confirm_notice'] = 'Você tem certeza que deseja enviar as notas para o Sistema de Controle Acadêmico (controle acadêmico)?';
$string['desc_escala_pg'] = 'Essa é a escala que deve ser utilizada pelos cursos da Pós-graduação.';
$string['desc_show_fi'] = 'Se a coluna FI deve ser mostrada. Caso negativo, as notas são sempre enviadas como FS';
$string['desc_show_mention'] = 'Se a coluna menção deve ser mostrada. Caso negativo, nenhuma menção será enviada';
$string['dont_use_metacourse_grades'] = 'Clique aqui caso queira usar as notas deste curso ao invés das notas do agrupamento.';
$string['error_on_middleware_connection'] = 'Erro ao conectar ao middleware';
$string['fi'] = 'Frequência Insuficiente';
$string['grades_in_history'] = 'As notas desta turma não podem ser transpostas pois já foram incorporadas ao histórico escolar do estudante.';
$string['grades_selected_by_group'] = 'A transposição de notas não pode ser feita pois você está visualizando apenas um grupo. Para transpor as notas é preciso visualizar todos os participantes. Para isso, defina o campo \'Grupos visíveis\' como \'Todos os participantes\' no topo deste relatório e clique no botão \'Transpor notas para o controle acadêmico\'.';
$string['grades_updated_on_remote'] = 'Atenção: há notas que foram digitadas diretamente no controle acadêmico (veja a coluna \'Alertas\'). Para transpor as notas desses alunos, substituindo as existentes, é necessário marcar a opção \'sobrepor\' que encontra-se no final deste relatório.';
$string['grade_updated_on_remote'] = 'Nota alterada diretamente no controle acadêmico.';
$string['invalid_grade_item_remote'] = 'Tipo inválido para as notas finais (Nota Moodle). O item correspondente à nota final deve ser configurado com:
    <UL>
    <LI>Tipo de nota: <b>Valor</b></LI>
    <LI>Nota máxima: <b>10,0</b></LI>
    <LI>Nota mínima: <b>0,0</b></LI>
    <LI>Nota para aprovação: <b>6,0</b> (opcional)</LI>
    <LI>Tipo de apresentação de nota: <b>Real</b></LI>
    </UL>
    Visite a <A HREF=\'{$a}\' TARGET=\'_blank\'>Configuração do item de Notas Finais</A> para ajustes.
    (<A HREF="http://tutoriais.moodle.ufsc.br/notas/notas.htm" TARGET="_new">ver tutorial sobre configuração de notas</A>).';
$string['is_metacourse_error'] = 'Este é um agrupamento de turmas. Neste caso, a transposição de notas deve ser realizada nas turmas afiliadas, uma a uma.<br/> Não é necessário desagrupar nem executar qualquer outra ação adicional.<br/> Abaixo encontram-se referências para o relatório de transposição de cada uma destas turmas. Apesar de não haver notas registradas nas turmas afiliadas, ao selecionar o relatório de transposição de notas, as notas são automaticamente buscadas no agrupamento.';
$string['mention'] = 'Menção I';
$string['modalidade_not_grad_nor_pos'] = 'Não é possível transpor as notas desta turma pois a modalidade dela é diferente de Graduação e Pós-Graduação';
$string['modulename'] = 'Transposição de notas Moodle-controle acadêmico';
$string['moodle_grade'] = 'Nota Moodle';
$string['never_sent'] = 'Nunca';
$string['not_remote_course'] = 'Transposição indisponível para turmas do controle acadêmico.';
$string['report_not_set'] = 'Módulo de Transposição de Notas não está corretamente configurado. Por favor, contacte a equipe de suporte.';
$string['overwrite_all_grades'] = 'Sobrepor notas que foram digitadas diretamente no controle acadêmico (veja a coluna \'Alertas\')';
$string['pluginname'] = 'Transposição de notas para o controle acadêmico';
$string['prevent_grade_sent'] = ' Isto impede o envio de notas.';
$string['return_to_index'] = 'Voltar à relação de notas.';
$string['send_date_not_in_time'] = 'As notas desta turma não podem ser transpostas pois no momento não há período ativo para digitação de notas configurado no controle acadêmico. A configuração atual é para o período {$a->periodo_with_slash}, de {$a->dtInicial} até {$a->dtFinal}. Em caso de dúvidas sobre o período de digitação de notas, por favor consulte a secretaria de seu departamento.';
$string['send_date_not_in_period'] = 'As notas desta turma não podem ser transpostas pois a digitação de notas no controle acadêmico está aberta apenas para turmas do periodo {$a->periodo_with_slash}, de {$a->dtInicial} até {$a->dtFinal}. Em caso de dúvidas sobre o período de digitação de notas, por favor consulte a secretaria de seu departamento.';
$string['send_date_not_in_period_capg'] = 'As notas desta turma não podem ser transpostas para o CAPG pois a digitação de notas está aberta apenas para disciplinas a partir do ano {$a}.';
$string['send_date_ok_remote'] = 'O envio de notas está aberto para as disciplinas de {$a->periodo_with_slash} e pode ser feito entre {$a->dtInicial} e {$a->dtFinal}.';
$string['send_date_ok_capg'] = 'O envio de notas está aberto para todas as disciplinas a partir do ano {$a}.';
$string['send_grades_url_nome'] = 'Send grades URL';
$string['send_grades_url_msg'] = 'Full URL to webservice to send grades';
$string['send_grades_function_name_nome'] = 'Send grades functon name';
$string['send_grades_function_name_msg'] = 'Name of the function to use to send grades.';
$string['send_grades_username_param_nome'] = 'Send grades username parameter';
$string['send_grades_username_param_msg'] = 'Name of the paramenter of send grades function that identifies who is sending the grades';
$string['send_grades_course_param_nome'] = 'Send grades course parameter';
$string['send_grades_course_param_msg'] = 'Name of the paramenter of send grades function that identifies the course which the grades belongs to';
$string['send_grades_grade_param_nome'] = 'Send grades grade parameter';
$string['send_grades_grade_param_msg'] = 'Name of the paramenter of send grades function that holds the final grade';
$string['send_grades_attendance_param_nome'] = 'Send grades attenance parameter';
$string['send_grades_attendance_param_msg'] = 'Name of the paramenter of send grades function that holds the final attendance value';
$string['send_grades_mention_param_nome'] = 'Send grades mention parameter';
$string['send_grades_mention_param_msg'] = 'Name of the paramenter of send grades function that holds the mention, if used';
$string['is_allowed_to_send_url_nome'] = 'Is allowed to send URL';
$string['is_allowed_to_send_url_msg'] = 'Full URL to webservice functon to check if user can send grades for a course.';
$string['is_allowed_to_send_function_name_nome'] = 'Is allowed to send function name';
$string['is_allowed_to_send_function_name_msg'] = 'Name of the function to check if user is allowed to send grades.';
$string['is_allowed_to_send_username_param_nome'] = 'Is allowed to send username parameter';
$string['is_allowed_to_send_username_param_msg'] = 'Name of the parameter of is allowed to send function that identifies the user who is sending the grades.';
$string['is_allowed_to_send_course_param_nome'] = 'Is allowed to send course parameter';
$string['is_allowed_to_send_course_param_msg'] = 'Name of the parameter of is allowed to send function that identifies the course for which the user is sending the grades.';
$string['get_grades_url_nome'] = 'Get grades URL';
$string['get_grades_url_msg'] = 'Full URL to webservice function to get grades';
$string['get_grades_function_name_nome'] = 'Get grades function name';
$string['get_grades_function_name_msg'] = 'Name of the function to use to get grades.';
$string['get_grades_username_param_nome'] = 'Get grades username parameter';
$string['get_grades_username_param_msg'] = 'Name of the paramenter of get grades function that identifies who is getting the grades';
$string['get_grades_course_param_nome'] = 'Get grades course parameter';
$string['get_grades_course_param_msg'] = 'Name of the paramenter of get grades function that identifies the course to get grades from.';
$string['sent_date'] = 'Data de envio';
$string['some_grades_not_sent'] = 'As notas abaixo não foram transpostas.';
$string['students'] = ' aluno(s)';
$string['students_not_in_remote'] = 'Alunos cadastrados no AVEA mas não no controle acadêmico';
$string['students_not_in_moodle'] = 'Alunos matriculados na turma do controle acadêmico mas não no Moodle';
$string['students_ok'] = 'Alunos cadastrados no AVEA e no controle acadêmico';
$string['submit_button'] = 'Transpor notas para o controle acadêmico';
$string['wsexport:send'] = 'Transpor notas para o controle acadêmico';
$string['wsexport:view'] = 'Visualizar notas a serem transpostas';
$string['wsexport'] = 'Transposição de notas Moodle-controle acadêmico';
$string['wsexport_help'] = '<h2>Para transferir as notas para o controle acadêmico é necessário que:</h2><ul><li>as notas de todos os alunos estejam de acordo com o expresso no artigo 71 da resolução nº 017/CUn/97: \'Todas as avaliações serão expressas através de notas graduadas de 0 (zero) a 10 (dez), não podendo ser fracionadas aquém ou além de 0,5 (zero vírgula cinco).\';</li><li>estar dentro do prazo definido pelo calendário acadêmico para digitação das notas finais;</li><li>que a nota de nenhum dos estudantes tenha ainda sido integrada definitivamente em seu histórico escolar.</li></ul><h2>O relatório de transposição de notas Moodle-controle acadêmico é subdividido em três seções (tabelas):</h2><ol><li>de alunos que estão regularmente matriculados na turma, mas não estão inscritos na turma correspondente no Moodle: em geral isto ocorre quando há algum problema com o cadastro do aluno no controle acadêmico, a exemplo de email inválido;</li><li>de alunos que estão inscritos na turma no Moodle, mas não estejam matriculados na turma correspondente do controle acadêmico: é caso de estudante que tenha sido inscrito pelo próprio professor na turma;</li><li>de alunos que estão regularmente matriculados na turma do controle acadêmico e também na turma correspondente no Moodle.</li></ol><p>OBS: somente as notas dos alunos que estão nesta terceira seção é que serão efetivamente transpostas para o controle acadêmico.</p><p>As seções descritas acima incluem os seguintes dados:</p><ol><li><strong>Nome:</strong> o nome completo do aluno, seguido pelo seu número de matrícula.</li><li><strong>Nota Moodle:</strong> a nota registrada na coluna de ‘Nota Final’ do relatório de notas do Moodle.</li><li><strong>Menção I:</strong> possibilita atribuir a menção I ao aluno, de que trata o artigo 74 da resolução nº 017/CUn/97.</li><li><strong>Frequência:</strong> possibilita registrar a frequência insuficiente do aluno, atribuindo-lhe nota 0 (zero).</li><li><strong>Nota controle acadêmico:</strong> a nota que está atribuída ao aluno no controle acadêmico</li><li><strong>Data de envio:</strong> data em que a nota foi enviada ao controle acadêmico.</li><li><strong>Alertas:</strong> campo onde são apresentadas mensagens indicando a ocorrência de problemas ou falhas.</li></ol>';
$string['unformatted_remotegrades'] = 'Há pelo menos uma nota fora do padrão de arredondamento de notas da UFSC. Isto impede o envio de notas.
         (<A HREF="http://tutoriais.moodle.ufsc.br/notas/notas.htm" TARGET="_new">ver tutorial sobre configuração de notas</A>).';
$string['turmas_prof'] = 'Turmas que você pode transpor notas';
$string['turmas_outros'] = 'Turmas de outros professores (os responsáveis devem transpor as notas)';
$string['unformatted_grades_capg_not_using_letters'] = 'A transposição de conceitos para o CAPG exige que a nota final do curso no Moodle utilize a escala "Conceitos da Pós-Graduação/UFSC" ou mapeamento de notas numéricas para letras.
         (<A HREF="http://tutoriais.moodle.ufsc.br/conceitos_capg/conceitos_capg.htm" TARGET="_new">ver tutorial sobre configuração de conceitos</A>).';
$string['unformatted_grades_capg_invalid_letters'] = 'O conceito final configurado neste curso utiliza letras não previstas no padão de conceitos do CAPG.
         (<A HREF="http://tutoriais.moodle.ufsc.br/conceitos_capg/conceitos_capg.htm" TARGET="_new">ver tutorial sobre configuração de conceitos</A>).';
$string['unformatted_grades_capg_invalid_scale'] = 'O conceito final configurado neste curso utiliza uma escala de notas diferente de "Conceitos da Pós-Graduação/UFSC".
         (<A HREF="http://tutoriais.moodle.ufsc.br/conceitos_capg/conceitos_capg.htm" TARGET="_new">ver tutorial sobre configuração de conceitos</A>).';
$string['unknown_gradetype'] = 'Tipo de nota desconhecido';
$string['use_metacourse_grades'] = 'Clique aqui caso queira usar as notas do agrupamento ao invés das notas deste curso.';
$string['using_course_grades'] = 'As notas apresentadas foram trazidas deste curso.';
$string['using_metacourse_grades'] = 'As notas apresentadas foram trazidas do agrupamento.';
$string['warning_diff_grade'] = 'As notas/conceitos no Moodle e no controle acadêmico diferem';
$string['warning_null_grade'] = 'Sem nota/conceito. Será transposto:&nbsp;\'{$a}\'';
$string['will_be_sent'] = '(Essas notas serão transpostas)';
$string['will_overwrite_grades'] = 'Você optou por substituir as notas de todos os alunos transpostas anteriormente.';
$string['wont_be_sent'] = ' <span class=\'wont_be_sent\'>Atenção: Essas notas não serão transpostas</span>';
$string['wont_overwrite_grades'] = 'Você optou por substituir as notas apenas dos alunos que não tiveram sua nota alterada diretamente no controle acadêmico.';
