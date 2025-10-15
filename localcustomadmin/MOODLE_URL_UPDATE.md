# Atualização das URLs para usar moodle_url

## Resumo das Alterações

Todos os arquivos do plugin foram atualizados para usar `new moodle_url()` em vez de strings hardcoded para URLs. Esta é uma prática recomendada pelo Moodle que garante:

### Benefícios do moodle_url:
1. **Integridade das URLs**: URLs são sempre construídas corretamente
2. **Compatibilidade**: Funciona com diferentes configurações de instalação do Moodle
3. **Segurança**: Proteção contra problemas de URL malformadas
4. **Flexibilidade**: Facilita mudanças de estrutura de URLs no futuro

## Arquivos Alterados:

### 1. index.php
- `$PAGE->set_url()`: Agora usa `new moodle_url('/local/localcustomadmin/index.php')`
- URLs dos cards: Todos agora usam `(new moodle_url('/caminho/arquivo.php'))->out()`

### 2. cursos.php
- `$PAGE->set_url()`: Atualizado para `new moodle_url('/local/localcustomadmin/cursos.php')`
- URLs das actions: Todas as actions agora usam moodle_url com parâmetros apropriados
- URLs dos cursos: Já estavam usando moodle_url corretamente

### 3. test_simple.php
- `$PAGE->set_url()`: Atualizado para usar moodle_url
- URL do card de teste: Agora usa `(new moodle_url('/local/localcustomadmin/dashboard.php'))->out()`

### 4. debug_strings.php
- `$PAGE->set_url()`: Atualizado para usar moodle_url

## Exemplos de Uso:

### Para URLs simples:
```php
$PAGE->set_url(new moodle_url('/local/localcustomadmin/index.php'));
```

### Para URLs com parâmetros:
```php
$url = new moodle_url('/course/edit.php', ['category' => 1]);
```

### Para usar em templates:
```php
'url' => (new moodle_url('/local/localcustomadmin/dashboard.php'))->out()
```

## Considerações Técnicas:

- Os erros de linting são normais pois o analisador não reconhece classes do Moodle
- O Moodle carrega automaticamente a classe `moodle_url` via `config.php`
- O método `->out()` converte o objeto moodle_url em string para uso em templates
- Parâmetros podem ser passados como segundo argumento (array associativo)

## Status:
✅ Todas as URLs do plugin foram migradas para moodle_url
✅ Compatibilidade com padrões do Moodle garantida
✅ Integridade das URLs assegurada

Data: 14 de Outubro de 2025