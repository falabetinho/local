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
 * Portuguese (Brazil) language strings for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Admin Personalizado Local';
$string['localcustomadmin'] = 'Admin Personalizado Local';
$string['localcustomadmin:view'] = 'Visualizar Admin Personalizado Local';
$string['localcustomadmin:manage'] = 'Gerenciar Admin Personalizado Local';

// Settings
$string['displayname'] = 'Nome de Exibição';
$string['displayname_desc'] = 'Nome personalizado que será exibido no lugar do nome padrão do plugin em toda a interface';

// WordPress Integration Settings
$string['wordpress_integration'] = 'Integração WordPress';
$string['wordpress_integration_desc'] = 'Configure a integração com WordPress para sincronização de dados';
$string['enable_wordpress'] = 'Ativar Integração WordPress';
$string['enable_wordpress_desc'] = 'Habilite esta opção para ativar a integração com WordPress';
$string['wordpress_endpoint'] = 'Endpoint WordPress';
$string['wordpress_endpoint_desc'] = 'URL do endpoint da API REST do WordPress (ex: https://seusite.com/wp-json/wp/v2)';
$string['wordpress_username'] = 'Usuário WordPress';
$string['wordpress_username_desc'] = 'Nome de usuário do WordPress para autenticação (deve ter permissão manage_categories)';
$string['wordpress_apppassword'] = 'Senha de Aplicativo WordPress';
$string['wordpress_apppassword_desc'] = 'Application Password gerada no WordPress (Usuários → Perfil → Application Passwords). Cole sem espaços.';

// Modal form strings
$string['addcategory'] = 'Adicionar Categoria';
$string['editcategory'] = 'Editar Categoria';
$string['categoryname'] = 'Nome da Categoria';
$string['categoryimage'] = 'Imagem da Categoria';
$string['categoryimage_help'] = 'Envie uma imagem para representar esta categoria';
$string['categorysaved'] = 'Categoria salva com sucesso';
$string['categoryupdated'] = 'Categoria atualizada com sucesso';
$string['categoryadded'] = 'Categoria adicionada com sucesso';
$string['categorynameexists'] = 'Uma categoria com este nome já existe neste nível';
$string['idnumberexists'] = 'Uma categoria com este número de ID já existe';

// General strings
$string['administration'] = 'Administração';
$string['dashboard'] = 'Painel';
$string['settings'] = 'Configurações';
$string['users'] = 'Usuários';
$string['courses'] = 'Cursos';
$string['manage'] = 'Gerenciar';
$string['view_report'] = 'Ver Relatório';

// Enrolment Management
$string['enrolment_management'] = 'Gerenciamento de Matrículas';
$string['enrolment_management_desc'] = 'Gerencie matrículas, preços de categorias e relatórios de status de pagamento.';
$string['enrolment_methods'] = 'Métodos de Matrícula';
$string['enrolment_methods_desc'] = 'Configure e gerencie os métodos de matrícula disponíveis no sistema.';
$string['statusreport_desc'] = 'Visualize relatórios detalhados de status de pagamento e inadimplência dos alunos.';

// CustomStatus Plugin Operations
$string['customstatus_operations'] = 'Operações do Plugin CustomStatus';
$string['customstatus_matricula'] = 'Matrícula';
$string['customstatus_manage'] = 'Gerenciar Status';
$string['customstatus_assign'] = 'Atribuir Status';
$string['customstatus_edit'] = 'Editar Status';
$string['customstatus_report'] = 'Relatórios';
$string['customstatus_blocked'] = 'Usuários Bloqueados';

// Users management strings
$string['users_management'] = 'Gerenciamento de Usuários';
$string['users_management_desc'] = 'Gerencie e visualize usuários do sistema com opções avançadas de filtro.';
$string['users_desc'] = 'Gerenciamento abrangente de usuários com filtros e ações.';
$string['open_users'] = 'Gerenciar Usuários';

// Page titles
$string['admindashboard'] = 'Painel Administrativo';
$string['adminsettings'] = 'Configurações Administrativas';
$string['courses_management'] = 'Gerenciamento de Cursos';

// Categories Management
$string['categories'] = 'Categorias';
$string['categories_management'] = 'Gerenciamento de Categorias';
$string['categories_management_desc'] = 'Gerencie categorias de cursos, visualize estatísticas e organize sua estrutura de conteúdo educacional.';
$string['add_category'] = 'Adicionar Categoria';

