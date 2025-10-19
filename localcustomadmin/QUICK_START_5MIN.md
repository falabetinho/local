# 🚀 INÍCIO RÁPIDO - 5 MINUTOS

**Status**: ✅ Tudo pronto!  
**Tempo total**: 5 minutos  

---

## ⚡ PASSO 1: Copiar Arquivos (2 min)

Copie estes arquivos para sua instalação Moodle:

```
Origem: c:\xampp\htdocs\moodle\local\localcustomadmin\
Destino: /path/to/your/moodle/local/localcustomadmin/
```

**Arquivos principais**:
- `form_curso.php`
- `edit_curso.php`
- `classes/course_manager.php`

**Atualizar**:
- `lang/en/local_localcustomadmin.php`
- `lang/pt_br/local_localcustomadmin.php`
- `styles/styles.css`
- `cursos.php`

---

## 🔧 PASSO 2: Configurar (2 min)

### 2.1 Verificar Plugins

Vá em: **Administração > Plugins > Plugins de Inscrição**

Certifique-se que estão habilitados:
- ✅ Fee-based enrollment (`enrol_fee`)
- ✅ Manual enrollment (`enrol_manual`)

### 2.2 Limpar Cache

Execute no terminal:
```bash
php /path/to/moodle/cli/purge_caches.php
```

### 2.3 Criar Categoria com Preço (Opcional)

Para testar, crie um preço de teste:

**Database**:
```sql
INSERT INTO mdl_local_customadmin_category_prices 
(categoryid, name, price, startdate, enddate, status, 
 isenrollmentfee, timecreated, timemodified)
VALUES
(2, 'Test Price', 99.99, UNIX_TIMESTAMP(), 
 UNIX_TIMESTAMP()+31536000, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
```

Ou use a interface de preços se disponível.

---

## ✅ PASSO 3: Usar (1 min)

### 3.1 Acessar Formulário

1. Faça login como **administrador**
2. Vá em: **Administração > Local Custom Admin > Cursos**
3. Clique em: **"Adicionar Curso"** (novo botão)

### 3.2 Preencher Formulário

**Aba "Geral"**:
- Nome completo: "Python 101"
- Nome abreviado: "py101"
- Categoria: (selecione uma)
- Formato: Topics
- Clique: "Salvar"

### 3.3 Verificar Resultado

✅ Sistema cria automaticamente:
- Inscrição tipo "fee" (com preço da categoria)
- Inscrição tipo "manual" (acesso livre)

**Pronto!** Seu curso está criado com enrollments! 🎉

---

## 🎯 Aba "Preço"

Para visualizar enrollments criados:

1. Clique em "Editar" no curso
2. Vá para aba **"Preço"**
3. Veja tabela com métodos de inscrição

```
┌────────┬────────┬────────┐
│ Método │ Status │ Preço  │
├────────┼────────┼────────┤
│ fee    │ Ativo  │ 99.99  │
│ manual │ Ativo  │   -    │
└────────┴────────┴────────┘
```

---

## 🐛 Troubleshooting Rápido

### Problema: "Botão não aparece"
```
1. Limpar cache:
   php cli/purge_caches.php
2. Limpar cache navegador:
   Ctrl+F5
3. Verificar permissão:
   Você tem 'local/localcustomadmin:manage'?
```

### Problema: "Erro ao salvar"
```
1. Verifique logs:
   tail -f moodledata/debug.log
2. Plugins habilitados?
   Admin > Plugins > Inscrição
3. Tabelas criadas?
   SELECT * FROM mdl_local_customadmin_category_prices;
```

### Problema: "Sem preço no enrollment"
```
1. Existe preço para categoria?
2. Preço está ativo (status=1)?
3. Data do preço está válida?
   SELECT * FROM mdl_local_customadmin_category_prices
   WHERE categoryid=2;
```

---

## 📚 Próximas Leituras

Após os 5 minutos, leia:

1. **Para mais detalhes**: `FORMCURSOS_README.md`
2. **Para instalação completa**: `INSTALLATION_GUIDE.md`
3. **Para desenvolvedor**: `TECHNICAL_DOCUMENTATION.md`

---

## ✨ Recursos

### Arquivo do Formulário
- `form_curso.php` - Define as abas e campos

### Processamento
- `edit_curso.php` - Lida com criação/edição

### Automação
- `classes/course_manager.php` - Cria enrollments com preço

### Interação
- `amd/src/course_form_tabs.js` - Abas funcionam

---

## 🎉 Pronto!

Você agora pode:
- ✅ Criar cursos com formulário
- ✅ Ter enrollments automáticos
- ✅ Sincronizar preços
- ✅ Visualizar métodos de inscrição

**Tempo total gasto**: 5 minutos ⏱️

---

## 📞 Precisa de Ajuda?

Veja:
- 📖 [`DOCUMENTATION_INDEX.md`](DOCUMENTATION_INDEX.md) - Índice geral
- 🆘 [`INSTALLATION_GUIDE.md`](INSTALLATION_GUIDE.md) - Troubleshooting
- 💡 [`classes/examples/course_manager_examples.php`](classes/examples/course_manager_examples.php) - Exemplos

---

**Divirta-se criando cursos!** 🚀
