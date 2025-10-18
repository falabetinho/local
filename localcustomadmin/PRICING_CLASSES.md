# Category Price Management Classes

Este documento descreve as classes criadas para gerenciamento de preços de categorias no plugin `local_localcustomadmin`.

## Arquivos Criados

### 1. `classes/category_price_manager.php`

**Responsabilidade**: Gerenciamento de operações no banco de dados para preços de categorias.

**Métodos principais**:

- `create($data)` - Cria um novo preço de categoria
  - Valida campos obrigatórios
  - Define valores padrão
  - Retorna o ID do registro criado

- `update($id, $data)` - Atualiza um preço existente
  - Atualiza o campo `timemodified` automaticamente

- `get($id)` - Obtém um preço pelo ID

- `get_category_prices($categoryid, $activeonly)` - Lista preços de uma categoria
  - Pode filtrar apenas preços ativos
  - Ordenado por data de início (DESC)

- `get_active_price($categoryid, $timestamp)` - Obtém o preço ativo em determinado momento
  - Considera período de validade (startdate/enddate)
  - Útil para integração com sistema de pagamentos

- `delete($id)` - Deleta um preço

- `delete_category_prices($categoryid)` - Deleta todos os preços de uma categoria

- `get_prices($filters, $limitfrom, $limitnum)` - Lista com filtros avançados
  - Filtros: categoryid, status, ispromotional, isenrollmentfee
  - Suporta paginação

- `count_prices($filters)` - Conta preços com filtros

- `enable($id)` / `disable($id)` - Ativa/desativa um preço

- `get_category_stats($categoryid)` - Retorna estatísticas da categoria
  - Total de preços, preços ativos, preços promocionais
  - Preço médio, mínimo e máximo

**Uso**:
```php
use local_localcustomadmin\category_price_manager;

// Criar preço
$pricedata = (object) [
    'categoryid' => 5,
    'name' => 'Preço Regular',
    'price' => 99.99,
    'startdate' => time(),
    'enddate' => 0,  // Indefinido
    'status' => 1,
    'installments' => 3
];
$id = category_price_manager::create($pricedata);

// Obter preço ativo
$active = category_price_manager::get_active_price(5);

// Listar preços
$prices = category_price_manager::get_category_prices(5, true);
```

### 2. `classes/category_price_validator.php`

**Responsabilidade**: Validação de dados de preço de categoria.

**Métodos principais**:

- `validate($data)` - Valida dados básicos
  - Verifica campos obrigatórios
  - Valida tipos de dados (numérico, timestamp, etc)
  - Retorna array de erros (vazio se válido)

- `category_exists($categoryid)` - Verifica se categoria existe

- `check_date_overlap($categoryid, $startdate, $enddate, $excludeid)` - Detecta sobreposição de períodos
  - Útil para evitar preços conflitantes
  - Exclui ID específico (para atualizações)

- `sanitize($data)` - Limpa e normaliza dados
  - Converte tipos
  - Trunca strings longas
  - Converte booleanos para 0/1

- `validate_complete($data, $excludeid)` - Validação completa
  - Valida tudo
  - Verifica se categoria existe
  - Detecta sobreposições

**Uso**:
```php
use local_localcustomadmin\category_price_validator;

$data = (object) [
    'categoryid' => 5,
    'name' => 'Preço',
    'price' => 99.99
];

// Sanitizar
$data = category_price_validator::sanitize($data);

// Validar completo
$errors = category_price_validator::validate_complete($data);
if (!empty($errors)) {
    // Exibir erros
    foreach ($errors as $field => $error) {
        echo "$field: $error\n";
    }
}
```

### 3. `classes/webservice/price_handler.php`

**Responsabilidade**: APIs webservice para operações de preço.

**Métodos webservice (CRUD)**:

#### `get_category_prices($categoryid, $activeonly)`
- **Tipo**: READ
- **Retorna**: Lista de preços da categoria

