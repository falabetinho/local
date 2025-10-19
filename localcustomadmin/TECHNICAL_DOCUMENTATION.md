# Documentação Técnica - Formulário de Cursos com Abas

## 1. Visão Geral

O sistema implementa um formulário personalizado para criação e edição de cursos com integração automática de preços e métodos de inscrição. O formulário possui **duas abas principais**:

### Aba "Geral" (General)
- Campos nativa do Moodle para configurar o curso
- Nome completo, nome abreviado, categoria, descrição
- Formato do curso, visibilidade, data de início

### Aba "Preço" (Pricing)
- Visualiza métodos de inscrição configurados
- Mostra preços e status de cada método
- Permite gerenciar enrollments associados ao curso

---

## 2. Arquivos do Projeto

### Estrutura de Diretórios
```
localcustomadmin/
├── form_curso.php                          # Classe do formulário com abas
├── edit_curso.php                          # Página de processamento
├── classes/
│   ├── course_manager.php                  # Gerenciador de cursos
│   └── examples/
│       └── course_manager_examples.php     # Exemplos de uso
├── amd/
│   ├── src/
│   │   └── course_form_tabs.js             # Script das abas
│   └── build/
│       └── course_form_tabs.min.js         # Versão minificada
├── lang/
│   ├── en/
│   │   └── local_localcustomadmin.php      # Strings em inglês
│   └── pt_br/
│       └── local_localcustomadmin.php      # Strings em português
├── styles/
│   └── styles.css                          # Estilos personalizados
└── cursos.php                              # Página de gerenciamento (modificado)
```

---

## 3. Detalhes de Implementação

### 3.1 Classe `local_localcustomadmin_course_form`

**Arquivo**: `form_curso.php`

Extends `moodleform` e define a estrutura do formulário com duas abas.

#### Métodos Principais

```php
public function definition()
```
- Define campos do formulário
- Cria estrutura HTML das abas
- Usa elementos de formulário nativo do Moodle

```php
private function get_enrolments_html($courseid)
```
- Gera tabela com métodos de inscrição ativos
- Mostra preço e status de cada método
- Retorna HTML para a aba "Preço"

```php
private function get_enrolment_price($enrolid)
```
- Busca preço de uma inscrição específica
- Consulta tabela `enrol` para campo `cost`

```php
public function validation($data, $files)
```
- Valida dados do formulário
- Verifica unicidade de `shortname`

#### Campos do Formulário

| Campo | Tipo | Requerido | Descrição |
|-------|------|-----------|-----------|
| id | hidden | Não | ID do curso (0 para novo) |
| fullname | text | Sim | Nome completo do curso |
| shortname | text | Sim | Identificador único |
| category | select | Sim | Categoria do curso |
| summary_editor | editor | Não | Descrição do curso |
| format | select | Não | Formato do curso |
| visible | checkbox | Não | Visibilidade do curso |
| startdate | date_time | Não | Data de início |

---

### 3.2 Página `edit_curso.php`

**Responsabilidades**:
1. Carrega o formulário
2. Processa submissão (criar/atualizar)
3. Chama inicialização de enrollments

#### Fluxo

```
GET /edit_curso.php?id=0
  ↓
  Forma vazia para novo curso
  ↓ POST com dados
  ↓
  create_course($formdata)
  ↓
  course_manager::initialize_course_enrolments($courseid)
  ↓
  Redirect com sucesso
```

#### Permissões

Requer capability: `local/localcustomadmin:manage`

---

### 3.3 Classe `course_manager`

**Arquivo**: `classes/course_manager.php`

Gerencia inicialização e operações com enrollments de cursos.

#### Métodos Públicos

```php
public static function initialize_course_enrolments($courseid)
```
- **Propósito**: Inicializar/atualizar inscrições com base em preços de categoria
- **Parâmetros**: `$courseid` (int) - ID do curso
- **Retorna**: `bool` - Sucesso/falha
- **Throws**: `\Exception` - Se curso não existir

**Algoritmo**:
```
1. Buscar curso
2. Buscar preço ativo da categoria (category_price_manager::get_active_price())
3. Se há preço ativo:
   a. Buscar/criar inscrição "fee"
   b. Atualizar preço da inscrição
4. Garantir inscrição "manual" existe
5. Retornar true
```

```php
private static function get_or_create_fee_enrolment($courseid)
```
- Busca ou cria inscrição tipo "fee"
- Usa plugin nativo `enrol_fee`
- Retorna objeto da inscrição ou null

```php
private static function ensure_manual_enrolment($courseid)
```
- Garante existência de inscrição manual
- Usa plugin nativo `enrol_manual`
- Permite acesso sem pagamento

```php
private static function update_fee_enrolment($enrolid, $price)
```
- Atualiza preço de inscrição "fee"
- Modifica campo `cost` na tabela `enrol`

