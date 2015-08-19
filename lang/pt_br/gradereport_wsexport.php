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
 * @copyright  2015 onwards Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['alerts'] = 'Alertas';
$string['all_grades_formatted'] = '';
$string['all_grades_sent_except_these'] = 'As notas foram transpostas para o controle acadêmico, exceto as dos estudantes listados abaixo:';
$string['all_grades_sent'] = 'Todas as notas foram transpostas para o controle acadêmico';
$string['all_grades_was_sent'] = 'Todas as notas foram transpostas.';
$string['cannot_populate_tables'] = 'Não foi possível obter dados válidos sobre estudantes e suas notas válidas no Moodle. Por favor, contacte a administração do Moodle.';
$string['class_not_in_middleware'] = 'Não é possível transpor as notas desta turma pois esta turma não é mais sincronizada com o sistema acadêmico.';
$string['config_show_fi'] = 'Mostrar coluna FI';
$string['config_show_mention'] = 'Mostrar coluna menção';
$string['confirm_notice'] = 'Você tem certeza que deseja enviar as notas para o controle acadêmico?';
$string['desc_show_fi'] = 'Se a coluna FI deve ser mostrada. Caso negativo, as notas são sempre enviadas como FS';
$string['desc_show_mention'] = 'Se a coluna menção deve ser mostrada. Caso negativo, nenhuma menção será enviada';
$string['dont_use_metacourse_grades'] = 'Clique aqui caso queira usar as notas deste curso ao invés das notas do agrupamento.';
$string['editgradeitems'] = 'Editar items de nota a serem enviados.';
$string['fi'] = 'Frequência Insuficiente';
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
$string['modulename'] = 'Envio de notas para o controle acadêmico';
$string['moodle_grade_course_total'] = 'Nota do curso no Moodle';
$string['must_set_grade_items'] = 'Configure os itens de nota a serem enviados na tela a seguir.';
$string['name'] = 'Nome';
$string['never_sent'] = 'Nunca';
$string['not_remote_course'] = 'Transposição indisponível para turmas do controle acadêmico.';
$string['overwrite_all_grades'] = 'Sobrepor notas que foram digitadas diretamente no controle acadêmico (veja a coluna \'Alertas\')';
$string['pluginname'] = 'Envio de notas para o controle acadêmico';
$string['prevent_grade_sent'] = ' Isto impede o envio de notas.';
$string['report_not_set'] = 'Módulo de Transposição de Notas não está corretamente configurado. Por favor, contacte a equipe de suporte.';
$string['remote_grade'] = 'Nota no controle acadêmico';
$string['return_to_index'] = 'Voltar à relação de notas.';
$string['setgradeitems'] = 'Definir items de nota';
$string['setgradeitems_help'] = 'Você deve definir quais items de nota serão enviados para o sistema acadêmico.';
$string['some_grades_not_sent'] = 'As notas abaixo não foram transpostas.';
$string['students'] = '{$a} aluno(s)';
$string['students_not_in_remote'] = 'Alunos cadastrados no Moodle mas não no controle acadêmico ({$a} aluno(s))';
$string['students_not_in_moodle'] = 'Alunos matriculados na controle acadêmico mas não no Moodle ({$a} aluno(s))';
$string['students_ok'] = 'Alunos cadastrados no Moodle e no controle acadêmico ({$a} aluno(s))';
$string['submit_button'] = 'Transpor notas para o controle acadêmico';
$string['timeupdated'] = 'Última atualização';
$string['turmas_prof'] = 'Turmas que você pode transpor notas';
$string['turmas_outros'] = 'Turmas de outros professores (os responsáveis devem transpor as notas)';
$string['unformatted_remotegrades'] = 'Há pelo menos uma nota fora do padrão de arredondamento de notas da UFSC. Isto impede o envio de notas. (<A HREF="http://tutoriais.moodle.ufsc.br/notas/notas.htm" TARGET="_new">ver tutorial sobre configuração de notas</A>).';
$string['unknown_gradetype'] = 'Tipo de nota desconhecido';
$string['use_metacourse_grades'] = 'Clique aqui caso queira usar as notas do agrupamento ao invés das notas deste curso.';
$string['using_course_grades'] = 'As notas apresentadas foram trazidas deste curso.';
$string['using_metacourse_grades'] = 'As notas apresentadas foram trazidas do agrupamento.';
$string['warning_diff_grade'] = 'As notas/conceitos no Moodle e no controle acadêmico diferem';
$string['warning_null_grade'] = 'Sem nota/conceito. Será transposto:&nbsp;\'{$a}\'';
$string['will_overwrite_grades'] = 'Você optou por substituir as notas de todos os alunos transpostas anteriormente.';
$string['wont_be_sent'] = ' <span class="wont_be_sent">Atenção: Essas notas não serão transpostas</span>';
$string['wont_overwrite_grades'] = 'Você optou por substituir as notas apenas dos alunos que não tiveram sua nota alterada diretamente no controle acadêmico.';
$string['wsexport:send'] = 'Transpor notas para o controle acadêmico';
$string['wsexport:view'] = 'Visualizar notas a serem transpostas';
$string['wsexport'] = 'Transposição de notas Moodle-controle acadêmico';
$string['wsexport_help'] = '<h2>Para transferir as notas para o controle acadêmico é necessário que:</h2><ul><li>as notas de todos os alunos estejam de acordo com o expresso no artigo 71 da resolução nº 017/CUn/97: \'Todas as avaliações serão expressas através de notas graduadas de 0 (zero) a 10 (dez), não podendo ser fracionadas aquém ou além de 0,5 (zero vírgula cinco).\';</li><li>estar dentro do prazo definido pelo calendário acadêmico para digitação das notas finais;</li><li>que a nota de nenhum dos estudantes tenha ainda sido integrada definitivamente em seu histórico escolar.</li></ul><h2>O relatório de transposição de notas Moodle-controle acadêmico é subdividido em três seções (tabelas):</h2><ol><li>de alunos que estão regularmente matriculados na turma, mas não estão inscritos na turma correspondente no Moodle: em geral isto ocorre quando há algum problema com o cadastro do aluno no controle acadêmico, a exemplo de email inválido;</li><li>de alunos que estão inscritos na turma no Moodle, mas não estejam matriculados na turma correspondente do controle acadêmico: é caso de estudante que tenha sido inscrito pelo próprio professor na turma;</li><li>de alunos que estão regularmente matriculados na turma do controle acadêmico e também na turma correspondente no Moodle.</li></ol><p>OBS: somente as notas dos alunos que estão nesta terceira seção é que serão efetivamente transpostas para o controle acadêmico.</p><p>As seções descritas acima incluem os seguintes dados:</p><ol><li><strong>Nome:</strong> o nome completo do aluno, seguido pelo seu número de matrícula.</li><li><strong>Nota Moodle:</strong> a nota registrada na coluna de ‘Nota Final’ do relatório de notas do Moodle.</li><li><strong>Menção I:</strong> possibilita atribuir a menção I ao aluno, de que trata o artigo 74 da resolução nº 017/CUn/97.</li><li><strong>Frequência:</strong> possibilita registrar a frequência insuficiente do aluno, atribuindo-lhe nota 0 (zero).</li><li><strong>Nota controle acadêmico:</strong> a nota que está atribuída ao aluno no controle acadêmico</li><li><strong>Data de envio:</strong> data em que a nota foi enviada ao controle acadêmico.</li><li><strong>Alertas:</strong> campo onde são apresentadas mensagens indicando a ocorrência de problemas ou falhas.</li></ol>';

