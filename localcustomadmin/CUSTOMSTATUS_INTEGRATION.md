# 🔗 Integração Custom Status - LocalCustomAdmin

## 📦 Arquivos Adicionados

### API de Integração
- **`classes/api/customstatus_integration.php`** - API completa com 10 métodos públicos

### Interface Web
- **`status_report.php`** - Página de relatório com estatísticas e listagem de alunos

### Automação
- **`classes/task/check_overdue_payments.php`** - Tarefa agendada para verificar inadimplência
- **`db/tasks.php`** - Registro da tarefa (executa diariamente às 2:00 AM)

### Strings de Idioma
- **`lang/en/local_localcustomadmin.php`** - Strings em inglês
- **`lang/pt_br/local_localcustomadmin.php`** - Strings em português

---

## 🚀 Como Usar

### 1. Acessar Relatório de Status

**Caminho:** LocalCustomAdmin → Relatório de Status

ou diretamente: `/local/localcustomadmin/status_report.php`

**Funcionalidades:**
- ✅ Selecionar categoria para análise
- ✅ Visualizar estatísticas (Total, Quitados, Pendentes, Bloqueados)
- ✅ Calcular receita (Esperada, Recebida, Pendente)
- ✅ Listar alunos por status em abas
- ✅ Marcar inadimplentes em massa
- ✅ Enviar lembretes (placeholder para implementação futura)

---

### 2. API Disponível

```php
use local_localcustomadmin\api\customstatus_integration;

// Verificar disponibilidade
$available = customstatus_integration::is_available();

// Obter preço da categoria
$price = customstatus_integration::get_category_price($categoryid);

// Verificar se usuário pagou
$paid = customstatus_integration::has_user_paid($userid, $categoryid);

// Registrar pagamento
$success = customstatus_integration::register_payment($userid, $categoryid, $amount);

// Obter estatísticas
$stats = customstatus_integration::get_category_statistics($categoryid);
// Retorna: ['total' => X, 'paid' => Y, 'payment_due' => Z, 'blocked' => W]

// Obter usuários por status
$users = customstatus_integration::get_users_by_status($categoryid, 'payment_due');

// Atribuir status a usuário
$success = customstatus_integration::assign_status_to_user(
    $userid, 
    $categoryid, 
    'payment_due', 
    'Mensagem personalizada'
);

// Marcar categoria como inadimplente em massa
$count = customstatus_integration::mark_category_overdue($categoryid);

// Relatório completo
$report = customstatus_integration::get_category_report($categoryid);
```

---

### 3. Tarefa Agendada (Cron)

A tarefa `check_overdue_payments` executa automaticamente:

**Quando:** Todos os dias às 2:00 AM

**O que faz:**
1. Verifica todas as categorias com preços definidos
2. Identifica alunos matriculados há mais de 7 dias
3. Verifica se não possuem status 'paid', 'scholarship' ou 'complimentary'
4. Atribui status 'payment_due' automaticamente
5. Envia email de notificação (opcional)

**Testar manualmente:**
```bash
php admin/cli/scheduled_task.php --execute='\local_localcustomadmin\task\check_overdue_payments'
```

**Configurar agendamento:**
Administração → Site → Tarefas Agendadas → Buscar "check overdue payments"

---

## 🎯 Cenários de Uso

### Cenário 1: Aluno se Matricula

```php
// Quando aluno se matricular em curso de categoria paga
function on_user_enrolment($userid, $courseid) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    
    // Verificar se há preço definido
    $price = customstatus_integration::get_category_price($course->category);
    
    if ($price > 0) {
        // Verificar se já pagou
        $paid = customstatus_integration::has_user_paid($userid, $course->category);
        
        if (!$paid) {
            // Atribuir status payment_due
            customstatus_integration::assign_status_to_user(
                $userid,
                $course->category,
                'payment_due',
                'Aguardando pagamento da matrícula'
            );
        } else {
            // Atribuir status paid
            customstatus_integration::assign_status_to_user(
                $userid,
                $course->category,
                'paid',
                'Pagamento confirmado'
            );
        }
    }
}
```

### Cenário 2: Confirmar Pagamento

```php
// Quando sistema de pagamento confirmar transação
function on_payment_confirmed($userid, $categoryid, $amount) {
    // Registrar pagamento
    $success = customstatus_integration::register_payment($userid, $categoryid, $amount);
    
    if ($success) {
        // Status automaticamente atualizado para 'paid'
        
        // Enviar email de confirmação
        $user = $DB->get_record('user', ['id' => $userid]);
        email_to_user(
            $user,
            get_admin(),
            'Pagamento Confirmado',
            'Seu pagamento foi confirmado. Acesso liberado!'
        );
    }
}
```

