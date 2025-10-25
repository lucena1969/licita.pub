# ✅ Correções Aplicadas nas Migrações

## 🐛 Problema Identificado

**Erro:** `#1452 - Cannot add or update a child row: a foreign key constraint fails`

**Causa:** Os scripts estavam tentando inserir dados de exemplo nas tabelas filhas (contratos, atas, PCAs) antes de garantir que os dados da tabela pai (órgãos) existissem.

---

## 🔧 Correções Realizadas

### **1. Script 002 - Contratos** ✅

**Antes:**
```sql
INSERT INTO contratos (...) VALUES (...)
```

**Depois:**
```sql
INSERT INTO contratos (...)
SELECT ...
FROM DUAL
WHERE EXISTS (SELECT 1 FROM orgaos WHERE id = '00000000000001')
AND NOT EXISTS (SELECT 1 FROM contratos WHERE pncp_id = 'CONTRATO-EXEMPLO-001-2025');
```

**Benefício:** Só insere o contrato se o órgão existir + previne duplicatas.

---

### **2. Script 003 - Atas de Registro de Preço** ✅

**Antes:**
```sql
INSERT INTO atas_registro_preco (...) VALUES (...)
SET @ata_id = (SELECT id FROM atas_registro_preco ...)
INSERT INTO itens_ata (...) VALUES (UUID(), @ata_id, ...)
```

**Depois:**
```sql
-- ARP
INSERT INTO atas_registro_preco (...)
SELECT ...
WHERE EXISTS (SELECT 1 FROM orgaos WHERE id = '00000000000001')
AND NOT EXISTS (SELECT 1 FROM atas_registro_preco WHERE pncp_id = '...');

-- Itens da ARP
INSERT INTO itens_ata (...)
SELECT ... FROM atas_registro_preco arp
WHERE arp.pncp_id = 'ARP-EXEMPLO-001-2025'
AND NOT EXISTS (SELECT 1 FROM itens_ata WHERE ata_id = arp.id AND numero_item = 1);
```

**Benefício:**
- Não usa variáveis (@ata_id)
- Verifica existência do órgão
- Previne duplicatas nos itens

---

### **3. Script 004 - Planos de Contratação Anual** ✅

**Antes:**
```sql
INSERT INTO planos_contratacao_anual (...) VALUES (...), (...), (...)
UPDATE planos_contratacao_anual SET licitacao_id = '...' WHERE pncp_id = '...';
```

**Depois:**
```sql
INSERT INTO planos_contratacao_anual (...)
SELECT * FROM (
  SELECT ... UNION ALL
  SELECT ... UNION ALL
  SELECT ...
) AS novos_pca
WHERE EXISTS (SELECT 1 FROM orgaos WHERE id = '00000000000001')
AND NOT EXISTS (SELECT 1 FROM planos_contratacao_anual WHERE pncp_id IN (...));

UPDATE planos_contratacao_anual
SET licitacao_id = '...'
WHERE pncp_id = '...'
  AND licitacao_id IS NULL
  AND EXISTS (SELECT 1 FROM licitacoes WHERE id = '...');
```

**Benefício:**
- Insere múltiplos registros de uma vez
- Verifica existência do órgão
- UPDATE condicional (só se licitação existir)

---

## 📋 Como Executar Agora

### **Opção 1: Limpar e Re-executar (RECOMENDADO)**

Se você já executou os scripts com erro, faça:

```sql
-- 1. Limpar tabelas problemáticas (ordem inversa)
DROP TABLE IF EXISTS adesoes_ata;
DROP TABLE IF EXISTS itens_ata;
DROP TABLE IF EXISTS atas_registro_preco;
DROP TABLE IF EXISTS planos_contratacao_anual;
DROP TABLE IF EXISTS categorias_pca;
DROP TABLE IF EXISTS aditivos_contratuais;
DROP TABLE IF EXISTS contratos;

-- 2. Re-executar os scripts corrigidos na ORDEM:
-- Execute via phpMyAdmin:
-- ✅ 001_criar_tabela_orgaos.sql (já funcionou)
-- ✅ 002_criar_tabela_contratos.sql (corrigido)
-- ✅ 003_criar_tabela_atas_registro_preco.sql (corrigido)
-- ✅ 004_criar_tabela_planos_contratacao_anual.sql (corrigido)
```

### **Opção 2: Continuar de onde parou**

Se você parou no script 002:

```sql
-- 1. Verificar se órgão foi criado
SELECT * FROM orgaos WHERE id = '00000000000001';

-- Se NÃO retornou nada, insira manualmente:
INSERT INTO orgaos (id, cnpj, razao_social, nome_fantasia, esfera, poder, uf, municipio, tipo)
VALUES (
  '00000000000001',
  '00000000000001',
  'PREFEITURA MUNICIPAL DE EXEMPLO',
  'PMEXEMPLO',
  'MUNICIPAL',
  'EXECUTIVO',
  'SP',
  'São Paulo',
  'Prefeitura'
);

-- 2. Agora execute os scripts corrigidos:
-- ✅ 002_criar_tabela_contratos.sql
-- ✅ 003_criar_tabela_atas_registro_preco.sql
-- ✅ 004_criar_tabela_planos_contratacao_anual.sql
```

---

## ✅ Verificar se Funcionou

Execute após os scripts:

```sql
-- Verificar todas as tabelas criadas
SHOW TABLES;

-- Verificar registros
SELECT 'orgaos' AS tabela, COUNT(*) AS registros FROM orgaos
UNION ALL SELECT 'contratos', COUNT(*) FROM contratos
UNION ALL SELECT 'aditivos_contratuais', COUNT(*) FROM aditivos_contratuais
UNION ALL SELECT 'atas_registro_preco', COUNT(*) FROM atas_registro_preco
UNION ALL SELECT 'itens_ata', COUNT(*) FROM itens_ata
UNION ALL SELECT 'adesoes_ata', COUNT(*) FROM adesoes_ata
UNION ALL SELECT 'planos_contratacao_anual', COUNT(*) FROM planos_contratacao_anual
UNION ALL SELECT 'categorias_pca', COUNT(*) FROM categorias_pca;
```

**Resultado esperado:**
```
orgaos                      → 1
contratos                   → 1
aditivos_contratuais        → 0
atas_registro_preco         → 1
itens_ata                   → 2
adesoes_ata                 → 0
planos_contratacao_anual    → 3
categorias_pca              → 11
```

---

## 🎯 Próximos Passos

Após executar com sucesso:

1. ✅ **Delete dados de exemplo em produção:**
   ```sql
   DELETE FROM contratos WHERE pncp_id LIKE '%EXEMPLO%';
   DELETE FROM atas_registro_preco WHERE pncp_id LIKE '%EXEMPLO%';
   DELETE FROM planos_contratacao_anual WHERE pncp_id LIKE '%EXEMPLO%';
   DELETE FROM orgaos WHERE id = '00000000000001';
   ```

2. ✅ **Desenvolver integração com PNCP**
3. ✅ **Criar Models e Repositories**
4. ✅ **Desenvolver API REST**

---

## 📞 Dúvidas?

Se ainda tiver problemas:

1. Verifique se executou na **ordem correta** (001 → 002 → 003 → 004)
2. Certifique-se de que o script **001** foi executado com sucesso
3. Verifique se a tabela `orgaos` tem o registro com id = '00000000000001'

**Agora pode executar sem erros!** ✅