// Settings strings.

$string['send_grades_heading'] = 'Send grades call';
$string['send_grades_info'] = 'These settings let you configure the how to call a function to send grandes to external system.';
$string['send_grades_url_nome'] = 'Send grades URL';
$string['send_grades_url_msg'] = 'Full URL to webservice to send grades';
$string['send_grades_function_name_nome'] = 'Function name';
$string['send_grades_function_name_msg'] = 'Name of the function to use to send grades.';
$string['send_grades_username_param_nome'] = 'Username parameter';
$string['send_grades_username_param_msg'] = 'Name of the paramenter that identifies who is sending the grades';
$string['send_grades_course_param_nome'] = 'Course parameter';
$string['send_grades_course_param_msg'] = 'Name of the paramenter that identifies the course which the grades belongs to';
$string['send_grades_grades_param_nome'] = 'Grades parameter';
$string['send_grades_grades_param_msg'] = 'Name of the paramenter that holds the grades (must be array(array("user" => "grade"))';
/*
$string['send_grades_attendance_param_nome'] = 'Send grades attendance parameter';
$string['send_grades_attendance_param_msg'] = 'Name of the paramenter of send grades function that holds the final attendance value';
$string['send_grades_mention_param_nome'] = 'Send grades mention parameter';
$string['send_grades_mention_param_msg'] = 'Name of the paramenter of send grades function that holds the mention, if used';
*/
$string['grade_items_heading'] = 'Grades items';
$string['grade_items_info'] = 'These settings let you configure how many and which grade items to send as parameters of send grades call.<br/>By default, onlye the grades for the course total are sent and you have to configure the name of the parameter for this.<br/>For multiple grade items, you configure the parameters and the name that will be shown on report and the teacher choose which grade item to send for each field.';
$string['grade_items_coursetotal_param_nome'] = 'Course total parameter';
$string['grade_items_coursetotal_param_msg'] = 'Name of the paramenter of send grades function that identifies to use to send the course total grade.';
$string['grade_items_multipleitems_nome'] = 'Send multiple grade items';
$string['grade_items_multipleitems_msg'] = 'If you want to export multiple grade items instead of just the course total, check this option.';
$string['grade_items_gradeitem_param_nome'] = 'Grade item parameter {$a}';
$string['grade_items_gradeitem_param_msg'] = 'Name of the parameter to send the grade item.';
$string['grade_items_gradeitem_name_nome'] = 'Grade item name {$a}';
$string['grade_items_gradeitem_name_msg'] = 'Name to be shown on the report to select the corresponding grade item.';

