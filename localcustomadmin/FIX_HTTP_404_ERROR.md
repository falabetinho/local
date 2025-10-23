# Correção: Erro HTTP 404 na Sincronização WordPress

## Problema

Ao tentar sincronizar categorias do Moodle com o WordPress, ocorria o seguinte erro:

```
Error syncing category Category 1: HTTP 404: No route was found matching the URL and request method.
```

## Causa Raiz

O WordPress REST API possui uma convenção de nomenclatura onde:

- **Taxonomia registrada no WordPress:** `nivel` (singular)
- **Rota da API REST:** `/wp/v2/niveis` (plural)

O código estava tentando acessar `/wp/v2/nivel` (singular), que não existe na API REST.

### Por que isso acontece?

Quando você registra uma taxonomia customizada no WordPress com `register_taxonomy()`, o WordPress automaticamente pluraliza o nome para criar a rota da API REST.

Exemplo:
```php
// Registro no WordPress
register_taxonomy('nivel', 'curso', $args);

// Rotas geradas automaticamente pela API REST:
// GET    /wp/v2/niveis       - Listar todos os termos
// POST   /wp/v2/niveis       - Criar novo termo
// GET    /wp/v2/niveis/{id}  - Obter termo específico
// PUT    /wp/v2/niveis/{id}  - Atualizar termo
// DELETE /wp/v2/niveis/{id}  - Deletar termo
```

## Solução Implementada

Foi adicionada uma nova propriedade na classe `wordpress_category_sync` para separar:

1. **Nome da taxonomia** (usado no banco de dados): `nivel`
2. **Rota da API REST** (usado nas chamadas HTTP): `niveis`

### Código Antes:
```php
/** @var string WordPress taxonomy name */
private $taxonomy = 'nivel';

// Chamada à API (INCORRETA)
$this->api->create_term($this->taxonomy, $termdata);
// Tentava acessar: /wp/v2/nivel (não existe!)
```

### Código Depois:
```php
/** @var string WordPress taxonomy name */
private $taxonomy = 'nivel';

/** @var string WordPress REST API route (plural form) */
private $taxonomy_route = 'niveis';

// Chamada à API (CORRETA)
$this->api->create_term($this->taxonomy_route, $termdata);
// Acessa: /wp/v2/niveis (existe!)
```

## Arquivos Modificados

- `classes/wordpress_category_sync.php`
  - Linha 48: Adicionada propriedade `$taxonomy_route`
  - Linha 127: Uso de `$taxonomy_route` em `update_term()`
  - Linha 142: Uso de `$taxonomy_route` em `create_term()` (404 recovery)
  - Linha 165: Uso de `$taxonomy_route` em `create_term()` (novo termo)

## Verificação no WordPress

Para confirmar a rota correta da sua taxonomia:

### Via API REST:
```bash
curl http://seu-site.com/wp-json/wp/v2
```

Procure pela sua taxonomia no JSON retornado:
```json
{
  "/wp/v2/niveis": {
    "namespace": "wp/v2",
    "methods": ["GET", "POST"],
    ...
  }
}
```

### Via código WordPress:
```php
// Verificar taxonomias registradas
$taxonomies = get_taxonomies(['public' => true], 'objects');
foreach ($taxonomies as $taxonomy) {
    echo $taxonomy->name . ' -> ' . $taxonomy->rest_base . "\n";
}
```

## Impacto

✅ **Antes da correção:** Todas as sincronizações falhavam com erro HTTP 404  
✅ **Depois da correção:** Sincronizações funcionam corretamente

## Lições Aprendidas

1. **Sempre consulte a API REST** do WordPress antes de implementar integrações
2. **Rotas REST nem sempre correspondem ao nome da taxonomia** - o WordPress pode pluralizar automaticamente
3. **Separe conceitos no código:** nome interno vs. rota da API
4. **Teste com a API diretamente** antes de implementar no Moodle

## Referências

- [WordPress REST API Handbook - Taxonomies](https://developer.wordpress.org/rest-api/reference/taxonomies/)
- [WordPress register_taxonomy() Reference](https://developer.wordpress.org/reference/functions/register_taxonomy/)
- [REST API Discovery](https://developer.wordpress.org/rest-api/using-the-rest-api/discovery/)
