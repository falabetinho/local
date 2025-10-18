# 📝 Language Strings Reference - local_localcustomadmin

## Overview

Todas as strings utilizadas no plugin estão centralizadas nos arquivos de idioma:
- `lang/en/local_localcustomadmin.php` - English
- `lang/pt_br/local_localcustomadmin.php` - Portuguese (Brazil)

## Strings Organizadas por Módulo

### 🏢 Plugin & Core Strings

```
pluginname                          → "Local Custom Admin" / "Admin Personalizado Local"
localcustomadmin                    → "Local Custom Admin" / "Admin Personalizado Local"
localcustomadmin:view               → "View Local Custom Admin" / "Visualizar Admin Personalizado Local"
localcustomadmin:manage             → "Manage Local Custom Admin" / "Gerenciar Admin Personalizado Local"
```

### 📂 Navigation & Pages

```
administration                      → "Administration" / "Administração"
dashboard                           → "Dashboard" / "Painel"
settings                            → "Settings" / "Configurações"
users                               → "Users" / "Usuários"
courses                             → "Courses" / "Cursos"

admindashboard                      → "Administrative Dashboard" / "Painel Administrativo"
adminsettings                       → "Administrative Settings" / "Configurações Administrativas"
courses_management                  → "Courses Management" / "Gerenciamento de Cursos"
```

### 👥 Users Management

```
users_management                    → "Users Management" / "Gerenciamento de Usuários"
users_management_desc               → "Manage and view system users..." / "Gerencie e visualize usuários..."
users_desc                          → "Comprehensive user management..." / "Gerenciamento abrangente de usuários..."
open_users                          → "Manage Users" / "Gerenciar Usuários"
```

### 📚 Categories Management

```
categories                          → "Categories" / "Categorias"
categories_management               → "Categories Management" / "Gerenciamento de Categorias"
categories_management_desc          → "Manage course categories..." / "Gerencie categorias de cursos..."
add_category                        → "Add Category" / "Adicionar Categoria"
edit_category                       → "Edit Category" / "Editar Categoria"
view_subcategories                  → "View Subcategories" / "Visualizar Subcategorias"
no_categories                       → "No categories found" / "Nenhuma categoria encontrada"
create_first_category               → "Create first category" / "Criar primeira categoria"
category_created                    → "Category created successfully" / "Categoria criada com sucesso"
category_updated                    → "Category updated successfully" / "Categoria atualizada com sucesso"
category_deleted                    → "Category deleted successfully" / "Categoria deletada com sucesso"

// From form_categoria.php
categoryparent                      → "Parent Category" / "Categoria Pai"
categorydescription                 → "Category Description" / "Descrição da Categoria"
categorytheme                       → "Category Theme" / "Tema da Categoria"
categorycreated                     → "Category created successfully" / "Categoria criada com sucesso"
categoryupdated                     → "Category updated successfully" / "Categoria atualizada com sucesso"
categoryduplicate                   → "A category with this name already exists at this level" / 
                                      "Uma categoria com este nome já existe neste nível"
```

### 💰 Category Pricing

```
categoryprices                      → "Category Prices" / "Preços de Categorias"
categoryprices_management           → "Category Pricing Management" / "Gerenciamento de Preços de Categorias"
categoryprices_management_desc      → "Manage category prices, discounts..." / "Gerencie preços de categorias..."
add_price                           → "Add Price" / "Adicionar Preço"
edit_price                          → "Edit Price" / "Editar Preço"
delete_price                        → "Delete Price" / "Deletar Preço"
pricename                           → "Price Name" / "Nome do Preço"
pricevalue                          → "Price Value" / "Valor do Preço"
startdate                           → "Start Date" / "Data de Início"
enddate                             → "End Date" / "Data de Fim"
ispromotional                       → "Is Promotional" / "É Promocional"
isenrollmentfee                     → "Is Enrollment Fee" / "É Taxa de Inscrição"
nofees                              → "No fees" / "Sem taxas"
status                              → "Status" / "Status"
active                              → "Active" / "Ativo"
inactive                            → "Inactive" / "Inativo"
installments                        → "Number of Installments" / "Número de Parcelas"
```

