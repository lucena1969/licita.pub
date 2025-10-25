# 🚀 Guia Rápido - Executar Migrações

## 📝 **Opção 1: Via phpMyAdmin (MAIS FÁCIL)**

### Para Hostinger / cPanel:
1. Acesse o **phpMyAdmin** no painel de controle
2. Selecione o banco `u590097272_licitapub`
3. Clique na aba **"SQL"**
4. Abra cada arquivo `.sql` na ordem abaixo e execute:

```
001_criar_tabela_orgaos.sql
002_criar_tabela_contratos.sql
003_criar_tabela_atas_registro_preco.sql
004_criar_tabela_planos_contratacao_anual.sql
```

5. Pronto! ✅

---

## 💻 **Opção 2: Via Terminal/SSH (AVANÇADO)**

### Pré-requisitos:
```bash
# Acesso SSH ao servidor
ssh usuario@servidor

# Navegar até o diretório
cd /caminho/para/licita.pub/database/migrations
```

### Executar com MySQL CLI:
```bash
mysql -u u590097272_licitapub -p u590097272_licitapub < 001_criar_tabela_orgaos.sql
mysql -u u590097272_licitapub -p u590097272_licitapub < 002_criar_tabela_contratos.sql
mysql -u u590097272_licitapub -p u590097272_licitapub < 003_criar_tabela_atas_registro_preco.sql
mysql -u u590097272_licitapub -p u590097272_licitapub < 004_criar_tabela_planos_contratacao_anual.sql
```

---

## 🐘 **Opção 3: Via Script PHP (AUTOMATIZADO)**

### Local (XAMPP/WAMP):
```bash
cd C:\xampp\htdocs\licita.pub\database\migrations
php executar_migracoes.php
```

### Linux/Servidor:
```bash
cd /var/www/licita.pub/database/migrations
php executar_migracoes.php
```

### Comandos disponíveis:
```bash
# Executar todas as migrações
php executar_migracoes.php

# Verificar status
php executar_migracoes.php --verificar

# Reverter tudo (CUIDADO!)
php executar_migracoes.php --rollback
```

---

## ✅ **Verificar se Funcionou**

Execute este SQL no phpMyAdmin:

```sql
-- Listar todas as tabelas
SHOW TABLES;

-- Verificar registros de exemplo
SELECT 'orgaos' AS tabela, COUNT(*) AS registros FROM orgaos
UNION ALL
SELECT 'contratos', COUNT(*) FROM contratos
UNION ALL
SELECT 'atas_registro_preco', COUNT(*) FROM atas_registro_preco
UNION ALL
SELECT 'planos_contratacao_anual', COUNT(*) FROM planos_contratacao_anual;
```

**Resultado esperado:**
- `orgaos`: 1 registro
- `contratos`: 1 registro
- `atas_registro_preco`: 1 registro
- `planos_contratacao_anual`: 3 registros

---

## ⚠️ **Possíveis Erros**

### Erro: "Table already exists"
**Solução:** Alguma tabela já foi criada antes. Execute:
```sql
DROP TABLE IF EXISTS orgaos, contratos, atas_registro_preco, planos_contratacao_anual;
```
E tente novamente.

### Erro: "Cannot add foreign key constraint"
**Solução:** A tabela base ainda não existe. Certifique-se de que `licitacoes` e `usuarios` existem:
```sql
SHOW TABLES LIKE 'licitacoes';
SHOW TABLES LIKE 'usuarios';
```

### Erro: "Access denied"
**Solução:** Verifique as credenciais do banco de dados no `.env`:
```bash
DB_HOST=localhost
DB_DATABASE=u590097272_licitapub
DB_USERNAME=u590097272_licitapub
DB_PASSWORD=sua_senha_aqui
```

---

## 📞 **Precisa de Ajuda?**

- 📧 Email: contato@licita.pub
- 📚 Documentação completa: `README.md`
- 🐛 Reportar problemas: GitHub Issues

---

**Tempo estimado:** 5-10 minutos ⏱️
