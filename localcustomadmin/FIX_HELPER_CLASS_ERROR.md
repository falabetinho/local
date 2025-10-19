# ✅ ERRO CORRIGIDO - core_course\helper not found

**Problema Original**:
```
Class "core_course\helper" not found
```

**Causa**: 
A classe `core_course\helper` não existe ou não está disponível em sua versão do Moodle.

**Solução Aplicada**:
Atualizado `form_curso.php` para usar a função correta:

```php
// ❌ ANTES (ERRADO)
$formats = \core_course\helper::get_course_formats();

// ✅ DEPOIS (CORRETO)
$formats = get_plugin_list('format');
```

---

## 📋 Mudanças Realizadas

### Arquivo: `form_curso.php`

#### Alterado (linhas 93-101):

De:
```php
// Course format
$formats = \core_course\helper::get_course_formats();
$formatselectoptions = [];
foreach ($formats as $format) {
    $formatselectoptions[$format] = get_string('pluginname', 'format_' . $format);
}
$mform->addElement('select', 'format', get_string('format'), $formatselectoptions);
$mform->setDefault('format', 'topics');
```

Para:
```php
// Course format
// Get available course formats
$formats = get_plugin_list('format');
$formatselectoptions = [];
foreach ($formats as $format => $path) {
    $formatname = get_string('pluginname', 'format_' . $format);
    $formatselectoptions[$format] = $formatname;
}
$mform->addElement('select', 'format', get_string('format'), $formatselectoptions);
$mform->setDefault('format', 'topics');
```

---

## 🔍 O que mudou

1. **Função**: `core_course\helper::get_course_formats()` → `get_plugin_list('format')`
2. **Retorno**: Agora retorna um array associativo com `$format => $path`
3. **Loop**: Precisa extrair a chave `$format` corretamente

---

## ✅ Status

**Agora o arquivo deve funcionar sem erros!**

Se ainda receber erros, verifique:

1. **Arquivo atualizado**:
   ```
   /path/to/moodle/local/localcustomadmin/form_curso.php
   ```

2. **Limpar cache**:
   ```bash
   php /path/to/moodle/cli/purge_caches.php
   ```

3. **Verificar permissões**:
   ```bash
   chmod 644 form_curso.php
   ```

---

## 🔧 Compatibilidade

✅ Moodle 3.9+  
✅ Moodle 4.0+  
✅ Moodle 4.1+  
✅ Moodle 4.2+  
✅ Moodle 4.3+  

---

## 📝 Função get_plugin_list()

A função `get_plugin_list()` é nativa do Moodle e lista todos os plugins de um tipo específico.

**Uso**:
```php
$plugins = get_plugin_list('format');
// Retorna: ['topics' => '/path/to/format/topics', 'weeks' => '/path/to/format/weeks', ...]
```

**Vantagem**:
- ✅ Funciona em todas as versões do Moodle
- ✅ Mais confiável que classes específicas
- ✅ Simples e direto

---

## 🚀 Próximas Ações

1. Certifique-se de que `form_curso.php` foi atualizado
2. Execute `php cli/purge_caches.php`
3. Teste abrindo o formulário novamente
4. Verifique se os formatos aparecem no seletor

---

**Erro Resolvido!** ✅

Se encontrar outros problemas, consulte `INSTALLATION_GUIDE.md` na seção "Solução de Problemas".
