# 📦 Migrações do Banco de Dados - Licita.pub

Este diretório contém todos os scripts SQL de migração para expandir o banco de dados do licita.pub com as funcionalidades necessárias para integração com o PNCP.

---

## 📋 **Estrutura das Migrações**

### **Scripts Disponíveis**

| Arquivo | Descrição | Dependências |
|---------|-----------|--------------|
| `000_EXECUTAR_TODAS_MIGRACOES.sql` | **Script Master** - Executa todas as migrações | Nenhuma |
| `001_criar_tabela_orgaos.sql` | Cria tabela de órgãos públicos | Nenhuma |
| `002_criar_tabela_contratos.sql` | Cria tabelas de contratos e aditivos | 001, tabelas base |
| `003_criar_tabela_atas_registro_preco.sql` | Cria tabelas de ARPs, itens e adesões | 001, tabelas base |
| `004_criar_tabela_planos_contratacao_anual.sql` | Cria tabela de PCAs e categorias | 001, tabelas base |

---

## 🚀 **Como Executar as Migrações**

### **Opção 1: Executar TODAS de uma vez (RECOMENDADO)**

#### **Via phpMyAdmin:**
1. Acesse o phpMyAdmin
2. Selecione o banco `licitapub` (ou `u590097272_licitapub` em produção)
3. Clique na aba **SQL**
4. Copie e cole o conteúdo de `000_EXECUTAR_TODAS_MIGRACOES.sql`
5. Clique em **Executar**

#### **Via linha de comando (MySQL CLI):**
```bash
# Navegue até o diretório de migrações
cd /workspaces/licita.pub/database/migrations

# Execute o script master
mysql -u root -p licitapub < 000_EXECUTAR_TODAS_MIGRACOES.sql
```

#### **Via linha de comando (com SOURCE):**
```bash
mysql -u root -p licitapub
```

Dentro do MySQL:
```sql
SOURCE /workspaces/licita.pub/database/migrations/000_EXECUTAR_TODAS_MIGRACOES.sql;
```

---

### **Opção 2: Executar INDIVIDUALMENTE (para debug)**

Execute os scripts na **ordem correta**:

```bash
# 1. Órgãos (independente)
mysql -u root -p licitapub < 001_criar_tabela_orgaos.sql

# 2. Contratos
mysql -u root -p licitapub < 002_criar_tabela_contratos.sql

# 3. Atas de Registro de Preço
mysql -u root -p licitapub < 003_criar_tabela_atas_registro_preco.sql

# 4. Planos de Contratação Anual
mysql -u root -p licitapub < 004_criar_tabela_planos_contratacao_anual.sql
```

---

## 📊 **Tabelas Criadas**

### **1. `orgaos`** (Migração 001)
Armazena informações de órgãos públicos do PNCP.

**Campos principais:**
- `id` - ID do órgão no PNCP
- `cnpj` - CNPJ do órgão
- `razao_social` - Nome oficial
- `esfera` - FEDERAL, ESTADUAL, MUNICIPAL
- `poder` - EXECUTIVO, LEGISLATIVO, JUDICIARIO
- `uf`, `municipio` - Localização

**Índices especiais:**
- FULLTEXT em `razao_social` e `nome_fantasia` (busca textual)
- Índices compostos para filtros rápidos

---

### **2. `contratos`** (Migração 002)
Armazena contratos públicos firmados.

**Campos principais:**
- `pncp_id` - ID único no PNCP
- `licitacao_id` - Vínculo com licitação origem (pode ser NULL)
- `orgao_id` - Órgão contratante
- `contratado_nome`, `contratado_cnpj` - Dados do contratado
- `valor_inicial`, `valor_atual` - Valores
- `data_assinatura`, `data_inicio`, `data_fim` - Datas
- `situacao` - ATIVO, ENCERRADO, SUSPENSO, RESCINDIDO

**Tabela relacionada:** `aditivos_contratuais`

---

### **3. `atas_registro_preco`** (Migração 003)
Armazena Atas de Registro de Preço (ARPs).

**Campos principais:**
- `pncp_id` - ID único no PNCP
- `licitacao_id` - Licitação origem
- `orgao_gerenciador_id` - Órgão gestor da ARP
- `data_vigencia_inicio`, `data_vigencia_fim` - Período de validade
- `permite_adesao` - Se aceita carona de outros órgãos

**Tabelas relacionadas:**
- `itens_ata` - Itens registrados na ARP com preços e fornecedores
- `adesoes_ata` - Órgãos que aderiram (carona)

---

### **4. `planos_contratacao_anual`** (Migração 004)
Armazena itens do Plano de Contratação Anual (PCA).

**Campos principais:**
- `pncp_id` - ID único no PNCP
- `orgao_id` - Órgão planejador
- `ano` - Ano do planejamento
- `descricao` - O que será contratado
- `categoria` - OBRAS, SERVICOS, TI, SAUDE, etc
- `valor_estimado` - Valor previsto
- `data_prevista` - Quando será licitado
- `situacao` - PLANEJADO, EM_LICITACAO, CONTRATADO, CANCELADO

**Tabelas relacionadas:**
- `categorias_pca` - Categorias auxiliares
- `v_pca_resumo` - View para análise estatística

---

## 🔗 **Diagrama de Relacionamentos (ERD)**