### Cenário 3: Dashboard Administrativo

```php
// Página customizada de dashboard
$categoryid = 5;
$report = customstatus_integration::get_category_report($categoryid);

echo "<h2>{$report['category']->name}</h2>";
echo "<p>Preço: R$ " . number_format($report['price'], 2) . "</p>";

echo "<div class='stats'>";
echo "<div>Total: {$report['statistics']['total']}</div>";
echo "<div>Quitados: {$report['statistics']['paid']}</div>";
echo "<div>Pendentes: {$report['statistics']['payment_due']}</div>";
echo "</div>";

echo "<div class='revenue'>";
echo "<div>Esperada: R$ " . number_format($report['revenue']['expected'], 2) . "</div>";
echo "<div>Recebida: R$ " . number_format($report['revenue']['received'], 2) . "</div>";
echo "<div>Pendente: R$ " . number_format($report['revenue']['pending'], 2) . "</div>";
echo "</div>";
```

---

## ⚙️ Instalação

### 1. Arquivos já estão no lugar
Todos os arquivos foram criados nas pastas corretas do `local/localcustomadmin`.

### 2. Limpar cache do Moodle
```bash
php admin/cli/purge_caches.php
```

### 3. Acessar Notifications
Vá em: **Administração → Notificações** para registrar a tarefa agendada.

### 4. Testar Integração
```bash
# Verificar se Custom Status está disponível
php -r "
require('config.php');
require_once(\$CFG->dirroot . '/local/localcustomadmin/classes/api/customstatus_integration.php');
echo \local_localcustomadmin\api\customstatus_integration::is_available() ? 'OK' : 'NOT FOUND';
"
```

---

## 📊 Estrutura de Dados

### Tabelas Utilizadas

**LocalCustomAdmin:**
- `local_customadmin_category_prices` - Preços das categorias

**Custom Status (plugin externo):**
- `enrol_customstatus_status` - Definições de status
- `enrol_customstatus_user` - Status atribuídos aos usuários
- `enrol` - Métodos de matrícula
- `user_enrolments` - Matrículas dos usuários

---

## 🔒 Permissões

**Necessário:**
- `local/localcustomadmin:manage` - Para acessar relatório e ações administrativas

**Verificação:**
```php
require_capability('local/localcustomadmin:manage', $context);
```

---

## 🐛 Troubleshooting

### Erro: "Custom Status plugin is not installed"

**Solução:** Instalar o plugin `enrol_customstatus` primeiro:
```bash
cd /path/to/moodle/enrol
git clone <repo-url> customstatus
php admin/cli/purge_caches.php
# Acessar: Administração → Notificações
```

### Erro: "Call to undefined method"

**Causa:** Cache não foi limpo após adicionar arquivos.

**Solução:**
```bash
php admin/cli/purge_caches.php
```

### Tarefa não executa

**Verificar registro:**
```sql
SELECT * FROM mdl_task_scheduled 
WHERE classname LIKE '%check_overdue%';
```

**Forçar execução:**
```bash
php admin/cli/scheduled_task.php --execute='\local_localcustomadmin\task\check_overdue_payments'
```

---

## 🎨 Customização

### Alterar critério de inadimplência

Editar: `classes/task/check_overdue_payments.php`

```php
// Linha 87 - Alterar de 7 para X dias
$threshold = time() - (7 * 24 * 60 * 60);  // 7 dias
```

### Desabilitar envio de email

Editar: `classes/task/check_overdue_payments.php`

```php
// Linha 110 - Comentar
// $this->send_overdue_notification($user->userid, $category->categoryid);
```

### Alterar horário da tarefa

Editar: `db/tasks.php`

```php
'hour' => '2',  // Alterar para hora desejada (0-23)
```

---

## 📈 Próximos Passos

### Implementações Futuras

1. **Sistema de Pagamentos Completo**
   - Tabela `local_customadmin_payments`
   - Integração com gateways (PagSeguro, Mercado Pago)
   - Histórico de transações

2. **Notificações Avançadas**
   - Templates de email personalizados
   - Notificações in-app
   - SMS para pagamentos críticos

3. **Relatórios Expandidos**
   - Gráficos com Chart.js
   - Exportação para Excel/PDF
   - Análise de tendências

4. **Webhooks**
   - Endpoint para receber confirmações de gateway
   - Atualização automática de status

---

## 📞 Suporte

Para dúvidas sobre integração, consulte:
- `/enrol/customstatus/INTEGRATION_GUIDE.md` - Guia completo do Custom Status
- Documentação oficial: [Moodle Enrolment API](https://docs.moodle.org/dev/Enrolment_API)

---

**Desenvolvido com ❤️ - Outubro 2025**