// WordPress Mappings
$string['wordpress_mappings'] = 'Mapeamentos WordPress';
$string['synced'] = 'Sincronizado';
$string['pending'] = 'Pendente';
$string['notfound'] = 'Não encontrado';
$string['type'] = 'Tipo';
$string['last_sync'] = 'Última Sincronização';
$string['showdetails'] = 'Mostrar Detalhes';
$string['error_message'] = 'Mensagem de Erro';
$string['no_mappings_found'] = 'Nenhum Mapeamento Encontrado';
$string['no_mappings_found_desc'] = 'Não há mapeamentos de sincronização entre Moodle e WordPress. Sincronize categorias ou cursos para criar mapeamentos.';
$string['timecreated'] = 'Data de Criação';
$string['timemodified'] = 'Data de Modificação';
$string['edit_category'] = 'Editar Categoria';
$string['view_subcategories'] = 'Visualizar Subcategorias';
$string['no_categories'] = 'Nenhuma categoria encontrada';
$string['create_first_category'] = 'Criar primeira categoria';
$string['category_created'] = 'Categoria criada com sucesso';
$string['category_updated'] = 'Categoria atualizada com sucesso';
$string['category_deleted'] = 'Categoria deletada com sucesso';

// Messages
$string['welcome'] = 'Bem-vindo ao Admin Personalizado Local';
$string['nopermission'] = 'Você não tem permissão para acessar esta página.';
$string['notfound'] = 'Página não encontrada.';
$string['success'] = 'Operação concluída com sucesso.';
$string['error'] = 'Ocorreu um erro ao processar sua solicitação.';

// Template strings
$string['no_admin_tools'] = 'Nenhuma ferramenta administrativa está disponível para sua função de usuário.';

// Card descriptions
$string['dashboard_desc'] = 'Acesse o painel administrativo para visualizar estatísticas do sistema e ações rápidas.';
$string['settings_desc'] = 'Configure e gerencie as configurações administrativas do plugin.';
$string['courses_desc'] = 'Gerencie e monitore todos os cursos do sistema';

// Button texts
$string['open_dashboard'] = 'Abrir Painel';
$string['open_settings'] = 'Abrir Configurações';
$string['open_courses'] = 'Abrir Cursos';

// Course related strings
$string['total_courses'] = 'Total de Cursos';
$string['visible_courses'] = 'Cursos Visíveis';
$string['hidden_courses'] = 'Cursos Ocultos';
$string['create_course'] = 'Criar Curso';
$string['create_course_desc'] = 'Criar um novo curso no sistema';
$string['manage_courses'] = 'Gerenciar Cursos';
$string['manage_courses_desc'] = 'Gerenciar todos os cursos do sistema';
$string['manage_categories'] = 'Gerenciar Categorias';
$string['manage_categories_desc'] = 'Organizar cursos em categorias';
$string['course_backups'] = 'Backups de Cursos';
$string['course_backups_desc'] = 'Restaurar cursos de arquivos de backup';

// Password reset strings
$string['resetpassword'] = 'Resetar Senha';
$string['newpassword'] = 'Nova Senha';
$string['confirmpassword'] = 'Confirmar Senha';
$string['passwordmustmatch'] = 'As senhas não coincidem. Por favor, tente novamente.';
$string['passwordempty'] = 'O campo de senha não pode estar vazio.';
$string['passwordchanged'] = 'Senha alterada com sucesso!';
$string['passwordpolicyerror'] = 'A senha não atende aos requisitos de política de senha.';
$string['passwordresetalert'] = 'Alerta';
$string['resetpasswordtitle'] = 'Resetar Senha do Usuário';
$string['passwordresetsuccess'] = 'Senha alterada com sucesso!';
$string['passwordresetfailed'] = 'Erro ao resetar a senha. Por favor, tente novamente.';
$string['modalopenerror'] = 'Erro ao abrir o modal de resetar senha.';
$string['stringsloaderror'] = 'Erro ao carregar strings de idioma.';

// Privacy API
$string['privacy:metadata'] = 'O plugin Admin Personalizado Local não armazena dados pessoais.';

