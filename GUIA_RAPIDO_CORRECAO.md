# 🚀 GUIA RÁPIDO - CORRIGIR BUSCA POR PALAVRA-CHAVE

**Problema:** Busca por palavra-chave não funciona ou é muito lenta.

**Solução:** 3 arquivos para executar em sequência.

---

## ⚡ CORREÇÃO RÁPIDA (5 minutos)

### 1️⃣ Corrigir Índices do Banco (2 min)

No **phpMyAdmin** ou **SSH**, execute:

```bash
mysql -u u590097272_neto -p u590097272_licitapub < corrigir_busca.sql
```

**O que faz:** Cria índices FULLTEXT para busca rápida.

---

### 2️⃣ Atualizar Controller (1 min)

Via **FTP** ou **Painel de Arquivos**:

```bash
# Fazer backup
cp backend/src/Controllers/LicitacaoController.php \
   backend/src/Controllers/LicitacaoController.php.backup

# Substituir
cp backend/src/Controllers/LicitacaoController_FIXED.php \
   backend/src/Controllers/LicitacaoController.php
```

**O que faz:** Substitui código LIKE por FULLTEXT SEARCH.

---

### 3️⃣ Testar (2 min)

**Opção A: Via navegador**

```
https://licita.pub/testar_busca_servidor.php
```

**Opção B: Via terminal**

```bash
./testar_busca_completo.sh
```

**Opção C: Manualmente**

```bash
curl "https://licita.pub/backend/api/licitacoes/buscar.php?q=computador"
```

---

## ✅ RESULTADO ESPERADO

Antes da correção:
```
Busca por "computador": ~2-5 segundos
```

Depois da correção:
```
Busca por "computador": ~0.05-0.1 segundos ⚡
```

**Ganho:** 50-100x mais rápido!

---

## 🔍 VERIFICAR SE FUNCIONOU

### No phpMyAdmin:

```sql
-- Verificar índices
SHOW INDEXES FROM licitacoes WHERE Index_type = 'FULLTEXT';

-- Deve retornar 3 índices:
-- idx_objeto
-- idx_nome_orgao
-- idx_busca_completa
```

### Na API:

```bash
curl "https://licita.pub/backend/api/licitacoes/buscar.php?q=serviço&limite=5"
```

Deve retornar JSON com resultados em menos de 1 segundo.

---

## 🆘 PROBLEMAS?

### Erro: "Table 'licitacoes' doesn't exist"
**Solução:** Banco não foi criado. Execute migrations primeiro.

### Erro: "Can't DROP INDEX"
**Solução:** Índice não existe. Ignore e continue.

### Erro: "Duplicate key name"
**Solução:** Índice já existe. Tudo OK!

### Busca não retorna resultados
**Solução:**
1. Tabela pode estar vazia - execute sincronização PNCP
2. Palavra muito curta (< 3 chars) - use termos maiores
3. Controller não foi atualizado - verifique arquivo

---

## 📞 SUPORTE RÁPIDO

**Ver logs de erro:**
```bash
tail -f /home/u590097272/logs/php_errors.log
```

**Testar SQL direto:**
```sql
SELECT COUNT(*) FROM licitacoes
WHERE MATCH(objeto) AGAINST('computador' IN BOOLEAN MODE);
```

---

## 📦 ARQUIVOS CRIADOS

1. ✅ `diagnostico_busca.sql` - Script de diagnóstico
2. ✅ `corrigir_busca.sql` - Script de correção (EXECUTAR ESTE!)
3. ✅ `LicitacaoController_FIXED.php` - Controller corrigido (USAR ESTE!)
4. ✅ `testar_busca_completo.sh` - Script de teste
5. ✅ `testar_busca_servidor.php` - Teste via web
6. ✅ `ANALISE_PROBLEMA_BUSCA.md` - Análise completa

---

**Desenvolvido para Licita.pub**
**Tempo total: ~5 minutos**
