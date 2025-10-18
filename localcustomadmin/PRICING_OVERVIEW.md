# 📊 Classes de Manipulação de Preços - Resumo Visual

## Arquitetura

```
┌─────────────────────────────────────────────────────────────────┐
│                      WEBSERVICE API                              │
│              (price_handler.php)                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ GET:   get_category_prices                               │   │
│  │ WRITE: create_category_price                             │   │
│  │ WRITE: update_category_price                             │   │
│  │ WRITE: delete_category_price                             │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                             │
                ┌────────────┴─────────────┐
                │                          │
        ┌───────▼──────────┐      ┌───────▼─────────────┐
        │ VALIDAÇÃO        │      │ BANCO DE DADOS      │
        │ (validator)      │      │ (manager)           │
        │ ┌──────────────┐ │      │ ┌──────────────────┐│
        │ │ validate()   │ │      │ │ create()         ││
        │ │ sanitize()   │ │      │ │ update()         ││
        │ │ check_overlap│ │      │ │ get()            ││
        │ └──────────────┘ │      │ │ get_prices()     ││
        │                  │      │ │ get_active_price()││
        └──────────────────┘      │ │ delete()         ││
                                  │ │ get_stats()      ││
                                  │ └──────────────────┘│
                                  └────────────────────┘
                                           │
                                  ┌────────▼────────┐
                                  │  BD Table       │
                                  │ (install.xml)   │
                                  │                 │
                                  │ category_prices │
                                  └─────────────────┘
```

## 📁 Estrutura de Arquivos

```
localcustomadmin/
├── classes/
│   ├── category_price_manager.php         ⭐ Manager CRUD
│   ├── category_price_validator.php       ⭐ Validações
│   └── webservice/
│       ├── user_handler.php               (existente)
│       └── price_handler.php              ⭐ APIs Webservice
├── db/
│   ├── install.xml                        (tabela)
│   └── services.php                       (registros)
├── lang/
│   ├── en/
│   │   └── local_localcustomadmin.php     (strings EN)
│   └── pt_br/
│       └── local_localcustomadmin.php     (strings pt_BR)
└── PRICING_CLASSES.md                     (documentação)
```

## 🔌 Métodos Disponíveis

### Manager (CRUD)

```
category_price_manager::
├── create(data)                    → ID
├── update(id, data)                → bool
├── get(id)                         → object|false
├── get_category_prices(cat, active) → array
├── get_active_price(cat, ts)       → object|null
├── delete(id)                      → bool
├── delete_category_prices(cat)     → bool
├── get_prices(filters, from, num)  → array
├── count_prices(filters)           → int
├── enable(id)                      → bool
├── disable(id)                     → bool
└── get_category_stats(cat)         → object (stats)
```

### Validator

```
category_price_validator::
├── validate(data)                     → array (erros)
├── category_exists(id)                → bool
├── check_date_overlap(...)            → bool
├── sanitize(data)                     → object
└── validate_complete(data, excludeid) → array (erros)
```

### Webservices

```
Método                          Tipo    Retorno
────────────────────────────────────────────────────
get_category_prices             READ    []prices
create_category_price           WRITE   {price, msg}
update_category_price           WRITE   {price, msg}
delete_category_price           WRITE   {success, msg}
```

## 🔐 Segurança

```
Request AJAX
    ↓
[1] Validação de Parâmetros (Moodle validate_parameters)
    ↓
[2] Verificação de Contexto (context_system)
    ↓
[3] Verificação de Capacidade (local/localcustomadmin:manage)
    ↓
[4] Sanitização de Dados (category_price_validator::sanitize)
    ↓
[5] Validação Completa (category_price_validator::validate_complete)
    ↓
[6] Operação de BD (category_price_manager)
    ↓
Response JSON
```

## 📊 Fluxo de Dados - Criar Preço

```
FRONTEND (AJAX)
    │
    ├─ categoryid: 5
    ├─ name: "Preço Regular"
    ├─ price: 99.99
    └─ status: 1
    │
    ▼
WEBSERVICE: create_category_price()
    │
    ├─ validate_parameters() ✓
    ├─ validate_context() ✓
    ├─ has_capability() ✓
    │
    ├─ Instancia data object
    │
    ▼ price_handler::create_category_price()
    │
    ├─ sanitize(data)
    │   └─ categoryid: (int)5
    │   └─ name: trim/substr
    │   └─ price: (float)99.99
    │
    ├─ validate_complete(data)
    │   ├─ validate() - campos básicos ✓
    │   ├─ category_exists() ✓
    │   └─ check_date_overlap() ✓
    │
    ├─ category_price_manager::create(data)
    │   └─ DB INSERT
    │   └─ Return ID
    │
    ├─ Get created price
    │
    ▼ RESPONSE
    {
        "id": 1,
        "categoryid": 5,
        "name": "Preço Regular",
        "price": 99.99,
        "status": 1,
        "success": true,
        "message": "Price created successfully!"
    }
```

