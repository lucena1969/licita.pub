# 🏠 Guia Completo - Setup Localhost (Windows/XAMPP)

> **Objetivo:** Configurar o licita.pub para rodar 100% no seu computador local

---

## 📦 O que você precisa ter instalado

### 1. XAMPP (MySQL + phpMyAdmin)
- **Download:** https://www.apachefriends.org/
- **Versão:** 8.2.x ou superior
- **Instalar em:** `C:\xampp`

### 2. Python
- **Download:** https://www.python.org/downloads/
- **Versão:** 3.11 ou 3.12
- **IMPORTANTE:** ✅ Marcar "Add Python to PATH" durante instalação

### 3. Git (Opcional, mas recomendado)
- **Download:** https://git-scm.com/download/win
- Para clonar o repositório

### 4. Editor de Código (Opcional)
- **VSCode:** https://code.visualstudio.com/
- Ou qualquer editor de sua preferência

---

## 🚀 Passo a Passo - Do Zero ao Funcionando

### 📁 **ETAPA 1: Baixar o Projeto**

#### Opção A: Via Git (Recomendado)
```bash
# Abra o terminal (cmd ou PowerShell)
cd C:\xampp\htdocs

# Clone o repositório
git clone https://github.com/lucena1969/licita.pub.git

# Entre na pasta
cd licita.pub
```

#### Opção B: Download Manual
1. Acesse: https://github.com/lucena1969/licita.pub
2. Clique em **Code** → **Download ZIP**
3. Extraia para: `C:\xampp\htdocs\licita.pub`

**Verificação:**
```bash
dir
# Deve mostrar: backend, frontend, README.md, etc.
```

---

### 🗄️ **ETAPA 2: Configurar MySQL**

#### 2.1 Iniciar XAMPP
1. Abra **XAMPP Control Panel**
2. Clique em **Start** ao lado de **MySQL**
3. Aguarde até aparecer: `Port(s): 3306`

#### 2.2 Criar Banco de Dados

**Opção 1: Via phpMyAdmin (MAIS FÁCIL)**

1. Abra: http://localhost/phpmyadmin
2. Clique em **SQL** (menu superior)
3. Execute os scripts na ordem:

**Script 1 - Criar o banco:**
```sql
DROP DATABASE IF EXISTS licitapub;
CREATE DATABASE licitapub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE licitapub;
SELECT 'Banco de dados LICITAPUB criado com sucesso!' AS Status;
```

**Script 2 - Criar as tabelas:**
```sql
-- Cole TODO o conteúdo do arquivo: backend/sql/02_criar_tabelas_simples.sql
-- Ou use o init_db.py (próxima etapa)
```

**Opção 2: Deixar o Python criar automaticamente (RECOMENDADO)**
- Vamos fazer isso na próxima etapa com o comando `python init_db.py`

---

### 🐍 **ETAPA 3: Configurar Backend Python**

#### 3.1 Abrir Terminal na Pasta do Backend
```bash
# No terminal/cmd
cd C:\xampp\htdocs\licita.pub\backend
```

#### 3.2 Criar Ambiente Virtual Python
```bash
# Criar venv
python -m venv venv

# Ativar venv (Windows CMD)
venv\Scripts\activate

# OU se estiver no PowerShell
venv\Scripts\Activate.ps1

# OU se estiver no Git Bash
source venv/Scripts/activate
```

**Como saber se ativou?**
- Deve aparecer `(venv)` no início da linha do terminal

#### 3.3 Instalar Dependências
```bash
# Com venv ativo
pip install --upgrade pip
pip install -r requirements.txt

# Aguarde (pode levar 2-5 minutos)
```

**Verificação:**
```bash
pip list
# Deve mostrar: fastapi, uvicorn, sqlalchemy, etc.
```

#### 3.4 Configurar Arquivo .env

**Copiar exemplo:**
```bash
copy .env.example .env
```

**Editar o .env:**

Abra o arquivo `.env` com Notepad++ ou VSCode e configure:

```env
# ========================================
# CONFIGURAÇÃO LOCAL - XAMPP
# ========================================

# AMBIENTE
ENVIRONMENT=development

# DATABASE - MySQL XAMPP
# Se MySQL não tem senha (padrão XAMPP):
DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub

# Se você definiu senha no MySQL:
# DATABASE_URL=mysql+pymysql://root:SUA_SENHA@localhost:3306/licitapub

# SEGURANÇA - GERE UMA CHAVE NOVA!
SECRET_KEY=GERE_UMA_CHAVE_AQUI_VEJA_ABAIXO
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=10080

# EMAIL VERIFICATION TOKEN (7 dias)
EMAIL_VERIFICATION_EXPIRE_HOURS=168

# EMAIL SMTP (configure depois se quiser enviar emails)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASSWORD=
SMTP_FROM=noreply@licita.pub
SMTP_FROM_NAME=Licita.pub

# URLs
FRONTEND_URL=http://localhost:5173
BACKEND_URL=http://localhost:8000

# PNCP API
PNCP_API_URL=https://pncp.gov.br/api/consulta/v1
PNCP_USERNAME=
PNCP_PASSWORD=

# REDIS (deixe vazio por enquanto)
REDIS_URL=

# GOOGLE ADSENSE (deixe vazio)
ADSENSE_CLIENT_ID=

# LOGS
LOG_LEVEL=INFO
```