// Category pricing management strings
$string['categoryprices'] = 'Preços de Categorias';
$string['categoryprices_management'] = 'Gerenciamento de Preços de Categorias';
$string['categoryprices_management_desc'] = 'Gerencie preços de categorias, descontos e taxas de inscrição.';
$string['add_price'] = 'Adicionar Preço';
$string['edit_price'] = 'Editar Preço';
$string['delete_price'] = 'Deletar Preço';
$string['pricename'] = 'Nome do Preço';
$string['pricevalue'] = 'Valor do Preço';
$string['startdate'] = 'Data de Início';
$string['enddate'] = 'Data de Fim';
$string['ispromotional'] = 'É Promocional';
$string['isenrollmentfee'] = 'É Taxa de Inscrição';
$string['nofees'] = 'Sem taxas';
$string['status'] = 'Status';
$string['active'] = 'Ativo';
$string['inactive'] = 'Inativo';
$string['installments'] = 'Número de Parcelas';
$string['pricecreatorsuccess'] = 'Preço criado com sucesso!';
$string['priceupdatesuccess'] = 'Preço atualizado com sucesso!';
$string['pricedeletesuccess'] = 'Preço deletado com sucesso!';
$string['pricedeletefailed'] = 'Falha ao deletar preço.';

// Validation error strings
$string['errorcategoryid'] = 'ID da categoria é obrigatório';
$string['errorcategorynotfound'] = 'Categoria não encontrada';
$string['errorname'] = 'Nome do preço é obrigatório';
$string['errornametoolong'] = 'Nome do preço não deve exceder 255 caracteres';
$string['errorprice'] = 'Valor do preço é obrigatório';
$string['errorpriceinvalid'] = 'Preço deve ser um número positivo válido';
$string['errorstartdateinvalid'] = 'Data de início deve ser um timestamp válido';
$string['errorenddateinvalid'] = 'Data de fim deve ser um timestamp válido';
$string['errordaterange'] = 'Data de início deve ser anterior à data de fim';
$string['errordateoverlap'] = 'Este período de preço sobrepõe um preço ativo existente';
$string['errorinstallments'] = 'Número de parcelas deve estar entre 0 e 12';
$string['errorstatus'] = 'Status deve ser 0 ou 1';
$string['errorispromotional'] = 'Flag promocional deve ser 0 ou 1';
$string['errorisenrollmentfee'] = 'Flag de taxa de inscrição deve ser 0 ou 1';

// Form categoria strings
$string['back'] = 'Voltar';
$string['categoryparent'] = 'Categoria Pai';
$string['categorydescription'] = 'Descrição da Categoria';
$string['categorytheme'] = 'Tema da Categoria';
$string['categorycreated'] = 'Categoria criada com sucesso';
$string['categoryupdated'] = 'Categoria atualizada com sucesso';
$string['categoryduplicate'] = 'Uma categoria com este nome já existe neste nível';

// Course form strings
$string['addcourse'] = 'Adicionar Curso';
$string['editcourse'] = 'Editar Curso';
$string['coursecreated'] = 'Curso criado com sucesso';
$string['courseupdated'] = 'Curso atualizado com sucesso';
$string['shortnametaken'] = 'Nome abreviado já existe';
$string['general'] = 'Geral';
$string['course_enrolments_info'] = 'Veja e gerencie os métodos de inscrição para este curso. Use o link abaixo para importar preços da categoria.';
$string['save_course_first'] = 'Por favor, salve o curso primeiro para gerenciar inscrições.';
$string['enrolled_methods'] = 'Métodos de Inscrição';
$string['no_enrolment_methods'] = 'Nenhum método de inscrição configurado para este curso.';
$string['enrolment_method'] = 'Método de Inscrição';
$string['edit'] = 'Editar';

// Pricing tab strings
$string['pricing'] = 'Precificação';
$string['category_prices'] = 'Preços da Categoria';
$string['add_price'] = 'Adicionar Preço';
$string['price'] = 'Preço';
$string['price_name'] = 'Nome do Preço';
$string['validity_start'] = 'Data de Início';
$string['validity_end'] = 'Data de Fim';
$string['status'] = 'Status';
$string['actions'] = 'Ações';
$string['active'] = 'Ativo';
$string['inactive'] = 'Inativo';
$string['cancel'] = 'Cancelar';
$string['save'] = 'Salvar';
$string['create_category_first'] = 'Por favor, crie a categoria primeiro para gerenciar preços';
$string['promotional'] = 'Preço Promocional';
$string['enrollment_fee'] = 'Taxa de Inscrição';
$string['scheduled_task'] = 'Agendado';
$string['installments'] = 'Número de Parcelas';

