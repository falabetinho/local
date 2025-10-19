# Guia de Instalação e Configuração

## Pré-requisitos

- Moodle 3.9 ou superior
- Plugin `local_localcustomadmin` já instalado
- Plugins de inscrição habilitados:
  - `enrol_fee` (Fee-based enrollment)
  - `enrol_manual` (Manual enrollment)
- Tabela `mdl_local_customadmin_category_prices` criada

## Passo 1: Copiar Arquivos

Copiar os seguintes arquivos para a instalação Moodle:

```bash
# Formulário
localcustomadmin/form_curso.php

# Página de edição
localcustomadmin/edit_curso.php

# Classe gerenciadora
localcustomadmin/classes/course_manager.php
localcustomadmin/classes/examples/course_manager_examples.php

# Scripts JavaScript
localcustomadmin/amd/src/course_form_tabs.js

# Atualizar strings de idioma
localcustomadmin/lang/en/local_localcustomadmin.php
localcustomadmin/lang/pt_br/local_localcustomadmin.php

# Atualizar estilos
localcustomadmin/styles/styles.css

# Atualizar página de cursos
localcustomadmin/cursos.php

# Documentação
localcustomadmin/COURSE_FORM_GUIDE.md
localcustomadmin/TECHNICAL_DOCUMENTATION.md
localcustomadmin/IMPLEMENTATION_CHECKLIST.md
```

## Passo 2: Compilar Assets (Opcional)

Se você modificar o JavaScript, compile para versão minificada:

```bash
cd /path/to/moodle
php local/localcustomadmin/build_amd.php
```

Ou use Grunt se configurado no projeto:
```bash
cd local/localcustomadmin
grunt build
```

## Passo 3: Verificar Permissões

Certifique-se de que os usuários têm capability:

```
local/localcustomadmin:manage
```

Para atribuir a um papel (role), vá em:
- Administração > Papéis e Permissões > Definir Papéis
- Localize `local/localcustomadmin:manage`
- Atribua ao papel desejado (ex: Manager)

## Passo 4: Configurar Plugins de Inscrição

### Habilitar enrol_fee

1. Vá em Administração > Plugins > Plugins de Inscrição
2. Localize "Fee-based enrollment"
3. Clique em ícone de olho para habilitar
4. Configure conforme necessário

### Verificar enrol_manual

1. Vá em Administração > Plugins > Plugins de Inscrição
2. Verifique se "Manual enrollment" está habilitado
3. Este é geralmente habilitado por padrão

## Passo 5: Criar Dados de Teste

### Criar Preço de Categoria

```php
// Em um script de migração ou CLI

global $DB;

// Criar preço de categoria
$price = new stdClass();
$price->categoryid = 2;  // ID da categoria
$price->name = "Standard Course Price";
$price->price = 99.99;
$price->startdate = time();
$price->enddate = time() + (365 * 24 * 60 * 60); // 1 ano
$price->status = 1;  // Ativo
$price->isenrollmentfee = 1;
$price->ispromotional = 0;
$price->scheduledtask = 0;
$price->installments = 0;
$price->timecreated = time();
$price->timemodified = time();

$DB->insert_record('local_customadmin_category_prices', $price);
```

### Criar Categoria de Teste

```php
// Criar categoria
$categorydata = new stdClass();
$categorydata->name = "Test Courses";
$categorydata->parent = 0;
$categorydata->description = "Category for testing course form";

$category = core_course_category::create($categorydata);
echo "Categoria criada: ID " . $category->id;
```

## Passo 6: Testar Funcionalidade

### Teste 1: Acessar Formulário

1. Faça login como usuário com `local/localcustomadmin:manage`
2. Vá em Administração > Local Custom Admin > Cursos
3. Clique em "Adicionar Curso"
4. Formulário deve aparecer com duas abas

### Teste 2: Criar Novo Curso

1. Preencha campos na aba "Geral":
   - Nome completo: "Python for Beginners"
   - Nome abreviado: "pybegin101"
   - Categoria: Selecione categoria com preço ativo
   - Formato: "Topics"

2. Clique "Salvar"

3. Verifique:
   - Mensagem de sucesso aparece
   - Volta para página de cursos
   - Novo curso aparece na lista

### Teste 3: Verificar Enrollments

```php
// Via CLI ou script

$courseid = 123;  // ID do curso criado
$enrols = enrol_get_instances($courseid, true);

foreach ($enrols as $enrol) {
    $plugin = enrol_get_plugin($enrol->enrol);
    echo "Tipo: " . $enrol->enrol . "\n";
    echo "Status: " . ($enrol->status ? "Ativo" : "Inativo") . "\n";
    echo "Preço: " . (isset($enrol->cost) ? $enrol->cost : "N/A") . "\n";
}
```

Saída esperada:
```
Tipo: fee
Status: Ativo
Preço: 99.99

Tipo: manual
Status: Ativo
Preço: N/A
```

### Teste 4: Editar Curso