### ✅ Success Messages

```
pricecreatorsuccess                 → "Price created successfully!" / "Preço criado com sucesso!"
priceupdatesuccess                  → "Price updated successfully!" / "Preço atualizado com sucesso!"
pricedeletesuccess                  → "Price deleted successfully!" / "Preço deletado com sucesso!"
success                             → "Operation completed successfully." / "Operação concluída com sucesso."
```

### ❌ Error Messages

```
pricedeletefailed                   → "Failed to delete price." / "Falha ao deletar preço."
error                               → "An error occurred while processing your request." / 
                                      "Ocorreu um erro ao processar sua solicitação."
nopermission                        → "You do not have permission to access this page." / 
                                      "Você não tem permissão para acessar esta página."
notfound                            → "Page not found." / "Página não encontrada."
```

### 🔐 Password Reset

```
resetpassword                       → "Reset Password" / "Resetar Senha"
newpassword                         → "New Password" / "Nova Senha"
confirmpassword                     → "Confirm Password" / "Confirmar Senha"
passwordmustmatch                   → "The passwords do not match. Please try again." / 
                                      "As senhas não coincidem. Por favor, tente novamente."
passwordempty                       → "The password field cannot be empty." / 
                                      "O campo de senha não pode estar vazio."
passwordchanged                     → "Password changed successfully!" / "Senha alterada com sucesso!"
passwordpolicyerror                 → "The password does not meet password policy requirements." / 
                                      "A senha não atende aos requisitos de política de senha."
passwordresetalert                  → "Alert" / "Alerta"
resetpasswordtitle                  → "Reset User Password" / "Resetar Senha do Usuário"
passwordresetsuccess                → "Password changed successfully!" / "Senha alterada com sucesso!"
passwordresetfailed                 → "Error resetting password. Please try again." / 
                                      "Erro ao resetar a senha. Por favor, tente novamente."
modalopenerror                      → "Error opening password reset modal." / 
                                      "Erro ao abrir o modal de resetar senha."
stringsloaderror                    → "Error loading language strings." / "Erro ao carregar strings de idioma."
```

### 📋 Validation Errors - Prices

```
errorcategoryid                     → "Category ID is required" / "ID da categoria é obrigatório"
errorcategorynotfound               → "Category not found" / "Categoria não encontrada"
errorname                           → "Price name is required" / "Nome do preço é obrigatório"
errornametoolong                    → "Price name must not exceed 255 characters" / 
                                      "Nome do preço não deve exceder 255 caracteres"
errorprice                          → "Price value is required" / "Valor do preço é obrigatório"
errorpriceinvalid                   → "Price must be a valid positive number" / 
                                      "Preço deve ser um número positivo válido"
errorstartdateinvalid               → "Start date must be a valid timestamp" / 
                                      "Data de início deve ser um timestamp válido"
errorenddateinvalid                 → "End date must be a valid timestamp" / 
                                      "Data de fim deve ser um timestamp válido"
errordaterange                      → "Start date must be before end date" / 
                                      "Data de início deve ser anterior à data de fim"
errordateoverlap                    → "This price period overlaps with an existing active price" / 
                                      "Este período de preço sobrepõe um preço ativo existente"
errorinstallments                   → "Number of installments must be between 0 and 12" / 
                                      "Número de parcelas deve estar entre 0 e 12"
errorstatus                         → "Status must be 0 or 1" / "Status deve ser 0 ou 1"
errorispromotional                  → "Promotional flag must be 0 or 1" / "Flag promocional deve ser 0 ou 1"
errorisenrollmentfee                → "Enrollment fee flag must be 0 or 1" / 
                                      "Flag de taxa de inscrição deve ser 0 ou 1"
```

### 🎨 UI/Card Descriptions

