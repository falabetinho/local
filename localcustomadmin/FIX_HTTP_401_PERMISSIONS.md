# Correção: Erro HTTP 401 - Permissões da Taxonomia WordPress

## Problema

```
Error syncing category Category 1: HTTP 401: Sorry, you are not allowed to create terms in this taxonomy.
```

## Causa

A taxonomia customizada "nivel" no WordPress **não está exposta na API REST** ou não possui as **capabilities** corretas configuradas.

## Soluções

### Solução 1: Verificar Registro da Taxonomia (Recomendado)

No seu **tema ou plugin WordPress**, localize onde a taxonomia "nivel" está registrada e garanta que:

```php
register_taxonomy('nivel', 'curso', array(
    'labels' => array(
        'name' => __('Níveis'),
        'singular_name' => __('Nível')
    ),
    'hierarchical' => true, // Para permitir subcategorias (parent/child)
    'public' => true,
    
    // CRÍTICO: Expor na API REST
    'show_in_rest' => true,
    'rest_base' => 'niveis', // Rota da API (plural)
    'rest_namespace' => 'wp/v2',
    'rest_controller_class' => 'WP_REST_Terms_Controller',
    
    // CRÍTICO: Capabilities
    'capabilities' => array(
        'manage_terms' => 'manage_categories', // Quem pode gerenciar
        'edit_terms'   => 'manage_categories', // Quem pode editar
        'delete_terms' => 'manage_categories', // Quem pode deletar
        'assign_terms' => 'edit_posts',        // Quem pode atribuir aos posts
    ),
    
    // Outras configurações úteis
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array('slug' => 'nivel'),
));
```

### Solução 2: Verificar Permissões do Usuário da API

O usuário WordPress que está autenticando via API precisa ter a capability **`manage_categories`**.

#### Verificar capabilities do usuário atual:

Adicione este código temporário em `functions.php` ou crie um plugin de teste:

```php
add_action('rest_api_init', function() {
    register_rest_route('custom/v1', '/check-permissions', array(
        'methods' => 'GET',
        'callback' => function() {
            $current_user = wp_get_current_user();
            
            return array(
                'user_id' => $current_user->ID,
                'username' => $current_user->user_login,
                'roles' => $current_user->roles,
                'capabilities' => array(
                    'manage_categories' => current_user_can('manage_categories'),
                    'manage_options' => current_user_can('manage_options'),
                    'edit_posts' => current_user_can('edit_posts'),
                ),
                'all_caps' => array_keys($current_user->allcaps)
            );
        },
        'permission_callback' => '__return_true'
    ));
});
```

Depois acesse:
```
http://seu-site.com/wp-json/custom/v1/check-permissions
```

**Com o cabeçalho de autorização:**
```bash
curl -H "Authorization: Bearer SEU_TOKEN" \
     http://seu-site.com/wp-json/custom/v1/check-permissions
```

### Solução 3: Dar Permissões ao Papel (Role) do Usuário

Se o usuário não tem as permissões necessárias, adicione-as:

```php
// Adicionar ao functions.php (executar uma única vez)
function add_nivel_permissions() {
    // Para Administradores
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_categories');
    }
    
    // Para Editores (se necessário)
    $role = get_role('editor');
    if ($role) {
        $role->add_cap('manage_categories');
    }
}
add_action('init', 'add_nivel_permissions');
```

**IMPORTANTE:** Depois de executar, **comente ou remova** este código para não executar repetidamente.

### Solução 4: Plugin de Autenticação (Verificar Token)

Se você está usando **Application Passwords** ou **JWT Authentication**, verifique:

#### Para Application Passwords:

1. Vá em: **Usuários → Perfil**
2. Role até **Application Passwords**
3. Certifique-se de que criou uma senha de aplicação
4. Copie o token gerado (formato: `xxxx xxxx xxxx xxxx xxxx xxxx`)

No Moodle, configure em:
```
Administração do site → Plugins → Administração local → Custom Admin → WordPress Integration
```

**Formato do token:**
- **Sem espaços:** `xxxxxxxxxxxxxxxxxxxxxxxx`
- Ou use Basic Auth: `base64(usuario:senha_aplicacao)`