1. Vá em página de Cursos
2. Clique em "Editar" em um curso criado
3. Modifique dados na aba "Geral"
4. Clique em aba "Preço"
5. Deve aparecer tabela com métodos de inscrição
6. Salve e verifique atualização

## Solução de Problemas

### Problema: Formulário não aparece

**Possíveis causas**:
- Arquivo `form_curso.php` não está no local correto
- Permissão `local/localcustomadmin:manage` não atribuída

**Solução**:
```php
// Verificar permissão
$context = context_system::instance();
echo has_capability('local/localcustomadmin:manage', $context) ? 'SIM' : 'NÃO';

// Verificar arquivo
echo file_exists('/path/to/form_curso.php') ? 'Existe' : 'Não existe';
```

### Problema: Abas não funcionam

**Possíveis causas**:
- Script JavaScript não carregado
- Conflito com outro JavaScript

**Solução**:
1. Limpar cache do navegador
2. Executar: `php purge_caches.php`
3. Verificar console do navegador (F12) para erros

### Problema: Enrollments não criados

**Possíveis causas**:
- Plugin `enrol_fee` ou `enrol_manual` não habilitado
- Exceção na classe `course_manager`

**Solução**:
```php
// Verificar plugins
echo enrol_get_plugin('fee') ? 'fee OK' : 'fee NÃO';
echo enrol_get_plugin('manual') ? 'manual OK' : 'manual NÃO';

// Testar manualmente
try {
    \local_localcustomadmin\course_manager::initialize_course_enrolments(123);
    echo "Sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
```

### Problema: Preço não atualizado

**Possíveis causas**:
- Nenhum preço ativo para a categoria
- Data do preço fora do intervalo

**Solução**:
```php
// Verificar preço ativo
$price = \local_localcustomadmin\category_price_manager::get_active_price(2);
echo $price ? $price->price : 'Nenhum preço ativo';

// Verificar data
echo "Agora: " . date('Y-m-d H:i:s', time());
echo "Início: " . date('Y-m-d H:i:s', $price->startdate);
echo "Fim: " . date('Y-m-d H:i:s', $price->enddate);
```

## Comandos Úteis (CLI)

### Limpar caches
```bash
php cli/purge_caches.php
```

### Compilar assets AMD
```bash
php local/localcustomadmin/build_amd.php
```

### Resetar plugin (recria tabelas)
```bash
php admin/cli/uninstall_plugins.php --plugins=local_localcustomadmin
php admin/cli/install_plugins.php --plugins=local_localcustomadmin
```

## Logs para Monitorar

Verifique o arquivo de log do Moodle em `moodledata/debug/` para erros:

```
tail -f /path/to/moodledata/debug.log
```

Procure por erros contendo:
- "course_manager"
- "form_curso"
- "local_localcustomadmin"

## Backup e Recuperação

### Antes de implementar

1. Backup da base de dados:
```bash
mysqldump -u root -p moodle > moodle_backup.sql
```

2. Backup dos arquivos:
```bash
tar -czf moodle_backup.tar.gz /path/to/moodle/local/localcustomadmin/
```

### Rollback em caso de problema

1. Restaurar banco:
```bash
mysql -u root -p moodle < moodle_backup.sql
```

2. Restaurar arquivos:
```bash
tar -xzf moodle_backup.tar.gz -C /path/to/moodle/
```

3. Limpar caches:
```bash
php cli/purge_caches.php
```

## Performance

### Recomendações

1. **Índices de banco**: Garantir índices em:
   - `mdl_local_customadmin_category_prices.categoryid`
   - `mdl_local_customadmin_category_prices.status`
   - `mdl_enrol.courseid`
   - `mdl_enrol.enrol`

2. **Cache**: Habilitar cache de:
   - Categorias
   - Preços de categorias

3. **Monitoramento**: Monitorar queries lentas:
```bash
mysql> SET SESSION long_query_time = 2;
mysql> SET SESSION log_queries_not_using_indexes = 'ON';
```

## Segurança

### Checklist

- [ ] Capability `local/localcustomadmin:manage` atribuído apenas a admins
- [ ] Validação de entrada habilitada
- [ ] CSRF tokens habilitados (padrão Moodle)
- [ ] SQL injection prevention via prepared statements
- [ ] XSS prevention via format_string()

### Auditoria

Monitorar criação/edição de cursos:
```php
// Adicionar log customizado
function log_course_action($action, $courseid, $userid) {
    $log = new stdClass();
    $log->action = $action;
    $log->courseid = $courseid;
    $log->userid = $userid;
    $log->timecreated = time();
    
    $DB->insert_record('local_customadmin_course_log', $log);
}
```

## Suporte

Para problemas ou dúvidas:

1. Verificar documentação em `TECHNICAL_DOCUMENTATION.md`
2. Consultar exemplos em `classes/examples/course_manager_examples.php`
3. Revisar arquivo de log do Moodle

---

Fim do Guia de Instalação