```
dashboard_desc                      → "Access the administrative dashboard..." / 
                                      "Acesse o painel administrativo..."
settings_desc                       → "Configure and manage administrative settings..." / 
                                      "Configure e gerencie as configurações administrativas..."
courses_desc                        → "Manage and monitor all courses in the system" / 
                                      "Gerencie e monitore todos os cursos do sistema"
no_admin_tools                      → "No administrative tools are currently available..." / 
                                      "Nenhuma ferramenta administrativa está disponível..."
```

### 🔗 Buttons & Links

```
open_dashboard                      → "Open Dashboard" / "Abrir Painel"
open_settings                       → "Open Settings" / "Abrir Configurações"
open_courses                        → "Open Courses" / "Abrir Cursos"
back                                → "Back" / "Voltar"
```

### 📊 Course Statistics

```
total_courses                       → "Total Courses" / "Total de Cursos"
visible_courses                     → "Visible Courses" / "Cursos Visíveis"
hidden_courses                      → "Hidden Courses" / "Cursos Ocultos"
create_course                       → "Create Course" / "Criar Curso"
create_course_desc                  → "Create a new course in the system" / 
                                      "Criar um novo curso no sistema"
manage_categories                   → "Manage Categories" / "Gerenciar Categorias"
manage_categories_desc              → "Organize courses into categories" / 
                                      "Organizar cursos em categorias"
course_backups                      → "Course Backups" / "Backups de Cursos"
course_backups_desc                 → "Restore courses from backup files" / 
                                      "Restaurar cursos de arquivos de backup"
```

### 🏷️ Form Labels (From core Moodle)

These are pulled from Moodle core but can be overridden:
```
categoryname                        → From 'core' (usually)
required                            → From 'core'
maximumchars                        → From 'core'
top                                 → From 'core'
savechanges                         → From 'core'
createcategory                      → From 'core'
forceno                             → From 'core'
```

### 🔒 Privacy

```
privacy:metadata                    → "The Local Custom Admin plugin does not store any personal data." / 
                                      "O plugin Admin Personalizado Local não armazena dados pessoais."
```

## Uso nos Arquivos

### form_categoria.php
- `edit_category`
- `add_category`
- `categoryname` (core)
- `required` (core)
- `maximumchars` (core)
- `top` (core)
- `categoryparent` (core)
- `categorydescription` (core)
- `categoryimage` (core)
- `forceno` (core)
- `categorytheme` (core)
- `savechanges` (core)
- `createcategory` (core)
- `categoryduplicate` (core)
- `categoryupdated` (core)
- `categorycreated` (core)
- `back`
- `categories`

### categorias.php
- `courses`
- `categories`
- `categories_management`
- `add_category`
- `edit_category`

### reset_password.js (AMD Module)
- `resetpassword`
- `newpassword`
- `confirmpassword`
- `passwordmustmatch`
- `passwordempty`
- `passwordresetsuccess`
- `passwordresetfailed`

### price_handler.php (Webservice)
- `pricecreatorsuccess`
- `priceupdatesuccess`
- `pricedeletesuccess`
- `pricedeletefailed`
- `errorcategoryid`, `errorname`, `errorprice`, etc.

## Total de Strings

- **English (en)**: 79 strings
- **Portuguese BR (pt_BR)**: 79 strings

## Como Adicionar Novas Strings

Ao adicionar novas funcionalidades:

1. Adicione a string no arquivo English primeiro
2. Adicione a tradução correspondente no arquivo Portuguese BR
3. Use o padrão: `get_string('string_key', 'local_localcustomadmin')`
4. Sempre traduzir chaves descritivas (não abreviar)
5. Manter consistência com strings existentes
6. Para strings de erro, usar prefixo `error`
7. Para strings de sucesso, usar sufixo `success`

## Exemplo

```php
// Adicionar nova string
$string['mynewstring'] = 'My New String';

// Usar no código
echo get_string('mynewstring', 'local_localcustomadmin');
```

---

**Última atualização**: 2025-10-18  
**Commit**: 7e7584e  
**Total de commits com strings**: 5