```php
public static function get_course_enrolments($courseid)
```
- Retorna todas inscrições ativas
- Usa `enrol_get_instances()`

```php
public static function get_enrolment_stats($courseid)
```
- Retorna estatísticas de inscrição
- Conta usuários por método

---

### 3.4 Script JavaScript

**Arquivo**: `amd/src/course_form_tabs.js`

Gerencia interatividade das abas do formulário.

**Funcionalidades**:
- Troca de abas ao clicar em botões
- Atualiza classes `active` e visibilidade
- Mantém estado ARIA para acessibilidade

---

## 4. Fluxo de Dados

### Criação de Novo Curso

```
┌─ Página cursos.php
│  └─ Botão "Adicionar Curso"
├─ Usuário clica
└─ Redirect para edit_curso.php

edit_curso.php
├─ Carrega coursecat::get_all()
├─ Prepara customdata com categorias
└─ Renderiza form_curso.php (vazio)

form_curso.php (renderiza)
├─ Aba "Geral": campos vazios
├─ Aba "Preço": mensagem "Salve primeiro"
└─ Botões: Save / Cancel

Usuário preenche formulário
├─ Nome: "Python Fundamentals"
├─ Shortname: "pythonf101"
├─ Categoria: "Programming" (ID: 5)
├─ Formato: "Topics"
└─ Clica "Save"

POST edit_curso.php
├─ mform->get_data() retorna objeto
├─ create_course($formdata) cria curso ID 123
├─ course_manager::initialize_course_enrolments(123)
│  ├─ get_course(123)
│  ├─ category_price_manager::get_active_price(5)
│  │  └─ Busca preço ativo em mdl_local_customadmin_category_prices
│  ├─ get_or_create_fee_enrolment(123)
│  │  └─ INSERT INTO enrol (courseid, enrol, cost, ...)
│  ├─ update_fee_enrolment(enrol_id, price)
│  │  └─ UPDATE enrol SET cost = 99.99
│  └─ ensure_manual_enrolment(123)
│     └─ INSERT INTO enrol (courseid, enrol='manual', ...)
└─ redirect /cursos.php com sucesso
```

### Edição de Curso Existente

```
edit_curso.php?id=123

├─ get_course(123)
├─ Prepara customdata
├─ form_curso.php renderiza COM dados

form_curso.php (preenchido)
├─ Aba "Geral": dados do curso
├─ Aba "Preço": 
│  ├─ get_enrolments_html(123)
│  │  ├─ enrol_get_instances(123, true)
│  │  ├─ FOR EACH enrolment:
│  │  │  ├─ enrol_get_plugin(enrol->enrol)->get_instance_name()
│  │  │  ├─ get_enrolment_price(enrol->id)
│  │  │  └─ Render em tabela
│  │  └─ Tabela com métodos ativos
│  └─ Botões de ação (Edit por método)
└─ Botões: Save / Cancel

Usuário modifica (ex: nome) e clica Save

POST edit_curso.php?id=123
├─ update_course($formdata)
├─ course_manager::initialize_course_enrolments(123)
│  └─ (mesmo processo acima, atualiza se necessário)
└─ redirect /cursos.php com sucesso
```

---

## 5. Integração com Category Prices

### Fluxo de Preços

```
Tabela: mdl_local_customadmin_category_prices
├─ categoryid: 5
├─ name: "Summer Promo"
├─ price: 49.99
├─ startdate: 1654041600 (2022-06-01)
├─ enddate: 1661817600 (2022-08-30)
├─ status: 1 (ativo)
└─ ...

Quando curso é criado em categoria 5:
├─ category_price_manager::get_active_price(5, now())
├─ Verifica: WHERE categoryid=5 AND status=1 AND startdate<=now() AND enddate>=now()
├─ Retorna: object com price=49.99
├─ Fee enrollment recebe cost=49.99
└─ Curso pronto para venda
```

---

## 6. Strings de Idioma

### Adicionar Novas Strings

**Inglês**: `lang/en/local_localcustomadmin.php`
```php
$string['addcourse'] = 'Add Course';
$string['editcourse'] = 'Edit Course';
$string['coursecreated'] = 'Course created successfully';
$string['courseupdated'] = 'Course updated successfully';
$string['general'] = 'General';
$string['pricing'] = 'Pricing';
```

**Português**: `lang/pt_br/local_localcustomadmin.php`
```php
$string['addcourse'] = 'Adicionar Curso';
$string['editcourse'] = 'Editar Curso';
$string['coursecreated'] = 'Curso criado com sucesso';
$string['courseupdated'] = 'Curso atualizado com sucesso';
$string['general'] = 'Geral';
$string['pricing'] = 'Precificação';
```

---

## 7. Estilos CSS

