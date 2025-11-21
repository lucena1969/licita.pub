# 🏠 Setup Local - Licita.pub no XAMPP

## 📂 Estrutura de Pastas Recomendada

### Opção 1: Dentro do htdocs (Recomendado)
```
C:\xampp\
├── htdocs\
│   └── licita.pub\              ← Seu projeto aqui
│       ├── backend\
│       │   ├── app\
│       │   ├── sql\
│       │   ├── venv\            ← Será criado
│       │   ├── .env             ← Criar manualmente
│       │   ├── requirements.txt
│       │   └── ...
│       ├── frontend\
│       │   └── (será criado depois)
│       ├── README.md
│       └── ...
├── mysql\
│   └── data\
│       └── licitapub\           ← Banco criado
└── phpMyAdmin\
```

### Opção 2: Fora do htdocs
```
C:\Projetos\
└── licita.pub\                  ← Seu projeto aqui
    ├── backend\
    ├── frontend\
    └── ...
```

---

## 🚀 Passo a Passo - Setup Local

### **Passo 1: Baixar o Projeto**

#### Opção A: Via Git (Recomendado)
```bash
# Abra o terminal/cmd e navegue até onde quer o projeto
cd C:\xampp\htdocs

# Clone o repositório
git clone https://github.com/lucena1969/licita.pub.git

# Entre na pasta
cd licita.pub
```

#### Opção B: Download Manual
1. Acesse: https://github.com/lucena1969/licita.pub
2. Clique em **Code** → **Download ZIP**
3. Extraia para `C:\xampp\htdocs\licita.pub`

---

### **Passo 2: Verificar Estrutura de Pastas**

Navegue até a pasta do projeto e verifique:

```bash
cd C:\xampp\htdocs\licita.pub
dir

# Você deve ver:
# - backend\
# - frontend\
# - README.md
# - INSTALACAO_XAMPP.md
# - etc...
```

---

### **Passo 3: Configurar Backend Python**

#### 3.1 Navegar até a pasta backend
```bash
cd backend
dir

# Você deve ver:
# - app\
# - sql\
# - requirements.txt
# - init_db.py
# - test_pncp.py
# - .env.example
```

#### 3.2 Criar Ambiente Virtual
```bash
# Criar venv
python -m venv venv

# Verificar se foi criado
dir
# Deve aparecer: venv\
```

#### 3.3 Ativar Ambiente Virtual

**No Windows (cmd):**
```bash
venv\Scripts\activate
```

**No Windows (PowerShell):**
```powershell
venv\Scripts\Activate.ps1
```

**No Linux/Mac:**
```bash
source venv/bin/activate
```

Você verá `(venv)` no início da linha do prompt.

#### 3.4 Instalar Dependências
```bash
# Com venv ativado
pip install -r requirements.txt

# Aguarde a instalação (pode demorar 2-5 minutos)
```

---

### **Passo 4: Configurar Variáveis de Ambiente (.env)**

#### 4.1 Copiar arquivo de exemplo
```bash
# Ainda na pasta backend
copy .env.example .env
```

#### 4.2 Editar o arquivo .env

Abra o arquivo `.env` com um editor de texto (Notepad++, VSCode, ou Bloco de Notas):