// Enrollment prices management
$string['manage_enrol_prices'] = 'Gerenciar Preços de Matrícula';
$string['import_category_prices'] = 'Importar Preços da Categoria';
$string['imported_prices'] = 'Preços Importados';
$string['available_prices'] = 'Preços Disponíveis para Importação';
$string['no_prices_imported'] = 'Nenhum preço foi importado ainda';
$string['no_prices_imported_help'] = 'Use o formulário abaixo para importar preços da categoria.';
$string['no_prices_available'] = 'Não há preços disponíveis na categoria deste curso';
$string['price_already_imported'] = 'Já importado';
$string['unlink_price'] = 'Desvincular';
$string['confirm_unlink'] = 'Deseja realmente desvincular este preço?';
$string['prices_imported_success'] = '{$a} preço(s) importado(s) com sucesso!';
$string['price_unlinked_success'] = 'Vínculo removido com sucesso!';
$string['import_selected_prices'] = 'Importar Preços Selecionados';
$string['back_to_course_edit'] = 'Voltar para Edição do Curso';
$string['course'] = 'Curso';
$string['price_name'] = 'Nome do Preço';
$string['price_value'] = 'Valor';
$string['installments_short'] = 'Parcelamento';
$string['type'] = 'Tipo';
$string['validity_period'] = 'Vigência';
$string['yes'] = 'Sim';
$string['no'] = 'Não';
$string['undefined'] = 'Indefinido';
$string['manage_categories'] = 'Gerenciar Categorias';

// Custom Status Integration
$string['statusreport'] = 'Relatório de Status';
$string['customstatus_integration'] = 'Integração Custom Status';
$string['customstatus_notavailable'] = 'O plugin Custom Status não está instalado ou ativado';
$string['totalstudents'] = 'Total de Alunos';
$string['paidstudents'] = 'Alunos Quitados';
$string['paymentdue'] = 'Pagamento Pendente';
$string['blockedstudents'] = 'Alunos Bloqueados';
$string['expectedrevenue'] = 'Receita Esperada';
$string['receivedrevenue'] = 'Receita Recebida';
$string['pendingrevenue'] = 'Receita Pendente';
$string['markoverdue'] = 'Marcar Inadimplentes';
$string['markoverdue_confirm'] = 'Tem certeza que deseja marcar todos os alunos não pagos como inadimplentes?';
$string['overdue_marked'] = '{$a} aluno(s) marcado(s) como inadimplente(s)';
$string['viewfullreport'] = 'Ver Relatório Completo';
$string['quickactions'] = 'Ações Rápidas';
$string['sendreminder'] = 'Enviar Lembrete';
$string['contactstudent'] = 'Contatar Aluno';
$string['checkoverduepayments'] = 'Verificar pagamentos vencidos e atualizar status dos alunos';

// Enrolment Management
$string['enrolment'] = 'Gerenciamento de Matrículas';
$string['enrolment_desc'] = 'Gerencie as matrículas dos alunos com status personalizados';
$string['open_enrolment'] = 'Abrir Matrículas';
$string['enrolment_management'] = 'Gerenciamento de Matrículas';
$string['selectcoursetoenrol'] = 'Selecione o Curso para Matricular';
$string['selectcategory'] = 'Selecione uma categoria';
$string['selectcourse'] = 'Selecione um curso';
$string['selectedcourse'] = 'Curso Selecionado';
$string['nocustomstatusenrol'] = 'Método de matrícula Custom Status não encontrado';
$string['nocustomstatusenrol_help'] = 'Este curso não possui o método de matrícula Custom Status habilitado. Adicione-o para começar a gerenciar as matrículas dos alunos.';
$string['addcustomstatusenrol'] = 'Adicionar Método Custom Status';
$string['enrolusers'] = 'Matricular Alunos';
$string['enrolusers_desc'] = 'Matricule novos alunos ou atualize matrículas existentes';
$string['enrolnow'] = 'Matricular Agora';
$string['managestatus'] = 'Gerenciar Status';
$string['managestatus_desc'] = 'Atribua ou atualize o status dos alunos';
$string['assignstatus'] = 'Atribuir Status';
$string['viewreport'] = 'Ver Relatório';
$string['viewreport_desc'] = 'Visualize relatório detalhado de matrículas e status';
$string['openreport'] = 'Abrir Relatório';
$string['statusreport_desc'] = 'Relatório integrado de status de pagamento';
$string['open_statusreport'] = 'Abrir Relatório';
$string['totalenrolled'] = 'Total Matriculados';
$string['paidstudents_count'] = 'Alunos Quitados';
$string['paymentdue_count'] = 'Pagamento Pendente';