$string['can_user_send_for_course_heading'] = 'Permission checking call (user is allowed to send grade for this course?)';
$string['can_user_send_for_course_info'] = 'These settings let you configure the parameters to check if user can send grades of a course.';
$string['can_user_send_for_course_url_nome'] = 'Can user send URL';
$string['can_user_send_for_course_url_msg'] = 'Full URL to webservice functon to check if user can send grades for a course.';
$string['can_user_send_for_course_function_name_nome'] = 'Can user send function name';
$string['can_user_send_for_course_function_name_msg'] = 'Name of the function to check if user is allowed to send grades.';
$string['can_user_send_for_course_username_param_nome'] = 'Can user send username parameter';
$string['can_user_send_for_course_username_param_msg'] = 'Name of the parameter that identifies the user who is sending the grades.';
$string['can_user_send_for_course_course_param_nome'] = 'Can user send course parameter';
$string['can_user_send_for_course_course_param_msg'] = 'Name of the parameter that identifies the course for which the user is sending the grades.';

$string['are_grades_valid_heading'] = 'Grades checking call (are the grades in the correct format to be sent?)';
$string['are_grades_valid_info'] = 'These settings let you configure the parameters to check if grades are in correct format.';
$string['are_grades_valid_url_nome'] = 'Are grades valid URL';
$string['are_grades_valid_url_msg'] = 'Full URL to webservice functon to check if grades are valid and can be sent.';
$string['are_grades_valid_function_name_nome'] = 'Are grades valid function name';
$string['are_grades_valid_function_name_msg'] = 'Name of the function to check if grades are valid.';
$string['are_grades_valid_username_param_nome'] = 'Are grades valid username parameter';
$string['are_grades_valid_username_param_msg'] = 'Name of the parameter of user who is validating the grades.';
$string['are_grades_valid_course_param_nome'] = 'Are grades valid course parameter';
$string['are_grades_valid_course_param_msg'] = 'Name of the parameter that identified the course for which the grades belong.';
$string['are_grades_valid_grades_param_nome'] = 'Are grades valid grades param';
$string['are_grades_valid_grades_param_msg'] = 'Name of the parameter of to send array of grades to be validated.';

$string['get_grades_heading'] = 'Get grades call';
$string['get_grades_info'] = 'These settings let you configure the parameters for the function to get grades from remote system and its return structure.';
$string['get_grades_url_nome'] = 'Get grades URL';
$string['get_grades_url_msg'] = 'Full URL to webservice function to get grades';
$string['get_grades_function_name_nome'] = 'Get grades function name';
$string['get_grades_function_name_msg'] = 'Name of the function to use to get grades.';
$string['get_grades_username_param_nome'] = 'Get grades username parameter';
$string['get_grades_username_param_msg'] = 'Name of the paramenter of get grades function that identifies who is getting the grades';
$string['get_grades_course_param_nome'] = 'Get grades course parameter';
$string['get_grades_course_param_msg'] = 'Name of the paramenter of get grades function that identifies the course to get grades from.';

$string['get_grades_return_heading'] = 'Get grades return';
$string['get_grades_return_info'] = 'Here you can configure the structure of returned values. This function must return an array like array("username1" => "grades_structure")), where "grades_structure is an associative array with field defined below."';
$string['get_grades_return_grade_nome'] = 'Grade attribute name';
$string['get_grades_return_grade_msg'] = 'Name of the attribute that holds the grades.';
$string['get_grades_return_fullname_nome'] = 'Fullname attribute name';
$string['get_grades_return_fullname_msg'] = 'Name of the attribute that holds the full name of student. Displayed when user are registered in remote but not on Moodle.';
$string['get_grades_return_timeupdated_nome'] = 'Time updated attribute name';
$string['get_grades_return_timeupdated_msg'] = 'Name of the attribute that holds the last time the grade was updated on remote. Must be null if never updated.';
$string['get_grades_return_updatedbymoodle_nome'] = 'Updated by moodle attribute name';
$string['get_grades_return_updatedbymoodle_msg'] = 'Name of the attribute that holds true if last update on grades were made by Moodle and otherwise holds false.';
$string['get_grades_return_attendance_nome'] = 'Attendance attribute name';
$string['get_grades_return_attendance_msg'] = 'Name of the optional attribute that holds the attendance status of the student.';

