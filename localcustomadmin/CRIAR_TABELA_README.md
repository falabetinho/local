# Criação da Tabela de Sincronização WordPress

Este diretório contém scripts para criar a tabela `local_customadmin_wp_mapping` necessária para a sincronização entre Moodle e WordPress.

## Opção 1: Usando SQL Direto (Recomendado para criar manualmente)

### Via phpMyAdmin ou MySQL Workbench:

1. Acesse seu phpMyAdmin ou MySQL Workbench
2. Selecione o banco de dados do Moodle
3. Abra o arquivo `create_wp_mapping_table.sql`
4. Execute o script SQL completo

### Via linha de comando MySQL:

```bash
# Substitua os valores conforme seu ambiente
mysql -u root -p nome_do_banco_moodle < create_wp_mapping_table.sql
```

**Importante:** Verifique se o prefixo das tabelas está correto (padrão: `mdl_`). Se seu Moodle usa outro prefixo, edite o arquivo SQL antes de executar.

## Opção 2: Usando a Interface Web do Moodle (Mais Seguro)

1. Acesse o Moodle como administrador
2. Vá em: **Administração do site → Notificações**
3. O Moodle detectará automaticamente que há uma nova versão do plugin
4. Clique em **Atualizar banco de dados**
5. A tabela será criada automaticamente

## Opção 3: Usando CLI do Moodle

Execute o script bash fornecido:

```bash
# Dê permissão de execução
chmod +x create_table_cli.sh

# Execute o script
./create_table_cli.sh
```

Ou execute diretamente o comando de upgrade do Moodle:

```bash
cd /caminho/para/moodle
php admin/cli/upgrade.php --non-interactive
```

## Verificação

Para verificar se a tabela foi criada corretamente:

### Via SQL:

```sql
-- Verificar existência da tabela
SHOW TABLES LIKE 'mdl_local_customadmin_wp_mapping';

-- Verificar estrutura
DESCRIBE mdl_local_customadmin_wp_mapping;

-- Verificar quantidade de registros
SELECT COUNT(*) FROM mdl_local_customadmin_wp_mapping;
```

### Via Interface do Moodle:

1. Acesse: **Administração do site → Desenvolvimento → XMLDB Editor**
2. Localize o plugin `local_localcustomadmin`
3. Clique em **Load** e verifique as tabelas

## Estrutura da Tabela

A tabela `local_customadmin_wp_mapping` contém:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT(10) | ID único (chave primária) |
| `moodle_type` | VARCHAR(50) | Tipo do item no Moodle (category, course) |
| `moodle_id` | BIGINT(10) | ID do item no Moodle |
| `wordpress_type` | VARCHAR(50) | Tipo do item no WordPress (term, post) |
| `wordpress_id` | BIGINT(10) | ID do item no WordPress |
| `wordpress_taxonomy` | VARCHAR(100) | Taxonomia do WordPress (ex: nivel) |
| `wordpress_post_type` | VARCHAR(100) | Tipo de post do WordPress (ex: curso) |
| `sync_status` | VARCHAR(20) | Status: pending, synced, error |
| `last_synced` | BIGINT(10) | Timestamp da última sincronização |
| `sync_error` | TEXT | Mensagem de erro (se houver) |
| `timecreated` | BIGINT(10) | Timestamp de criação |
| `timemodified` | BIGINT(10) | Timestamp de modificação |

### Índices:

- **PRIMARY KEY:** `id`
- **UNIQUE INDEX:** `(moodle_type, moodle_id)` - Garante um único mapeamento por item do Moodle
- **INDEX:** `(wordpress_type, wordpress_id)` - Busca rápida por itens do WordPress
- **INDEX:** `sync_status` - Filtro por status de sincronização
- **INDEX:** `moodle_type` - Filtro por tipo de item do Moodle

## Solução de Problemas

### Erro: "Table already exists"

Se a tabela já existe, você pode:

1. Deletar e recriar:
```sql
DROP TABLE IF EXISTS mdl_local_customadmin_wp_mapping;
-- Depois execute o create_wp_mapping_table.sql novamente
```

2. Ou simplesmente ignorar o erro se a estrutura estiver correta

### Erro: "Access denied"

Verifique se o usuário do banco de dados tem permissões para criar tabelas:

```sql
GRANT CREATE, ALTER, DROP ON nome_do_banco.* TO 'usuario_moodle'@'localhost';
FLUSH PRIVILEGES;
```

### Verificar prefixo das tabelas

Para descobrir qual prefixo seu Moodle usa:

```bash
grep 'prefix' /caminho/para/moodle/config.php
```

Ou via SQL:

```sql
SHOW TABLES LIKE '%course_categories';
```

## Próximos Passos

Após criar a tabela:

1. Configure as credenciais da API WordPress em:
   **Administração do site → Plugins → Administração local → Custom Admin → WordPress Integration**

2. Teste a conexão com o WordPress

3. Execute a sincronização inicial de categorias

## Suporte

Em caso de problemas, verifique:

- Logs do Moodle: **Administração do site → Relatórios → Logs**
- Logs do servidor web (Apache/Nginx)
- Logs do MySQL/MariaDB