#### `create_category_price($categoryid, $name, $price, ...)`
- **Tipo**: WRITE
- **Parâmetros opcionais**: startdate, enddate, ispromotional, isenrollmentfee, status, installments
- **Retorna**: Dados do preço criado + sucesso/mensagem

#### `update_category_price($id, $categoryid, ...)`
- **Tipo**: WRITE  
- **Todos os parâmetros exceto $id são opcionais**
- **Retorna**: Dados atualizados + sucesso/mensagem

#### `delete_category_price($id)`
- **Tipo**: WRITE
- **Retorna**: sucesso/mensagem

**Recursos de segurança**:
- Valida contexto do usuário
- Verifica capacidade `local/localcustomadmin:manage`
- Sanitiza dados de entrada
- Valida dados antes de operação
- Trata exceções apropriadamente

**Uso via AJAX (frontend)**:
```javascript
require(['core/ajax'], function(Ajax) {
    var promises = Ajax.call([{
        methodname: 'local_localcustomadmin_create_category_price',
        args: {
            categoryid: 5,
            name: 'Preço Regular',
            price: 99.99,
            status: 1
        }
    }]);
    
    promises[0].done(function(response) {
        console.log('Sucesso:', response.message);
    }).fail(function(error) {
        console.error('Erro:', error);
    });
});
```

## Fluxo de Validação

```
Dado do frontend
    ↓
validate_parameters (Moodle)
    ↓
sanitize() - Limpa dados
    ↓
validate_complete() - Validação completa
    ├─ validate() - Validações básicas
    ├─ category_exists() - Verifica categoria
    └─ check_date_overlap() - Detecta conflito
    ↓
create/update/delete - Operação BD
    ↓
Resposta ao frontend
```

## Schema da Tabela

```
local_customadmin_category_prices:
├─ id (int) - Chave primária
├─ categoryid (int) - Referência para course_categories
├─ name (varchar) - Nome do preço
├─ price (float) - Valor
├─ startdate (int) - Data início (timestamp)
├─ enddate (int) - Data fim (timestamp, 0 = indefinido)
├─ ispromotional (int) - Flag 0/1
├─ isenrollmentfee (int) - Flag 0/1
├─ status (int) - 0=Inativo, 1=Ativo
├─ scheduledtask (int) - Flag para tarefa agendada
├─ installments (int) - Número de parcelas (0-12)
├─ timecreated (int) - Timestamp criação
└─ timemodified (int) - Timestamp modificação
```

## Strings de Idioma

Todas as mensagens de erro e sucesso foram adicionadas aos arquivos de idioma:
- `lang/en/local_localcustomadmin.php`
- `lang/pt_br/local_localcustomadmin.php`

Strings incluem:
- Labels: pricename, pricevalue, status, installments, etc
- Mensagens: pricecreatorsuccess, priceupdatesuccess, pricedeletesuccess
- Erros de validação: errorcategoryid, errorprice, errordaterange, etc

## Webservices Registrados

Em `db/services.php` foram registrados 5 novos webservices:

1. `local_localcustomadmin_get_category_prices` - READ
2. `local_localcustomadmin_create_category_price` - WRITE
3. `local_localcustomadmin_update_category_price` - WRITE
4. `local_localcustomadmin_delete_category_price` - WRITE

Todos requerem:
- Usuário autenticado
- Capacidade: `local/localcustomadmin:manage`
- Acesso via AJAX ativado

## Próximos Passos

1. **Interface de Administração**: Criar página HTML para CRUD de preços
2. **AMD Modules**: Módulos JavaScript para interação frontend
3. **Templates Mustache**: Templates para exibição de preços
4. **Integração de Pagamento**: Usar `get_active_price()` para cálculos de taxa

## Testes Recomendados

- [ ] Criar preço com dados válidos
- [ ] Validar sobreposição de datas
- [ ] Listar preços paginados
- [ ] Obter preço ativo por timestamp
- [ ] Atualizar parcialmente (alguns campos)
- [ ] Deletar preço
- [ ] Testar cada campo de validação
- [ ] Verificar permissões de acesso