```env
# ========================================
# CONFIGURAÇÃO PARA XAMPP LOCAL
# ========================================

# AMBIENTE
ENVIRONMENT=development

# DATABASE - XAMPP MySQL
# Se o MySQL não tem senha (padrão XAMPP):
DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub

# Se o MySQL tem senha:
# DATABASE_URL=mysql+pymysql://root:SUA_SENHA@localhost:3306/licitapub

# SEGURANÇA - GERE UMA CHAVE FORTE!
SECRET_KEY=sua_chave_secreta_aqui_precisa_trocar
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=10080

# EMAIL VERIFICATION TOKEN (7 dias)
EMAIL_VERIFICATION_EXPIRE_HOURS=168

# EMAIL SMTP (configure depois quando for usar)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu_email@gmail.com
SMTP_PASSWORD=sua_senha_de_app
SMTP_FROM=noreply@licita.pub
SMTP_FROM_NAME=Licita.pub

# URLs
FRONTEND_URL=http://localhost:5173
BACKEND_URL=http://localhost:8000

# PNCP API (não precisa de credenciais)
PNCP_API_URL=https://pncp.gov.br/api/consulta/v1
PNCP_USERNAME=
PNCP_PASSWORD=

# REDIS (opcional - deixe em branco por enquanto)
REDIS_URL=

# GOOGLE ADSENSE (para produção - deixe em branco)
ADSENSE_CLIENT_ID=

# LOGS
LOG_LEVEL=INFO
```

#### 4.3 Gerar SECRET_KEY

**No terminal/cmd:**
```bash
python -c "import secrets; print(secrets.token_hex(32))"
```

**Exemplo de resultado:**
```
a8f5f167f44f4964e6c998dee827110c47e5b11e2d2a6b3b8a4b5c6d7e8f9a0b
```

Copie esse valor e cole no `.env` na linha `SECRET_KEY=`

---

### **Passo 5: Testar Configuração**

#### 5.1 Testar Conexão com Banco
```bash
# Certifique-se de estar na pasta backend com venv ativo
cd C:\xampp\htdocs\licita.pub\backend
venv\Scripts\activate

# Testar (não precisa se já executou os SQLs)
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

#### 5.2 Testar Integração PNCP
```bash
python test_pncp.py
```

**Resultado esperado:**
```
╔==========================================================╗
║               TESTE DE INTEGRAÇÃO PNCP                   ║
╚==========================================================╝

✓ Períodos gerados corretamente
✓ Encontrados 10 contratos
✓ Integração com PNCP funcionando corretamente!
```

---

### **Passo 6: Iniciar o Servidor API**

```bash
# Na pasta backend, com venv ativo
uvicorn app.main:app --reload
```

**Resultado esperado:**
```
INFO:     Uvicorn running on http://127.0.0.1:8000 (Press CTRL+C to quit)
INFO:     Started reloader process [xxxxx] using StatReload
INFO:     Started server process [xxxxx]
INFO:     Waiting for application startup.
============================================================
Licita.pub API iniciando...
Ambiente: development
Frontend URL: http://localhost:5173
============================================================
INFO:     Application startup complete.
```

**✅ Servidor rodando!**

---

### **Passo 7: Testar a API**

Abra no navegador:

1. **Documentação Swagger:** http://localhost:8000/docs
2. **API Root:** http://localhost:8000
3. **Health Check:** http://localhost:8000/health

---

## 📁 Checklist de Arquivos Importantes

Verifique se esses arquivos/pastas existem:

```
licita.pub\
├── backend\
│   ├── app\                     ✅ Código da aplicação
│   │   ├── api\
│   │   ├── core\
│   │   ├── models\
│   │   ├── schemas\
│   │   ├── services\
│   │   ├── repositories\
│   │   └── main.py
│   ├── sql\                     ✅ Scripts SQL
│   │   ├── 01_criar_banco.sql
│   │   ├── 02_criar_tabelas_simples.sql
│   │   └── 03_dados_iniciais.sql
│   ├── venv\                    ✅ Ambiente virtual (após criar)
│   ├── .env                     ✅ Configurações (após copiar)
│   ├── requirements.txt         ✅ Dependências
│   ├── init_db.py              ✅ Script de inicialização
│   └── test_pncp.py            ✅ Script de testes
├── README.md
├── INSTALACAO_XAMPP.md
└── SETUP_LOCAL.md
```

---

## 🔧 Comandos Úteis

### Ativar ambiente virtual
```bash
# Windows
venv\Scripts\activate