**Gerar SECRET_KEY:**
```bash
# No terminal com venv ativo
python -c "import secrets; print(secrets.token_hex(32))"

# Copie o resultado e cole no .env
```

#### 3.5 Inicializar Banco de Dados
```bash
# Com venv ativo, na pasta backend
python init_db.py
```

**Resultado esperado:**
```
============================================================
Criando tabelas no banco de dados...
============================================================

Models carregados:
  - Usuario
  - Licitacao
  - ItemLicitacao
  - Favorito
  - Alerta
  - HistoricoBusca
  - LogSincronizacao

✓ Tabelas criadas com sucesso!
```

#### 3.6 Testar Integração com PNCP
```bash
python test_pncp.py
```

**Resultado esperado:**
```
✓ Integração com PNCP funcionando corretamente!
```

---

### ▶️ **ETAPA 4: Iniciar o Servidor**

```bash
# Na pasta backend, com venv ativo
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

**Resultado esperado:**
```
INFO:     Uvicorn running on http://127.0.0.1:8000 (Press CTRL+C to quit)
INFO:     Started reloader process
INFO:     Application startup complete.
============================================================
Licita.pub API iniciando...
Ambiente: development
============================================================
```

**✅ Servidor rodando!**

---

### 🧪 **ETAPA 5: Testar a API**

#### 5.1 Abrir no Navegador

**Documentação Interativa:**
- http://localhost:8000/docs

**Health Check:**
- http://localhost:8000/health

#### 5.2 Testar Endpoints via Terminal

Abra um **NOVO terminal** (não feche o servidor):

**Teste 1: Health Check**
```bash
curl http://localhost:8000/health
```

**Teste 2: Registrar Usuário**
```bash
curl -X POST "http://localhost:8000/api/v1/auth/registrar" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"teste@licita.pub\",\"senha\":\"Senha123\",\"confirmar_senha\":\"Senha123\",\"nome\":\"Usuario Teste\",\"telefone\":\"(11) 98888-8888\"}"
```

**Teste 3: Fazer Login**
```bash
curl -X POST "http://localhost:8000/api/v1/auth/login" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"teste@licita.pub\",\"senha\":\"Senha123\"}"
```

**Teste 4: Sincronizar Licitações**
```bash
curl -X POST "http://localhost:8000/api/v1/licitacoes/sincronizar?data_inicial=20251016&data_final=20251023&limite=100"
```

**Teste 5: Listar Licitações**
```bash
curl "http://localhost:8000/api/v1/licitacoes/recentes?limite=5"
```

#### 5.3 Testar via Swagger UI

1. Acesse: http://localhost:8000/docs
2. Clique em **POST /api/v1/auth/registrar**
3. Clique em **Try it out**
4. Preencha os dados e clique em **Execute**
5. Veja a resposta abaixo

---

## 📂 Estrutura Final do Projeto

Após tudo configurado:

```
C:\xampp\htdocs\licita.pub\
├── backend\
│   ├── app\                    # Código da aplicação
│   ├── sql\                    # Scripts SQL
│   ├── venv\                   # Ambiente virtual ✅
│   ├── .env                    # Configurações ✅
│   ├── licitapub.db           # Banco SQLite (se usar)
│   ├── requirements.txt
│   ├── init_db.py
│   └── test_pncp.py
├── frontend\                   # (será desenvolvido)
├── README.md
├── SETUP_LOCAL.md
└── GUIA_LOCALHOST_COMPLETO.md  # Este arquivo
```

---

## 🛠️ Comandos Úteis

### Ativar ambiente virtual
```bash
# Windows CMD
cd C:\xampp\htdocs\licita.pub\backend
venv\Scripts\activate

# PowerShell
venv\Scripts\Activate.ps1