## 🌍 Strings de Idioma Adicionadas

### Português (pt_BR)
```
categoryprices          → "Preços de Categorias"
add_price              → "Adicionar Preço"
pricename              → "Nome do Preço"
pricevalue             → "Valor do Preço"
pricecreatorsuccess    → "Preço criado com sucesso!"
errordateoverlap       → "Este período sobrepõe um preço ativo"
```

### English (en)
```
categoryprices          → "Category Prices"
add_price              → "Add Price"
pricename              → "Price Name"
pricevalue              → "Price Value"
pricecreatorsuccess    → "Price created successfully!"
errordateoverlap       → "This price period overlaps with an existing active price"
```

## 💾 Schema da Tabela

```sql
CREATE TABLE local_customadmin_category_prices (
    id int(10) PRIMARY KEY AUTO_INCREMENT,
    categoryid int(10) NOT NULL,
    name varchar(255) NOT NULL,
    price float NOT NULL,
    startdate int(10) NOT NULL,
    enddate int(10) NOT NULL,
    ispromotional int(1) DEFAULT 0,
    isenrollmentfee int(1) DEFAULT 0,
    status int(1) DEFAULT 1,
    scheduledtask int(1) DEFAULT 0,
    installments int(2) DEFAULT 0,
    timecreated int(10) DEFAULT 0,
    timemodified int(10) DEFAULT 0,
    
    FOREIGN KEY (categoryid) REFERENCES course_categories(id),
    INDEX categoryid_idx (categoryid),
    INDEX status_idx (status)
);
```

## 🧪 Exemplos de Uso

### Criar via Backend

```php
use local_localcustomadmin\category_price_manager;
use local_localcustomadmin\category_price_validator;

$data = (object) [
    'categoryid' => 5,
    'name' => 'Promoção de Verão',
    'price' => 49.99,
    'startdate' => time(),
    'enddate' => time() + (90 * 24 * 60 * 60), // 90 dias
    'ispromotional' => 1,
    'status' => 1
];

// Sanitizar
$data = category_price_validator::sanitize($data);

// Validar
$errors = category_price_validator::validate_complete($data);
if (empty($errors)) {
    $id = category_price_manager::create($data);
    echo "Preço criado: ID $id";
} else {
    echo "Erros: " . implode(', ', $errors);
}
```

### Obter Preço Ativo

```php
$active = category_price_manager::get_active_price(5);
if ($active) {
    echo "Preço vigente: R$ {$active->price}";
    echo "Válido até: " . date('d/m/Y', $active->enddate);
} else {
    echo "Sem preço ativo";
}
```

### Listar com Filtros

```php
$filters = [
    'categoryid' => 5,
    'status' => 1,
    'ispromotional' => 0
];

$prices = category_price_manager::get_prices($filters, 0, 10);
foreach ($prices as $price) {
    echo "{$price->name}: R$ {$price->price}\n";
}
```

### Stats da Categoria

```php
$stats = category_price_manager::get_category_stats(5);
echo "Total: {$stats->total}";
echo "Ativos: {$stats->active}";
echo "Preço médio: R$ {$stats->average}";
```

## ✅ Checklist de Implementação

- ✅ `category_price_manager.php` - CRUD completo
- ✅ `category_price_validator.php` - Validações robustas
- ✅ `price_handler.php` - 4 webservices registrados
- ✅ `db/services.php` - Registros das APIs
- ✅ Strings de idioma (EN + pt_BR)
- ✅ Documentação em PRICING_CLASSES.md
- ✅ Git commits com mensagens descritivas
- ⏳ Interface de administração (próximo)
- ⏳ AMD modules frontend (próximo)
- ⏳ Templates Mustache (próximo)

## 🚀 Próximas Etapas

1. **Admin Interface** - Criar página CRUD visual
2. **AMD Modules** - Integração JavaScript
3. **Integração com Enrollment** - Usar preços ativos
4. **Relatórios** - Dashboard de preços

---

**Versão**: 2025101801  
**Commits**: 2 (c289df5, 743e949)  
**Linhas de código**: ~700 (classes) + ~40 (documentação)