# Linux/Mac
source venv/bin/activate
```

### Desativar ambiente virtual
```bash
deactivate
```

### Instalar nova dependência
```bash
pip install nome-do-pacote
pip freeze > requirements.txt  # Atualizar lista
```

### Iniciar servidor
```bash
uvicorn app.main:app --reload
```

### Iniciar em outra porta
```bash
uvicorn app.main:app --reload --port 8001
```

### Ver logs do servidor
```bash
# Os logs aparecem no terminal onde você iniciou o servidor
```

---

## 🐛 Problemas Comuns e Soluções

### Erro: "python não reconhecido"

**Causa:** Python não está no PATH

**Solução:**
1. Reinstale o Python marcando "Add to PATH"
2. Ou adicione manualmente ao PATH do Windows

---

### Erro: "No module named 'fastapi'"

**Causa:** Ambiente virtual não está ativo ou dependências não instaladas

**Solução:**
```bash
venv\Scripts\activate
pip install -r requirements.txt
```

---

### Erro: "Can't connect to MySQL server"

**Causa:** MySQL não está rodando

**Solução:**
1. Abra XAMPP Control Panel
2. Clique em "Start" ao lado de MySQL
3. Aguarde até aparecer a porta 3306

---

### Erro: "Access denied for user 'root'"

**Causa:** Senha do MySQL no .env está errada

**Solução:**
- XAMPP padrão não tem senha: `DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub`
- Se definiu senha: `DATABASE_URL=mysql+pymysql://root:SENHA@localhost:3306/licitapub`

---

### Erro: "Table doesn't exist"

**Causa:** Banco não foi criado ou scripts SQL não foram executados

**Solução:**
1. Execute os scripts SQL no phpMyAdmin na ordem
2. Ou execute: `python init_db.py`

---

### Porta 8000 já em uso

**Causa:** Outro processo usando a porta

**Solução:**
```bash
uvicorn app.main:app --reload --port 8001
# Acesse: http://localhost:8001
```

---

## 📝 Workflow Diário

Quando for trabalhar no projeto:

```bash
# 1. Iniciar XAMPP
# - Abra XAMPP Control Panel
# - Start: Apache (se usar) e MySQL

# 2. Navegar até o projeto
cd C:\xampp\htdocs\licita.pub\backend

# 3. Ativar ambiente virtual
venv\Scripts\activate

# 4. Iniciar servidor
uvicorn app.main:app --reload

# 5. Acessar documentação
# http://localhost:8000/docs

# 6. Quando terminar
# Ctrl+C para parar o servidor
# deactivate para desativar venv
# Stop MySQL no XAMPP
```

---

## 🎯 Próximos Passos

Após o setup local:

1. ✅ Banco de dados criado e funcionando
2. ✅ Backend Python configurado
3. ✅ Servidor API rodando
4. ✅ Testes passando

**Você pode:**
- Testar endpoints no Swagger (http://localhost:8000/docs)
- Sincronizar licitações do PNCP
- Começar o desenvolvimento do frontend
- Adicionar novos recursos

---

## 💡 Dicas

**Manter tudo organizado:**
- Use um editor de código (VSCode recomendado)
- Mantenha o terminal aberto com venv ativo
- Use Git para versionar suas mudanças

**Performance:**
- Feche programas pesados enquanto desenvolve
- MySQL do XAMPP pode consumir bastante RAM

**Backup:**
- Faça backup do banco via phpMyAdmin (Exportar)
- Use Git para versionamento do código

---

## 📚 Documentação Relacionada

- `README.md` - Visão geral do projeto
- `INSTALACAO_XAMPP.md` - Guia detalhado XAMPP
- `backend/sql/README_PHPMYADMIN.md` - Guia phpMyAdmin
- http://localhost:8000/docs - Documentação API (quando servidor rodando)

---

**Bom desenvolvimento! 🚀**

Se tiver dúvidas, consulte os guias ou verifique os logs no terminal!