```
usuarios (1) ──> (N) alertas
    │
    ├──> (N) favoritos ──> (N) licitacoes
    │
    └──> (N) historico_buscas

orgaos (1) ──> (N) licitacoes
    │
    ├──> (N) contratos (1) ──> (N) aditivos_contratuais
    │
    ├──> (N) atas_registro_preco (1) ──> (N) itens_ata
    │                            │
    │                            └──> (N) adesoes_ata
    │
    └──> (N) planos_contratacao_anual

licitacoes (1) ──> (N) itens_licitacao
    │
    ├──> (0..1) contratos
    ├──> (0..1) atas_registro_preco
    └──> (0..1) planos_contratacao_anual
```

---

## ⚠️ **Requisitos e Considerações**

### **Pré-requisitos:**
✅ MySQL 5.7+ ou MariaDB 10.3+
✅ Charset: `utf8mb4`
✅ Collation: `utf8mb4_unicode_ci`
✅ Tabelas base já existentes: `usuarios`, `licitacoes`, `itens_licitacao`, `alertas`, `favoritos`, `historico_buscas`, `logs_sincronizacao`

### **Permissões necessárias:**
- `CREATE TABLE`
- `CREATE INDEX`
- `ALTER TABLE` (para foreign keys)
- `INSERT` (para dados de exemplo)

### **Espaço em disco estimado:**
- Estruturas vazias: ~100 KB
- Com 10.000 licitações sincronizadas: ~50 MB
- Com 1 ano de dados (estimado): ~500 MB - 1 GB

---

## 🧪 **Testes de Verificação**

Após executar as migrações, rode estes comandos para verificar:

```sql
-- Verificar se todas as tabelas foram criadas
SHOW TABLES;

-- Contar registros de exemplo
SELECT 'orgaos' AS tabela, COUNT(*) AS registros FROM orgaos
UNION ALL
SELECT 'contratos', COUNT(*) FROM contratos
UNION ALL
SELECT 'atas_registro_preco', COUNT(*) FROM atas_registro_preco
UNION ALL
SELECT 'itens_ata', COUNT(*) FROM itens_ata
UNION ALL
SELECT 'planos_contratacao_anual', COUNT(*) FROM planos_contratacao_anual;

-- Verificar foreign keys
SELECT
  TABLE_NAME AS tabela,
  CONSTRAINT_NAME AS constraint,
  REFERENCED_TABLE_NAME AS tabela_referenciada
FROM information_schema.KEY_COLUMN_USAGE
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;

-- Verificar índices FULLTEXT
SELECT
  TABLE_NAME AS tabela,
  INDEX_NAME AS indice,
  GROUP_CONCAT(COLUMN_NAME) AS colunas
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_TYPE = 'FULLTEXT'
GROUP BY TABLE_NAME, INDEX_NAME;
```

---

## 🔄 **Rollback (Reverter Migrações)**

Se precisar **desfazer** as migrações, execute na **ordem inversa**:

```sql
-- ATENÇÃO: Isso vai DELETAR todas as tabelas e dados!

DROP TABLE IF EXISTS adesoes_ata;
DROP TABLE IF EXISTS itens_ata;
DROP TABLE IF EXISTS atas_registro_preco;

DROP TABLE IF EXISTS planos_contratacao_anual;
DROP TABLE IF EXISTS categorias_pca;

DROP TABLE IF EXISTS aditivos_contratuais;
DROP TABLE IF EXISTS contratos;

DROP TABLE IF EXISTS orgaos;

-- Remover foreign key de licitacoes
ALTER TABLE licitacoes DROP FOREIGN KEY IF EXISTS fk_licitacoes_orgao;
```

---

## 📝 **Dados de Exemplo**

Cada migração insere **dados de exemplo** para facilitar testes:
- 1 órgão de exemplo (Prefeitura Municipal de Exemplo)
- 1 contrato vinculado à licitação de teste
- 1 ARP com 2 itens
- 3 itens de PCA para 2025

**Para remover dados de exemplo em produção:**
```sql
DELETE FROM orgaos WHERE id = '00000000000001';
DELETE FROM contratos WHERE pncp_id LIKE '%EXEMPLO%';
DELETE FROM atas_registro_preco WHERE pncp_id LIKE '%EXEMPLO%';
DELETE FROM planos_contratacao_anual WHERE pncp_id LIKE '%EXEMPLO%';
```

---

## 🚀 **Próximos Passos**

Após executar as migrações:

1. ✅ **Criar Models PHP** para as novas tabelas
2. ✅ **Criar Repositories** para acesso aos dados
3. ✅ **Criar Services** para lógica de negócio
4. ✅ **Desenvolver sincronizador PNCP** (cron job)
5. ✅ **Criar endpoints da API REST**

---

## 📞 **Suporte**

Em caso de dúvidas ou problemas:
- 📧 Email: contato@licita.pub
- 📚 Documentação: [README.md do projeto]
- 🐛 Issues: [GitHub Issues]

---

## 📌 **Changelog**

### **v1.0.0** - 2025-10-25
- ✅ Criação inicial das 4 migrações
- ✅ Tabelas: orgaos, contratos, atas_registro_preco, planos_contratacao_anual
- ✅ Índices otimizados e foreign keys
- ✅ Dados de exemplo para testes

---

**Desenvolvido com ❤️ para o Licita.pub**
