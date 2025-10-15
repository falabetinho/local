# Página de Gestão de Cursos - cursos.php

## Visão Geral
A página `cursos.php` é uma nova funcionalidade do plugin Local Custom Admin que oferece uma interface centralizada para gestão e monitoramento de cursos no Moodle.

## Funcionalidades

### 📊 **Estatísticas de Cursos**
- **Total de Cursos**: Contagem total excluindo o curso do site
- **Cursos Visíveis**: Cursos ativos e disponíveis para estudantes
- **Cursos Ocultos**: Cursos em desenvolvimento ou arquivados

### 🎯 **Ações Rápidas** (apenas para usuários com capacidade de gestão)
1. **Criar Curso**: Link direto para criação de novo curso
2. **Gerenciar Categorias**: Acesso ao gerenciamento de categorias
3. **Backups de Curso**: Restaurar cursos de arquivos de backup

### 🏆 **Cursos Populares**
- Lista dos 10 cursos com mais matrículas
- Informações exibidas:
  - Nome completo e nome abreviado
  - Número de estudantes matriculados
  - Status de visibilidade
  - Links para visualizar/editar

## Layout e Design

### 🎨 **Layout Base do Moodle**
A página utiliza o layout `base` do Moodle, proporcionando:
- Cabeçalho padrão do Moodle
- Navegação breadcrumb
- Rodapé padrão
- Responsividade automática

### 📱 **Responsividade**
- **Desktop**: Cards em 3 colunas
- **Tablet**: Cards em 2 colunas  
- **Mobile**: Cards empilhados

## Template Mustache

### 📄 **Arquivo**: `templates/cursos.mustache`

### 🔧 **Variáveis de Contexto**:
```json
{
    "pagetitle": "Course Management",
    "statistics": [
        {
            "title": "Total Courses",
            "value": 25,
            "icon": "fa-graduation-cap",
            "variant": "primary"
        }
    ],
    "courses": [
        {
            "id": 1,
            "fullname": "Course Name",
            "shortname": "SHORT",
            "enrollments": 50,
            "visible": true,
            "courseurl": "/course/view.php?id=1",
            "editurl": "/course/edit.php?id=1",
            "status_class": "success",
            "status_text": "Visible"
        }
    ],
    "actions": [
        {
            "title": "Create Course",
            "description": "Create a new course",
            "url": "/course/edit.php",
            "icon": "fa-plus-circle",
            "variant": "primary"
        }
    ],
    "has_manage_capability": true
}
```

## Permissões e Segurança

### 🔐 **Capacidades Necessárias**
- **Visualização**: `local/localcustomadmin:view`
- **Gestão**: `local/localcustomadmin:manage` (para ações administrativas)

### 🛡️ **Controles de Segurança**
- Verificação de login obrigatória
- Validação de capacidades por contexto do sistema
- Links protegidos baseados em permissões

## Integração com o Sistema

### 🔗 **Navegação**
- Acessível via página principal do plugin (`index.php`)
- Breadcrumb: Local Custom Admin > Courses
- Link no card "Courses" da página inicial

### 📊 **Dados Utilizados**
- Tabela `course` do Moodle
- Tabela `enrol` para contagem de matrículas
- Tabela `user_enrolments` para estatísticas

### 🎯 **Consultas SQL**
```sql
-- Cursos populares com contagem de matrículas
SELECT c.id, c.fullname, c.shortname, c.visible, COUNT(ue.id) as enrollments
FROM {course} c
LEFT JOIN {enrol} e ON e.courseid = c.id
LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
WHERE c.id != ?
GROUP BY c.id, c.fullname, c.shortname, c.visible
ORDER BY enrollments DESC, c.fullname ASC
```

## Estilos CSS

### 🎨 **Classes Personalizadas**
- `.local-customadmin-cursos`: Container principal
- `.course-item`: Estilo para itens de curso
- `.quick-actions`: Seção de ações rápidas

### ✨ **Efeitos Visuais**
- Hover effects em cards
- Transições suaves
- Badges coloridos para status
- Ícones FontAwesome

## Strings de Idioma

### 🌐 **Strings Adicionadas**
```php
// Page titles
$string['courses_management'] = 'Courses Management';

// Course related strings
$string['total_courses'] = 'Total Courses';
$string['visible_courses'] = 'Visible Courses';
$string['hidden_courses'] = 'Hidden Courses';
$string['create_course'] = 'Create Course';
$string['create_course_desc'] = 'Create a new course in the system';
$string['manage_categories'] = 'Manage Categories';
$string['manage_categories_desc'] = 'Organize courses into categories';
$string['course_backups'] = 'Course Backups';
$string['course_backups_desc'] = 'Restore courses from backup files';
$string['courses_desc'] = 'Manage and monitor all courses in the system';
$string['open_courses'] = 'Open Courses';
```

## Como Usar

### 👤 **Para Administradores**
1. Acesse `/local/localcustomadmin/index.php`
2. Clique no card "Courses"
3. Visualize estatísticas e cursos populares
4. Use as ações rápidas para gestão

### 👁️ **Para Usuários com Acesso de Visualização**
1. Mesmo acesso inicial
2. Pode visualizar estatísticas
3. Pode acessar cursos (sem editar)
4. Não vê ações de gestão

## Próximas Melhorias

### 🚀 **Funcionalidades Futuras**
- Filtros por categoria de curso
- Busca de cursos por nome
- Relatórios de progresso
- Gráficos interativos
- Exportação de dados
- Gestão em lote de cursos