#### Para JWT Authentication:

Verifique se o plugin JWT está ativo e configurado corretamente no `wp-config.php`:

```php
define('JWT_AUTH_SECRET_KEY', 'sua-chave-secreta-aqui');
define('JWT_AUTH_CORS_ENABLE', true);
```

### Solução 5: Verificar via API REST Diretamente

Teste a API manualmente para isolar o problema:

```bash
# Listar termos (GET - geralmente funciona)
curl -H "Authorization: Bearer SEU_TOKEN" \
     http://seu-site.com/wp-json/wp/v2/niveis

# Criar termo (POST - onde o erro ocorre)
curl -X POST \
     -H "Authorization: Bearer SEU_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name":"Teste","slug":"teste"}' \
     http://seu-site.com/wp-json/wp/v2/niveis
```

Se retornar **401**, o problema é de permissões do WordPress.  
Se retornar **201**, o problema está no Moodle/configuração do token.

## Correção Rápida (Desenvolvimento)

Para **ambiente de desenvolvimento** (NÃO use em produção):

```php
// functions.php - Remove verificação de permissão (APENAS PARA TESTE!)
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    if (strpos($request->get_route(), '/wp/v2/niveis') !== false) {
        // Log para debug
        error_log('REST Request: ' . $request->get_method() . ' ' . $request->get_route());
        error_log('Current User: ' . wp_get_current_user()->user_login);
        error_log('Can manage_categories: ' . (current_user_can('manage_categories') ? 'yes' : 'no'));
    }
    return $result;
}, 10, 3);
```

## Verificação Final

Após aplicar as correções:

1. **Limpe o cache do WordPress:**
   - Via WP CLI: `wp cache flush`
   - Via plugin: W3 Total Cache, WP Super Cache, etc.
   - Via admin: **Ferramentas → Disponível → Limpar cache**

2. **Faça logout e login** no WordPress

3. **Gere um novo Application Password** (se usando)

4. **Teste novamente** no Moodle

## Arquivo de Configuração Exemplo (WordPress)

Crie um plugin chamado `nivel-taxonomy.php`:

```php
<?php
/**
 * Plugin Name: Nível Taxonomy
 * Description: Registra a taxonomia 'nivel' com suporte REST API
 * Version: 1.0.0
 */

add_action('init', function() {
    register_taxonomy('nivel', 'curso', array(
        'labels' => array(
            'name' => 'Níveis',
            'singular_name' => 'Nível',
            'menu_name' => 'Níveis',
            'all_items' => 'Todos os Níveis',
            'edit_item' => 'Editar Nível',
            'view_item' => 'Ver Nível',
            'update_item' => 'Atualizar Nível',
            'add_new_item' => 'Adicionar Novo Nível',
            'new_item_name' => 'Nome do Novo Nível',
            'search_items' => 'Buscar Níveis',
            'not_found' => 'Nenhum nível encontrado',
        ),
        'public' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rest_base' => 'niveis',
        'rest_namespace' => 'wp/v2',
        'rest_controller_class' => 'WP_REST_Terms_Controller',
        'capabilities' => array(
            'manage_terms' => 'manage_categories',
            'edit_terms' => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ),
        'rewrite' => array(
            'slug' => 'nivel',
            'with_front' => true,
            'hierarchical' => true,
        ),
    ));
});
```

Salve em: `wp-content/plugins/nivel-taxonomy.php` e ative via admin do WordPress.

## Checklist de Troubleshooting

- [ ] Taxonomia tem `'show_in_rest' => true`
- [ ] Taxonomia tem `'rest_base' => 'niveis'`
- [ ] Taxonomia tem capabilities corretas (`manage_categories`)
- [ ] Usuário da API tem role com `manage_categories`
- [ ] Application Password ou JWT Token está correto
- [ ] Token está configurado corretamente no Moodle
- [ ] Cache do WordPress foi limpo
- [ ] Teste manual via cURL funcionou

## Referências

- [WordPress REST API - Taxonomies](https://developer.wordpress.org/rest-api/reference/taxonomies/)
- [WordPress Roles and Capabilities](https://wordpress.org/support/article/roles-and-capabilities/)
- [Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/)
