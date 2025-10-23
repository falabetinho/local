# Visualizador de Mapeamentos WordPress

## 📋 Visão Geral

Esta funcionalidade permite visualizar todos os mapeamentos de sincronização entre o Moodle e o WordPress, mostrando quais categorias e cursos estão conectados com termos e posts do WordPress.

## 🎯 Características

### Painel de Estatísticas
- **Total de mapeamentos** cadastrados
- Quantidade de **categorias** sincronizadas
- Quantidade de **cursos** sincronizados
- Status: **Sincronizados**, **Pendentes** e **Erros**

### Filtros Disponíveis
1. **Por Tipo:**
   - Todos
   - Categorias (category)
   - Cursos (course)

2. **Por Status:**
   - Todos
   - Sincronizados (synced)
   - Pendentes (pending)
   - Com erro (error)

### Tabela de Mapeamentos
Exibe informações detalhadas:
- **ID do mapeamento**
- **Tipo** (categoria ou curso)
- **Nome do item no Moodle** (com ID e IDNumber)
- **ID e tipo do item no WordPress** (taxonomy/post_type)
- **Status da sincronização** (com badge colorido)
- **Data da última sincronização**
- **Botão de detalhes** (expandível)

### Detalhes Expandidos
Ao clicar no botão de detalhes (ℹ️):
- **Para itens com erro**: Mensagem completa do erro
- **Para itens sincronizados**: Datas de criação e modificação

## 📁 Arquivos Criados

### 1. wordpress_mappings.php (180 linhas)
**Localização:** `/local/localcustomadmin/wordpress_mappings.php`

**Responsabilidades:**
- Verificação de permissões (`local/localcustomadmin:manage`)
- Configuração da página (título, breadcrumb)
- Consulta SQL com JOINs para obter nomes dos itens
- Processamento de filtros (type e status)
- Cálculo de estatísticas
- Formatação de dados para o template
- Renderização do template Mustache

**SQL Query:**
```php
SELECT m.*, 
       CASE WHEN m.moodle_type = 'category' THEN cc.name
            WHEN m.moodle_type = 'course' THEN c.fullname
       END as moodle_name
FROM {local_customadmin_wp_mapping} m
LEFT JOIN {course_categories} cc ON m.moodle_type = 'category' AND m.moodle_id = cc.id
LEFT JOIN {course} c ON m.moodle_type = 'course' AND m.moodle_id = c.id
```

### 2. wordpress_mappings.mustache (223 linhas)
**Localização:** `/local/localcustomadmin/templates/wordpress_mappings.mustache`

**Componentes:**
- Cards de estatísticas com cores personalizadas
- Formulário de filtros com dropdowns
- Tabela responsiva com dados dos mapeamentos
- Linhas expansíveis para detalhes/erros
- CSS inline para estilização
- Alertas para situação sem mapeamentos

### 3. Strings de Idioma

**Português (PT-BR) - 12 novas strings:**
```php
$string['wordpress_mappings'] = 'Mapeamentos WordPress';
$string['synced'] = 'Sincronizado';
$string['pending'] = 'Pendente';
$string['notfound'] = 'Não encontrado';
$string['type'] = 'Tipo';
$string['last_sync'] = 'Última Sincronização';
$string['showdetails'] = 'Mostrar Detalhes';
$string['error_message'] = 'Mensagem de Erro';
$string['no_mappings_found'] = 'Nenhum Mapeamento Encontrado';
$string['no_mappings_found_desc'] = 'Não há mapeamentos...';
$string['timecreated'] = 'Data de Criação';
$string['timemodified'] = 'Data de Modificação';
```

**Inglês (EN) - 12 novas strings:**
```php
$string['wordpress_mappings'] = 'WordPress Mappings';
$string['synced'] = 'Synced';
$string['pending'] = 'Pending';
// ... (equivalentes em inglês)
```

### 4. Atualização do Link
**Arquivo:** `wordpress_integration_categories.php` (linha 120)

