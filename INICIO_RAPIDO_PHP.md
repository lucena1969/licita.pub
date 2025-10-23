# 🚀 Início Rápido - Licita.pub PHP

## ⚡ Setup em 5 Minutos

### 📋 Pré-requisitos
- ✅ XAMPP instalado (MySQL rodando)
- ✅ Composer instalado
- ✅ Projeto baixado em `C:\xampp\htdocs\licita.pub`

---

## 🎯 Passo 1: Criar Banco de Dados

### Abra o phpMyAdmin
http://localhost/phpmyadmin

### Execute os SQLs na ordem:

**1. Criar banco:**
```sql
DROP DATABASE IF EXISTS licitapub;
CREATE DATABASE licitapub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**2. Criar tabelas:**
- Clique no banco `licitapub` (sidebar)
- Clique em **SQL** (menu superior)
- Cole TODO o conteúdo de: `backend/sql/02_criar_tabelas_simples.sql`
- Clique em **Executar**

**Resultado esperado:**
```
✓ 7 tabelas criadas com sucesso!
```

---

## 🎯 Passo 2: Instalar Dependências PHP

Abra o terminal (cmd) na pasta backend:

```bash
cd C:\xampp\htdocs\licita.pub\backend

# Instalar dependências via Composer
composer install
```

**O que será instalado:**
- `vlucas/phpdotenv` - Carregar variáveis .env
- `firebase/php-jwt` - Autenticação JWT
- `guzzlehttp/guzzle` - HTTP Client para PNCP

---

## 🎯 Passo 3: Configurar .env

```bash
# Copiar exemplo
copy .env.example .env

# Editar
notepad .env
```

**Configure:**
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=licitapub
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=COLE_AQUI_A_CHAVE_GERADA_ABAIXO
```

**Gerar JWT_SECRET:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

Copie o resultado e cole no `.env` na linha `JWT_SECRET=`

---

## 🎯 Passo 4: Testar Conexão

Crie um arquivo de teste:

**C:\xampp\htdocs\licita.pub\backend\test_db.php**
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Config;
use App\Config\Database;

Config::load();

try {
    $db = Database::getConnection();
    echo "✅ Conexão com MySQL OK!\n\n";

    // Testar query
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "Total de usuários: " . $result['total'] . "\n";

    // Listar tabelas
    $stmt = $db->query("SHOW TABLES");
    echo "\nTabelas criadas:\n";
    while ($row = $stmt->fetch()) {
        echo "  - " . $row[0] . "\n";
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
```

**Execute:**
```bash
php test_db.php
```

**Resultado esperado:**
```
✅ Conexão com MySQL OK!

Total de usuários: 0

Tabelas criadas:
  - usuarios
  - licitacoes
  - itens_licitacao
  - favoritos
  - alertas
  - historico_buscas
  - logs_sincronizacao
```

---

## ✅ Verificação Final

Se tudo deu certo, você tem:

- ✅ Banco `licitapub` criado no MySQL
- ✅ 7 tabelas criadas
- ✅ Composer instalado e dependências baixadas
- ✅ Arquivo `.env` configurado
- ✅ Conexão com banco testada e funcionando

---

## 🎯 Próximos Passos

Agora vamos criar:

1. **Services** - Lógica de negócio
2. **Controllers** - Validação e resposta HTTP
3. **API Routes** - Endpoints REST
4. **Utils** - JWT, Validator, Response
5. **Frontend** - Interface HTML

---

## 📂 Estrutura Atual

```
backend/
├── src/
│   ├── Config/
│   │   ├── Database.php     ✅ Criado
│   │   └── Config.php       ✅ Criado
│   ├── Models/
│   │   ├── Usuario.php      ✅ Criado
│   │   └── Licitacao.php    ✅ Criado
│   ├── Repositories/
│   │   ├── UsuarioRepository.php    ✅ Criado
│   │   └── LicitacaoRepository.php  ✅ Criado
│   └── (outros em desenvolvimento...)
│
├── sql/
│   ├── 01_criar_banco.sql              ✅
│   └── 02_criar_tabelas_simples.sql    ✅
│
├── .env                     ✅ Configure
├── .env.example            ✅ Template
├── composer.json           ✅ Criado
└── test_db.php             ⏳ Crie para testar
```

---

## 🐛 Problemas Comuns

### Erro: "Class 'App\Config\Database' not found"
**Solução:**
```bash
composer dump-autoload
```

### Erro: "Access denied for user 'root'"
**Solução:**
Verifique a senha do MySQL no `.env`. XAMPP padrão não tem senha:
```env
DB_PASSWORD=
```

### Erro: "Unknown database 'licitapub'"
**Solução:**
Execute o SQL para criar o banco no phpMyAdmin.

---

## 💡 Comandos Úteis

```bash
# Instalar dependências
composer install

# Atualizar autoload
composer dump-autoload

# Testar conexão
php test_db.php

# Gerar JWT secret
php -r "echo bin2hex(random_bytes(32));"
```

---

**Status:** Estrutura básica pronta ✅
**Próximo:** Implementar Services, Controllers e API REST

**Bom desenvolvimento! 🚀**
