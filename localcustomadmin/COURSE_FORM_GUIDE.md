# Course Form Implementation Guide

## Overview

Este documento descreve a implementação do novo formulário de criar/editar cursos com suporte a duas abas: **Geral** e **Preço**.

## Arquivos Criados/Modificados

### Novos Arquivos

1. **`form_curso.php`** - Formulário principal com duas abas
   - Aba "Geral": campos para criar/editar curso
   - Aba "Preço": visualização de métodos de inscrição configurados

2. **`edit_curso.php`** - Página de processamento do formulário
   - Manipula criação e edição de cursos
   - Inicializa métodos de inscrição automaticamente

3. **`classes/course_manager.php`** - Classe gerenciadora de cursos
   - `initialize_course_enrolments()`: Inicializa inscrições com base em preços de categoria
   - `get_or_create_fee_enrolment()`: Cria inscrição tipo "fee"
   - `ensure_manual_enrolment()`: Garante que inscrição manual existe

4. **`amd/src/course_form_tabs.js`** - Script para gerenciar abas do formulário

### Arquivos Modificados

1. **`lang/en/local_localcustomadmin.php`**
   - Adicionadas strings para o formulário de cursos
   - Strings de abas e mensagens

2. **`lang/pt_br/local_localcustomadmin.php`**
   - Tradução das novas strings para português

3. **`styles/styles.css`**
   - Estilos para as abas e formulário
   - Design responsivo e moderno

4. **`cursos.php`**
   - Botão "Adicionar Curso" agora aponta para `edit_curso.php`

## Fluxo de Funcionamento

### 1. Criar um Novo Curso

```
[Página de Cursos] 
    ↓ (clica em "Adicionar Curso")
[edit_curso.php] 
    ↓ (carrega form_curso.php)
[Formulário com duas abas]
    ↓ (preenche Aba "Geral" e clica em Salvar)
[Moodle cria o curso com create_course()]
    ↓
[course_manager::initialize_course_enrolments() é chamado]
    ↓
[Busca preços ativos na tabela mdl_local_customadmin_category_prices]
    ↓
[Cria/atualiza inscrição tipo "fee" com o preço]
    ↓
[Cria inscrição manual (acesso livre)]
    ↓
[Volta para página de cursos com mensagem de sucesso]
```

### 2. Editar um Curso Existente

```
[Página de Cursos]
    ↓ (clica em "Editar" em um curso)
[edit_curso.php?id=123]
    ↓ (carrega dados do curso)
[Formulário com abas - Aba "Preço" agora mostra inscrições]
    ↓ (modifica dados e clica em Salvar)
[Moodle atualiza o curso com update_course()]
    ↓
[course_manager::initialize_course_enrolments() é chamado novamente]
    ↓
[Inscrições são atualizadas conforme necessário]
    ↓
[Volta com mensagem de sucesso]
```

## Estrutura do Formulário

### Aba "Geral" (General)
Campos nativos do Moodle para criar/editar curso:
- **Nome Completo (fullname)** - Nome do curso
- **Nome Abreviado (shortname)** - Identificador único
- **Categoria (category)** - Categoria do curso
- **Resumo (summary)** - Descrição do curso
- **Formato (format)** - Tipo de layout (Tópicos, Semanas, etc.)
- **Visível (visible)** - Se o curso aparece para alunos
- **Data de Início (startdate)** - Quando o curso começa

### Aba "Preço" (Pricing)
- Mostra lista de **Métodos de Inscrição** (Enrollment Methods)
- Exibe tipo de inscrição (fee, manual, etc.)
- Mostra status (ativo/inativo)
- Exibe preço configurado
- Opção para editar cada método (placeholder para future development)

## Inicialização Automática de Inscrições

Quando um curso é criado, a classe `course_manager` automaticamente:

1. **Busca preços ativos** da categoria do curso na tabela `mdl_local_customadmin_category_prices`

2. **Cria/atualiza inscrição "fee"**:
   - Se não existir inscrição tipo "fee", cria uma
   - Define o preço com base no preço ativo da categoria
   - Usa role "student" por padrão

3. **Garante inscrição manual**:
   - Se não existir, cria uma inscrição manual
   - Permite acesso livre para administradores/staff

## Strings de Idioma

### Inglês
```
$string['addcourse'] = 'Add Course';
$string['editcourse'] = 'Edit Course';
$string['coursecreated'] = 'Course created successfully';
$string['courseupdated'] = 'Course updated successfully';
$string['pricing'] = 'Pricing';
$string['course_enrolments_info'] = 'View and manage enrollment methods...';
$string['save_course_first'] = 'Please save the course first to manage enrollments.';
$string['enrolled_methods'] = 'Enrollment Methods';
$string['enrolment_method'] = 'Enrollment Method';
```

### Português
```
$string['addcourse'] = 'Adicionar Curso';
$string['editcourse'] = 'Editar Curso';
$string['coursecreated'] = 'Curso criado com sucesso';
$string['courseupdated'] = 'Curso atualizado com sucesso';
$string['pricing'] = 'Precificação';
$string['course_enrolments_info'] = 'Veja e gerencie os métodos de inscrição...';
$string['save_course_first'] = 'Por favor, salve o curso primeiro para gerenciar inscrições.';
$string['enrolled_methods'] = 'Métodos de Inscrição';
$string['enrolment_method'] = 'Método de Inscrição';
```

## Estilos CSS

Adicionados estilos para:
- **Abas**: `.local-customadmin-course-tabs`, `.nav-tabs`, `.nav-link`
- **Conteúdo das abas**: `.tab-content`, `.tab-pane`
- **Tabela de inscrições**: `.course-enrolments-section`, `.table`
- **Botões**: Styling para submit e cancel
- **Alertas**: Info e warning styles

## Funcionalidades Avançadas (Future Development)

1. **Editar preço de inscrição por curso**
   - Permitir ajustar preço de inscrição tipo "fee" na aba "Preço"
   - Sobrescrever preço da categoria por curso

2. **Configurar múltiplos métodos de inscrição**
   - Adicionar/remover métodos de inscrição
   - Configurar Paypal, transferência bancária, etc.

3. **Parcelamento**
   - Configurar número de parcelas por inscrição

## Requisitos

- Moodle 3.9+
- Plugins de inscrição nativo ("fee", "manual")
- Tabela `mdl_local_customadmin_category_prices` com dados de preços

## Permissões Requeridas

- `local/localcustomadmin:manage` - Para criar/editar cursos

## Próximas Etapas

1. Implementar edição de preço na aba "Preço"
2. Adicionar suporte para múltiplos métodos de inscrição
3. Criar interface para gerenciar parcelamento
4. Implementar validação de sobreposição de datas de preços