**ANTES:**
```php
'url' => '#', // TODO: Create mappings page
```

**DEPOIS:**
```php
'url' => new moodle_url('/local/localcustomadmin/wordpress_mappings.php'),
```

## 🔗 Acesso à Funcionalidade

### Caminho de Navegação:
1. **Administração do Site**
2. **Plugins → Local → Custom Admin**
3. **Integração WordPress → Categorias**
4. **Botão "Ver Mapeamentos"**

### URL Direta:
```
https://seu-moodle.com/local/localcustomadmin/wordpress_mappings.php
```

### Parâmetros URL:
- `type` - Filtro de tipo: `all`, `category`, `course`
- `status` - Filtro de status: `all`, `synced`, `pending`, `error`

**Exemplos:**
```
?type=category&status=synced   # Apenas categorias sincronizadas
?type=course&status=error      # Apenas cursos com erro
?status=pending                # Todos os itens pendentes
```

## 🎨 Interface

### Status Badges (Cores)
- **Sincronizado**: Badge verde (`badge-success`)
- **Pendente**: Badge amarelo (`badge-warning`)
- **Erro**: Badge vermelho (`badge-danger`)
- **Tipo**: Badge azul (`badge-info`)

### Cards de Estatísticas
- **Total**: Card cinza neutro
- **Categorias**: Card neutro
- **Cursos**: Card neutro
- **Sincronizados**: Card com borda verde
- **Pendentes**: Card com borda amarela
- **Erros**: Card com borda vermelha

### Ícones
- 📊 Estatísticas
- 🔍 Filtros
- 📋 Tabela
- ℹ️ Detalhes
- ⚠️ Erro
- ✓ Sincronizado

## 📊 Consulta de Dados

### Tabela Base
`mdl_local_customadmin_wp_mapping`

### Campos Retornados:
```sql
id                  -- ID do mapeamento
moodle_type         -- 'category' ou 'course'
moodle_id           -- ID no Moodle
moodle_name         -- Nome (via JOIN)
wordpress_type      -- 'term' ou 'post'
wordpress_id        -- ID no WordPress
wordpress_taxonomy  -- Ex: 'nivel', 'category'
wordpress_post_type -- Ex: 'curso', 'post'
sync_status         -- 'synced', 'pending', 'error'
last_synced         -- Timestamp
sync_error          -- Mensagem de erro (se houver)
timecreated         -- Data de criação
timemodified        -- Data de modificação
```

### Condições WHERE Dinâmicas:
```php
// Filtro de tipo
if ($type !== 'all') {
    $params['moodle_type'] = $type;
}

// Filtro de status
if ($status !== 'all') {
    $params['sync_status'] = $status;
}
```

## 🔐 Segurança

### Verificações Implementadas:
1. **Autenticação**: `require_login()`
2. **Capacidade**: `require_capability('local/localcustomadmin:manage', $context)`
3. **Contexto do Sistema**: `context_system::instance()`
4. **Parâmetros Sanitizados**: `optional_param(..., PARAM_ALPHA)`
5. **SQL Seguro**: Uso de placeholders e parâmetros preparados

## 🧪 Teste da Funcionalidade

### 1. Preparação
```bash
# Certifique-se de que a tabela existe
php admin/cli/upgrade.php

# Limpe o cache
php admin/cli/purge_caches.php
```

### 2. Criar Dados de Teste
```sql
-- Inserir mapeamento de teste
INSERT INTO mdl_local_customadmin_wp_mapping 
(moodle_type, moodle_id, wordpress_type, wordpress_id, 
 wordpress_taxonomy, sync_status, last_synced, timecreated, timemodified)
VALUES 
('category', 1, 'term', 10, 'nivel', 'synced', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('category', 2, 'term', 11, 'nivel', 'pending', NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('course', 1, 'post', 20, NULL, 'error', NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Atualizar erro no último registro
UPDATE mdl_local_customadmin_wp_mapping 
SET sync_error = 'HTTP 401: Sorry, you are not allowed to create posts'
WHERE id = (SELECT MAX(id) FROM mdl_local_customadmin_wp_mapping);
```

