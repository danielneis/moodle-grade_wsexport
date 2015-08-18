# Exportação de notas do Moodle para Webservices

* Este plugin fica disponível para os professores como um relatório de notas
* Ao acessar esse relatório, o plugin consulta o controle acadêmico para
saber se a pessoa que está acessando pode realmente enviar as notas;
se no momento em que o usuário está tentando enviar as notas isso é permitido;
enfim, qualquer validação que se queira fazer no sistema acadêmico.
** No caso de não ser possível enviar as notas, o sistema remoto deve enviar as
mensagens que serão mostradas para o usuário com os motivos pelo qual ele não pode
acessar aquele relatório ou fazer o envio das notas
* Quando o usuário pode transpor, é apresentado para o usuário uma tela
para ele associar os items de nota do seu curso com os items de nota a serem
enviados para o controle acadêmico.
** caso o plugin esteja configurado para enviar apenas a nota final do curso, esse passo não é executado
* Após selecionar os itens de nota a transpor, é feita uma segunda chamada ao
sistema externo para então validar se as notas do Moodle estão no formato adequado a serem enviadas
** O sistema remoto deve informar quaisquer inconformidades, e nesse caso, o usuário não poderá enviar
as notas até corrigir essa inconformidade e acessar novamente o formulário.