**Arquivo**: `styles/styles.css`

### Classes Principais

```css
.local-customadmin-course-tabs
  └─ Wrapper principal

.nav-tabs
  └─ Container dos botões de abas

.nav-link
  └─ Botão individual da aba
  └─ Estados: default, hover, active

.tab-content
  └─ Container do conteúdo das abas

.tab-pane
  └─ Painel individual
  └─ Classes: show, active, fade

.course-enrolments-section
  └─ Seção de enrollments

.course-enrolments-section .table
  └─ Tabela de enrollments
```

---

## 8. Fluxo de Validação

### Lado do Cliente
- Fullname: required, max 254
- Shortname: required, max 100
- Category: required

### Lado do Servidor
- Fullname: required, text
- Shortname: required, unique (exceto curso atual)
- Category: required, valid category ID

---

## 9. Tratamento de Erros

### Cenários de Erro

1. **Curso não encontrado ao editar**
   ```php
   $course = get_course($courseid);
   if (!$course) {
       throw new \Exception("Course not found: $courseid");
   }
   ```

2. **Plugin de inscrição não habilitado**
   ```php
   $enrolfee = enrol_get_plugin('fee');
   if (!$enrolfee) {
       // Sem plugin fee, apenas cria manual
       return null;
   }
   ```

3. **Shortname duplicado**
   ```php
   if (!empty($existing)) {
       $errors['shortname'] = get_string('shortnametaken', 'error');
   }
   ```

---

## 10. Extensões Futuras

### Planejado

1. **Editar preço por curso**
   - Sobrescrever preço da categoria na aba "Preço"

2. **Múltiplos métodos**
   - Adicionar/remover inscrições (PayPal, transferência, etc.)

3. **Parcelamento**
   - Configurar número de parcelas

4. **Validações avançadas**
   - Sobreposição de datas de preços
   - Preços por grupo demográfico

---

## 11. Testes Recomendados

### Testes Manuais

- [ ] Criar curso nova sem preço definido
- [ ] Criar curso em categoria com preço ativo
- [ ] Editar curso existente
- [ ] Mudar categoria de curso
- [ ] Visualizar aba "Preço" após salvar
- [ ] Verificar métodos de inscrição criados
- [ ] Testar em ambos idiomas (EN/PT-BR)

### Testes Automatizados

```php
// Unit test para course_manager::initialize_course_enrolments()
public function test_initialize_course_enrolments_with_active_price() {
    $course = $this->create_course(['category' => 2]);
    $this->create_active_price(2, 99.99);
    
    course_manager::initialize_course_enrolments($course->id);
    
    $fee_enrol = get_enrol_instance('fee', $course->id);
    $this->assertEquals(99.99, $fee_enrol->cost);
}
```

---

## 12. Performance

### Otimizações

- Lazy load de abas (JavaScript)
- Cache de categorias
- Índices em mdl_local_customadmin_category_prices

### Queries Críticas

```sql
-- Buscar preço ativo
SELECT * FROM mdl_local_customadmin_category_prices
WHERE categoryid = ? AND status = 1
AND startdate <= ? AND enddate >= ?
ORDER BY startdate DESC
LIMIT 1;

-- Contar usuários por método
SELECT COUNT(*) FROM mdl_user_enrolments
WHERE enrolid IN (
    SELECT id FROM mdl_enrol WHERE courseid = ?
);
```

---

## 13. Segurança

### Verificações

- [x] Validação de capability: `local/localcustomadmin:manage`
- [x] Sanitização de dados de entrada
- [x] CSRF protection (automático do Moodle)
- [x] XSS prevention em strings (format_string)
- [x] SQL injection prevention (prepared statements)

### Recomendações

- Manter permissões restritivas para `local/localcustomadmin:manage`
- Auditar operações de criação/edição de cursos
- Validar preços antes de atribuir

---

## 14. Suporte Multi-idioma

### Suportados

- [x] Inglês (EN)
- [x] Português Brasil (PT-BR)

### Adicionando Novo Idioma

1. Criar arquivo `lang/{locale}/local_localcustomadmin.php`
2. Copiar strings de EN como base
3. Traduzir para novo idioma

---

## 15. Referências

### Funções Moodle Utilizadas

- `create_course($data)` - Criar curso
- `update_course($data)` - Atualizar curso
- `get_course($id)` - Buscar curso
- `enrol_get_instances($courseid, $active)` - Listar inscrições
- `enrol_get_plugin($name)` - Buscar plugin
- `coursecat::get_all()` - Listar categorias
- `category_price_manager::get_active_price()` - Preço ativo

### APIs Utilizadas

- Moodle Forms API (moodleform)
- DB API (database queries)
- Navigation API (breadcrumbs)
- Output API (templates, rendering)

---

Fim da Documentação Técnica