// Enrolment Data Management
$string['enrolmentdata'] = 'Gerenciamento de Matrícula';
$string['enrolmentdata_desc'] = 'Registros completos de matrícula com dados pessoais, endereço e pagamento';
$string['manage_enrolmentdata'] = 'Gerenciar';

// WordPress Integration Card
$string['wordpress_card_title'] = 'Integração WordPress';
$string['wordpress_card_desc'] = 'Sincronize e gerencie dados entre Moodle e WordPress';
$string['manage_wordpress'] = 'Gerenciar';

// WordPress Integration Page
$string['wordpress_integration_disabled'] = 'A integração WordPress está desabilitada. Ative nas configurações.';
$string['wordpress_settings_incomplete'] = 'Configurações do WordPress incompletas. Configure o endpoint e chave API.';
$string['total_categories'] = 'Total de Categorias';
$string['synced_categories'] = 'Categorias Sincronizadas';
$string['pending_categories'] = 'Categorias Pendentes';
$string['never_synced'] = 'Nunca sincronizado';
$string['sync_categories'] = 'Sincronizar Categorias';
$string['sync_categories_desc'] = 'Envia todas as categorias do Moodle para a taxonomia "niveis" do WordPress';
$string['view_mappings'] = 'Ver Mapeamentos';
$string['view_mappings_desc'] = 'Visualizar mapeamento entre categorias Moodle e termos WordPress';
$string['test_connection'] = 'Testar Conexão';
$string['test_connection_desc'] = 'Verifica se a conexão com a API WordPress está funcionando';
$string['synced'] = 'Sincronizado';
$string['not_synced'] = 'Não Sincronizado';
$string['syncing'] = 'Sincronizando...';
$string['connection_success'] = 'Conexão estabelecida com sucesso';
$string['recent_categories'] = 'Categorias Recentes';
$string['no_categories_found'] = 'Nenhuma categoria encontrada';
$string['actions'] = 'Ações Rápidas';

// WordPress Integration Main Page
$string['sync_categories_title'] = 'Sincronizar Categorias';
$string['sync_categories_title_desc'] = 'Sincronize categorias do Moodle com a taxonomia "niveis" do WordPress';
$string['sync_courses_title'] = 'Sincronizar Cursos';
$string['sync_courses_title_desc'] = 'Sincronize cursos do Moodle como posts personalizados no WordPress';
$string['total_courses'] = 'Total de Cursos';
$string['connection_active'] = 'Conexão ativa com WordPress';
$string['connection_failed'] = 'Falha na conexão com WordPress';
$string['wordpress_integration_help_title'] = 'Como usar a Integração WordPress';
$string['wordpress_integration_help_1'] = 'Use "Sincronizar Categorias" para enviar as categorias do Moodle para a taxonomia "niveis" do WordPress';
$string['wordpress_integration_help_2'] = 'Use "Sincronizar Cursos" para criar ou atualizar posts de cursos no WordPress';
$string['wordpress_integration_help_3'] = 'Certifique-se de que o endpoint e a chave API estão configurados corretamente nas configurações do plugin';

// WordPress Integration Courses
$string['synced_courses'] = 'Cursos Sincronizados';
$string['pending_courses'] = 'Cursos Pendentes';
$string['sync_courses'] = 'Sincronizar Cursos';
$string['sync_courses_action_desc'] = 'Envia todos os cursos do Moodle como posts do tipo "curso" no WordPress';
$string['view_course_mappings_desc'] = 'Visualizar mapeamento entre cursos Moodle e posts WordPress';
$string['recent_courses'] = 'Cursos Recentes';
$string['no_courses_found'] = 'Nenhum curso encontrado';
