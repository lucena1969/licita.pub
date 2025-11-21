# 🚀 Guia Rápido - phpMyAdmin

## ⚠️ Problema Comum: "Acesso negado ao information_schema"

Se você recebeu este erro, use o script simplificado!

---

## ✅ Solução: Execute os Scripts na Ordem

### **Passo 1: Criar o Banco**

1. Abra phpMyAdmin: http://localhost/phpmyadmin
2. Clique em **"SQL"** no menu superior
3. **Copie e cole** TODO o conteúdo do arquivo:
   ```
   01_criar_banco.sql
   ```
4. Clique em **"Executar"**
5. Deve aparecer: "Banco de dados LICITAPUB criado com sucesso!"

---

### **Passo 2: Criar as Tabelas**

**IMPORTANTE:** Use o arquivo **SIMPLIFICADO**!

1. Na sidebar **esquerda**, clique em **"licitapub"** (o banco que você criou)
2. Clique em **"SQL"** no menu superior
3. **Copie e cole** TODO o conteúdo do arquivo:
   ```
   02_criar_tabelas_simples.sql   ← USE ESTE!
   ```
4. Clique em **"Executar"**
5. Aguarde alguns segundos

---

### **Passo 3: Verificar se Deu Certo**

1. Na sidebar esquerda, clique em **"licitapub"**
2. Você deve ver **7 tabelas**:
   - ✅ alertas
   - ✅ favoritos
   - ✅ historico_buscas
   - ✅ itens_licitacao
   - ✅ licitacoes
   - ✅ logs_sincronizacao
   - ✅ usuarios

3. **Teste:** Clique na aba "SQL" e execute:
   ```sql
   SHOW TABLES;
   ```
   Deve listar as 7 tabelas.

---

### **Passo 4 (OPCIONAL): Inserir Dados de Teste**

1. Ainda com "licitapub" selecionado na sidebar
2. Clique em "SQL"
3. **Copie e cole** TODO o conteúdo do arquivo:
   ```
   03_dados_iniciais.sql
   ```
4. Clique em "Executar"
5. Isso cria:
   - 1 usuário de teste (teste@licita.pub / Teste123)
   - 1 licitação de exemplo
   - 4 itens da licitação

---

## 📁 Qual Arquivo Usar?

| Arquivo | Quando Usar |
|---------|-------------|
| `01_criar_banco.sql` | **SEMPRE** - Primeiro passo |
| `02_criar_tabelas_simples.sql` | **SEMPRE** - Segundo passo (VERSÃO RECOMENDADA) |
| `02_criar_tabelas.sql` | Apenas se tiver permissões especiais no MySQL |
| `03_dados_iniciais.sql` | **OPCIONAL** - Apenas para testes |

---

## 🐛 Troubleshooting

### Erro: "Table already exists"

**Significa:** As tabelas já foram criadas antes.

**Soluções:**

**a) Continuar com as tabelas existentes:**
- Ignore o erro e continue

**b) Recriar tudo do zero (CUIDADO: apaga os dados!):**
```sql
-- Execute no SQL do phpMyAdmin
DROP DATABASE licitapub;

-- Depois execute novamente:
-- 01_criar_banco.sql
-- 02_criar_tabelas_simples.sql
```

---

### Erro: "Cannot add foreign key constraint"

**Causa:** Tentou criar uma tabela com foreign key antes da tabela pai.

**Solução:** Execute o script **completo** `02_criar_tabelas_simples.sql` de uma vez só.

---

### Erro: "Access denied"

**Causa:** Usuário sem permissões.

**Solução:**
1. Faça login como root no phpMyAdmin
2. Ou use o usuário correto que tem permissões

---

## ✅ Verificação Final

Execute este SQL para verificar tudo:

```sql
USE licitapub;

-- Ver todas as tabelas
SHOW TABLES;

-- Ver estrutura da tabela usuarios
DESCRIBE usuarios;

-- Ver estrutura da tabela licitacoes
DESCRIBE licitacoes;

-- Contar registros (se executou o script de dados iniciais)
SELECT 'Usuarios' AS Tabela, COUNT(*) AS Total FROM usuarios
UNION ALL
SELECT 'Licitacoes', COUNT(*) FROM licitacoes
UNION ALL
SELECT 'Itens', COUNT(*) FROM itens_licitacao;
```

**Resultado esperado:**
- SHOW TABLES: 7 tabelas
- DESCRIBE: Estrutura detalhada
- SELECT COUNT: 1 usuário, 1 licitação, 4 itens (se executou script de dados)

---

## 🎯 Resumo Rápido

```
1. phpMyAdmin → SQL → Cole "01_criar_banco.sql" → Executar
2. Sidebar → licitapub → SQL → Cole "02_criar_tabelas_simples.sql" → Executar
3. (Opcional) SQL → Cole "03_dados_iniciais.sql" → Executar
4. Verificar: Sidebar deve mostrar 7 tabelas
```

**Pronto! Banco configurado! 🎉**

Agora você pode:
- Configurar o backend Python
- Testar os endpoints da API
- Sincronizar dados do PNCP

---

## 📞 Próximo Passo

Depois de criar o banco, siga o arquivo:
- `INSTALACAO_XAMPP.md` (seção "Passo 4: Configurar Python + Backend")