# Git Bash
source venv/Scripts/activate
```

### Desativar ambiente virtual
```bash
deactivate
```

### Iniciar servidor
```bash
# Com venv ativo
uvicorn app.main:app --reload
```

### Parar servidor
- Pressione **Ctrl+C** no terminal do servidor

### Verificar MySQL rodando
```bash
# Abra XAMPP Control Panel
# MySQL deve estar com status "Running" e porta 3306
```

### Resetar banco de dados
```bash
# Com venv ativo
python init_db.py
```

---

## 🐛 Solução de Problemas Comuns

### Erro: "python não reconhecido"
**Causa:** Python não está no PATH

**Solução:**
1. Reinstale Python marcando "Add to PATH"
2. OU adicione manualmente:
   - Painel de Controle → Sistema → Variáveis de Ambiente
   - Adicione: `C:\Python312` (ou onde instalou)

---

### Erro: "No module named 'fastapi'"
**Causa:** Ambiente virtual não está ativo ou dependências não instaladas

**Solução:**
```bash
# 1. Ative o venv
venv\Scripts\activate

# 2. Instale dependências
pip install -r requirements.txt
```

---

### Erro: "Can't connect to MySQL server"
**Causa:** MySQL não está rodando

**Solução:**
1. Abra XAMPP Control Panel
2. Clique em **Start** ao lado de MySQL
3. Aguarde aparecer a porta 3306
4. Se não iniciar, verifique:
   - Porta 3306 em uso por outro programa
   - Logs do MySQL no XAMPP

---

### Erro: "Access denied for user 'root'"
**Causa:** Senha do MySQL no .env está errada

**Solução:**
- XAMPP padrão não tem senha:
  ```env
  DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub
  ```
- Se definiu senha:
  ```env
  DATABASE_URL=mysql+pymysql://root:SUA_SENHA@localhost:3306/licitapub
  ```

---

### Erro: "Table doesn't exist"
**Causa:** Banco não foi criado ou tabelas não existem

**Solução:**
```bash
python init_db.py
```

---

### Porta 8000 já em uso
**Causa:** Outro processo usando a porta

**Solução:**
```bash
# Use outra porta
uvicorn app.main:app --reload --port 8001
# Acesse: http://localhost:8001
```

---

### Servidor parou de funcionar
**Solução:**
```bash
# Reinicie o servidor
# Pressione Ctrl+C para parar
# Execute novamente:
uvicorn app.main:app --reload
```

---

## 📝 Workflow Diário de Desenvolvimento

Quando for trabalhar no projeto:

```bash
# 1. Iniciar XAMPP
# - Abra XAMPP Control Panel
# - Start: MySQL

# 2. Abrir terminal na pasta backend
cd C:\xampp\htdocs\licita.pub\backend

# 3. Ativar ambiente virtual
venv\Scripts\activate

# 4. Iniciar servidor
uvicorn app.main:app --reload

# 5. Acessar documentação
# http://localhost:8000/docs

# 6. Quando terminar
# Ctrl+C para parar servidor
# deactivate para sair do venv
# Stop MySQL no XAMPP
```

---

## 🎯 Checklist de Verificação

Use este checklist para garantir que tudo está funcionando:

- [ ] XAMPP instalado e MySQL rodando (porta 3306)
- [ ] Python instalado (versão 3.11+)
- [ ] Projeto baixado em `C:\xampp\htdocs\licita.pub`
- [ ] Banco de dados `licitapub` criado
- [ ] Ambiente virtual criado (`venv` existe)
- [ ] Dependências instaladas (`pip list` mostra fastapi)
- [ ] Arquivo `.env` configurado
- [ ] Tabelas criadas (`python init_db.py` executado)
- [ ] Servidor rodando (http://localhost:8000)
- [ ] Swagger funcionando (http://localhost:8000/docs)
- [ ] Teste de usuário funcionou (registro + login)
- [ ] Integração PNCP testada (`python test_pncp.py`)

---

## 🎓 Próximos Passos

Agora que está tudo funcionando:

1. **Sincronizar mais licitações:**
   ```bash
   curl -X POST "http://localhost:8000/api/v1/licitacoes/sincronizar?data_inicial=20251001&data_final=20251023&limite=1000"
   ```

2. **Explorar a API no Swagger:**
   - http://localhost:8000/docs
   - Teste todos os endpoints

3. **Configurar o Frontend:**
   - Próximo passo: desenvolver interface React

4. **Estudar o código:**
   - Explore `backend/app/` para entender a estrutura
   - Veja os models, services, repositories

---

## 📞 Precisa de Ajuda?

Se encontrar problemas:

1. Verifique os logs do servidor no terminal
2. Consulte a seção "Solução de Problemas" acima
3. Verifique se todos os itens do checklist estão OK
4. Leia os erros com atenção - eles geralmente indicam o problema

---

## 🎉 Parabéns!

Se chegou até aqui e tudo está funcionando, você tem:

✅ Backend FastAPI rodando
✅ Banco de dados MySQL configurado
✅ API REST completa funcionando
✅ Integração com PNCP ativa
✅ Ambiente de desenvolvimento pronto

**Você está pronto para desenvolver!** 🚀

---

**Documento criado:** Outubro 2025
**Versão:** 1.0
**Para:** Setup localhost Windows/XAMPP
