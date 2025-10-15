# Gerenciamento de Categorias - Nova Funcionalidade

## Resumo da Implementação

A nova página de **Gerenciamento de Categorias** foi criada seguindo os requisitos especificados, proporcionando uma interface completa para administrar categorias de cursos no Moodle.

## Arquivos Criados/Modificados:

### 1. categorias.php
- **Localização**: `/local/localcustomadmin/categorias.php`
- **Funcionalidade**: Página principal de gerenciamento de categorias
- **Layout**: Utiliza template base do Moodle
- **Breadcrumb**: /cursos/categorias
- **Segurança**: Requer capacidade `local/localcustomadmin:manage`

### 2. templates/categorias.mustache
- **Localização**: `/local/localcustomadmin/templates/categorias.mustache`
- **Componentes**:
  - Botão "Adicionar Categoria"
  - Tabela responsiva com categorias
  - Colunas: ID, Nome, Cursos, Subcategorias, Ações
  - Botões de ação: Exibir Subcategorias, Editar Categoria

### 3. Strings de Idioma Adicionadas
No arquivo `lang/en/local_localcustomadmin.php`:
- `categories`: Categories
- `categories_management`: Categories Management
- `categories_management_desc`: Manage course categories...
- `add_category`: Add Category
- `edit_category`: Edit Category
- `view_subcategories`: View Subcategories

## Funcionalidades Implementadas:

### ✅ Requisitos Atendidos:

1. **Card alterado**: O card "Manage Categories" agora direciona para `categorias.php`
2. **Template base**: Utiliza layout base do Moodle
3. **Breadcrumb**: Implementado /cursos/categorias
4. **Botão Adicionar**: Leva para `/course/editcategory.php`
5. **Tabela completa** com:
   - ID da categoria (badge)
   - Nome e caminho da categoria
   - Número de cursos (badge azul)
   - Número de subcategorias (badge amarelo)
6. **Coluna de ações**:
   - Botão "Exibir Subcategorias" (desabilitado, conforme solicitado)
   - Botão "Editar Categoria" (funcional, leva para edição)

### 📊 Informações Técnicas:

**Query SQL**: Busca todas as categorias com contadores automáticos:
```sql
SELECT cc.id, cc.name, cc.description, cc.parent, cc.coursecount, cc.depth, cc.path,
       (SELECT COUNT(*) FROM {course_categories} sub WHERE sub.parent = cc.id) as subcategories_count,
       (SELECT COUNT(*) FROM {course} c WHERE c.category = cc.id) as courses_count
FROM {course_categories} cc
ORDER BY cc.sortorder ASC
```

**Recursos Visuais**:
- Badges coloridos para contadores
- Hierarquia visual para categorias/subcategorias
- Responsividade para diferentes tamanhos de tela
- Ícones FontAwesome para melhor UX
- Estados visuais para botões desabilitados

## Navegação:

1. **Acesso**: /local/localcustomadmin/cursos.php → Card "Manage Categories"
2. **Breadcrumb**: Local Custom Admin → Courses → Categories
3. **Retorno**: Botão "Voltar para Cursos"

## URLs Utilizadas:

- **Adicionar Categoria**: `/course/editcategory.php` (Moodle nativo)
- **Editar Categoria**: `/course/editcategory.php?id={category_id}` (Moodle nativo)
- **Voltar**: `/local/localcustomadmin/cursos.php`

## Status dos Botões:

- ✅ **Adicionar Categoria**: Funcional (leva para criação no Moodle)
- ✅ **Editar Categoria**: Funcional (leva para edição no Moodle) 
- ⚪ **Exibir Subcategorias**: Desabilitado (conforme solicitado)

## Versão do Plugin:
- **Anterior**: 2025101402
- **Atual**: 2025101403

Data de Implementação: 14 de Outubro de 2025