### 3. Acessar a Página
1. Login como administrador
2. Navegue para a página de mapeamentos
3. Teste os filtros:
   - Filtre por "Categorias"
   - Filtre por "Erros"
   - Combine filtros
4. Clique no botão ℹ️ para expandir detalhes
5. Verifique as estatísticas no topo

### 4. Validações
- [ ] Estatísticas batem com os dados reais
- [ ] Filtros funcionam corretamente
- [ ] Nome da categoria/curso aparece corretamente
- [ ] Badges de status têm cores corretas
- [ ] Detalhes expandem/colapsam
- [ ] Mensagens de erro são exibidas
- [ ] Página funciona sem mapeamentos (mensagem informativa)

## 🐛 Troubleshooting

### Problema: Página em branco
**Solução:**
```bash
# Habilitar debug
php admin/cli/cfg.php --name=debug --set=32767
php admin/cli/cfg.php --name=debugdisplay --set=1

# Verificar logs
tail -f /var/log/apache2/error.log
```

### Problema: Template não encontrado
**Solução:**
```bash
# Limpar cache de templates
php admin/cli/purge_caches.php

# Verificar permissões
chmod 644 templates/wordpress_mappings.mustache
```

### Problema: Nomes não aparecem
**Verificação:**
```sql
-- Verificar se os IDs existem
SELECT m.*, cc.name as cat_name, c.fullname as course_name
FROM mdl_local_customadmin_wp_mapping m
LEFT JOIN mdl_course_categories cc ON m.moodle_type = 'category' AND m.moodle_id = cc.id
LEFT JOIN mdl_course c ON m.moodle_type = 'course' AND m.moodle_id = c.id;
```

### Problema: Filtros não funcionam
**Debug:**
```php
// Adicionar no início de wordpress_mappings.php
var_dump($type, $status, $params);
die();
```

## 📝 Manutenção Futura

### Melhorias Possíveis:

1. **Paginação**
   ```php
   $page = optional_param('page', 0, PARAM_INT);
   $perpage = 50;
   $records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
   ```

2. **Ordenação**
   ```php
   $sort = optional_param('sort', 'id', PARAM_ALPHA);
   $dir = optional_param('dir', 'DESC', PARAM_ALPHA);
   $sql .= " ORDER BY $sort $dir";
   ```

3. **Ações em Lote**
   - Re-sincronizar selecionados
   - Deletar mapeamentos antigos
   - Exportar para CSV

4. **Gráficos**
   - Pizza chart com estatísticas
   - Linha do tempo de sincronizações
   - Taxa de sucesso/falha

## 📚 Referências

- **Moodle Database API**: https://docs.moodle.org/dev/Data_manipulation_API
- **Mustache Templates**: https://docs.moodle.org/dev/Templates
- **Capability System**: https://docs.moodle.org/dev/Capability
- **SQL Queries**: https://docs.moodle.org/dev/Data_definition_API

## ✅ Checklist de Implementação

- [x] Criar wordpress_mappings.php (backend PHP)
- [x] Criar wordpress_mappings.mustache (template)
- [x] Adicionar strings PT-BR
- [x] Adicionar strings EN
- [x] Atualizar link em wordpress_integration_categories.php
- [x] Remover TODO comment
- [x] Testar sem erros de sintaxe
- [x] Documentar funcionalidade

## 🎉 Status

**✅ IMPLEMENTAÇÃO COMPLETA**

A funcionalidade "Ver Mapeamentos" está totalmente implementada e pronta para uso. Todos os arquivos foram criados, as strings de idioma foram adicionadas e o link foi ativado na página de integração WordPress.

---

**Data de Implementação:** 2025
**Versão do Plugin:** 2025102204+
**Autor:** Local Custom Admin Team
