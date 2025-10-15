# Templates Mustache - Local Custom Admin

Este diretório contém os templates Mustache utilizados pelo plugin Local Custom Admin.

## Estrutura dos Templates

### 1. `index.mustache`
Template principal da página index que exibe cards com as opções administrativas disponíveis.

**Variáveis de contexto:**
- `pagetitle` - Título da página
- `welcome_message` - Mensagem de boas-vindas
- `cards` - Array de objetos card com propriedades:
  - `title` - Título do card
  - `description` - Descrição do card
  - `url` - URL de destino
  - `btntext` - Texto do botão
  - `icon` - Classe do ícone FontAwesome
- `no_cards` - Boolean para quando não há cards disponíveis

### 2. `dashboard.mustache`
Template para a página de dashboard que exibe estatísticas do sistema.

**Variáveis de contexto:**
- `pagetitle` - Título da página
- `statistics` - Array de objetos de estatística com propriedades:
  - `title` - Título da estatística
  - `value` - Valor numérico
  - `icon` - Classe do ícone FontAwesome
  - `variant` - Variante de cor do Bootstrap

### 3. `card.mustache`
Template reutilizável para componentes card.

**Variáveis de contexto:**
- `title` - Título do card
- `description` - Descrição do card
- `url` - URL de destino
- `btntext` - Texto do botão
- `icon` - Classe do ícone FontAwesome (opcional)
- `variant` - Variante de cor do Bootstrap (opcional)

## Como usar os templates

### No PHP:
```php
// Preparar dados de contexto
$templatecontext = [
    'pagetitle' => 'Minha Página',
    'welcome_message' => 'Bem-vindo!',
    'cards' => [
        [
            'title' => 'Dashboard',
            'description' => 'Acesse o dashboard',
            'url' => '/local/localcustomadmin/dashboard.php',
            'btntext' => 'Abrir Dashboard',
            'icon' => 'fa-tachometer-alt'
        ]
    ]
];

// Renderizar template
echo $OUTPUT->render_from_template('local_localcustomadmin/index', $templatecontext);
```

## Estilos CSS

Os templates utilizam classes CSS personalizadas definidas em `/styles/styles.css`:

- `.local-customadmin-index` - Container principal da index
- `.local-customadmin-dashboard` - Container principal do dashboard
- Classes Bootstrap padrão para layout e componentes

## Ícones

Os templates utilizam ícones FontAwesome. Alguns exemplos:

- `fa-tachometer-alt` - Dashboard
- `fa-cog` - Configurações
- `fa-users` - Usuários
- `fa-graduation-cap` - Cursos
- `fa-chart-bar` - Estatísticas

## Responsividade

Todos os templates são responsivos e utilizam o sistema de grid do Bootstrap:

- Desktop: 3-4 colunas
- Tablet: 2 colunas
- Mobile: 1 coluna

## Acessibilidade

Os templates seguem práticas de acessibilidade:

- Uso apropriado de tags semânticas
- Atributos alt para ícones informativos
- Contraste adequado de cores
- Navegação por teclado funcional