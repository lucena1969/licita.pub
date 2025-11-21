# Guia de Instalação - XAMPP + Licita.pub

## 📋 Pré-requisitos

- XAMPP instalado (baixe em: https://www.apachefriends.org/)
- Python 3.11+ instalado
- Git instalado (opcional)

---

## 🔧 Passo 1: Configurar MySQL no XAMPP

### 1.1 Iniciar MySQL

1. Abra o **XAMPP Control Panel**
2. Clique em **Start** ao lado de **MySQL**
3. Aguarde até aparecer a porta (geralmente 3306)

### 1.2 Acessar phpMyAdmin

1. No XAMPP Control Panel, clique em **Admin** ao lado de MySQL
2. OU acesse: http://localhost/phpmyadmin
3. Você verá a interface do phpMyAdmin

---

## 📊 Passo 2: Criar o Banco de Dados

### Opção A: Via phpMyAdmin (Recomendado para iniciantes)

1. **Abra o phpMyAdmin** (http://localhost/phpmyadmin)

2. **Clique em "SQL"** no menu superior

3. **Execute os scripts na ordem:**

#### Script 1: Criar Banco
```sql
-- Copie e cole TODO o conteúdo do arquivo:
backend/sql/01_criar_banco.sql
```
- Cole o conteúdo no campo SQL
- Clique em **Executar** (botão inferior direito)
- Você verá: "Banco de dados LICITAPUB criado com sucesso!"

#### Script 2: Criar Tabelas
```sql
-- Copie e cole TODO o conteúdo do arquivo:
backend/sql/02_criar_tabelas.sql
```
- Cole o conteúdo no campo SQL
- Clique em **Executar**
- Você verá: "✓ Tabelas criadas com sucesso! Total_Tabelas: 7"

#### Script 3: Dados Iniciais (OPCIONAL - apenas para testes)
```sql
-- Copie e cole TODO o conteúdo do arquivo:
backend/sql/03_dados_iniciais.sql
```
- Cole o conteúdo no campo SQL
- Clique em **Executar**
- Cria um usuário de teste: teste@licita.pub (senha: Teste123)

### Opção B: Via Linha de Comando MySQL

Se preferir usar o terminal:

```bash
# Navegue até a pasta do projeto
cd C:\xampp\htdocs\licita.pub\backend\sql

# Execute os scripts
mysql -u root -p < 01_criar_banco.sql
mysql -u root -p licitapub < 02_criar_tabelas.sql
mysql -u root -p licitapub < 03_dados_iniciais.sql  # Opcional
```

---

## ✅ Passo 3: Verificar Instalação

### No phpMyAdmin:

1. Na **sidebar esquerda**, clique em **licitapub**
2. Você deve ver **7 tabelas**:
   - ✅ usuarios
   - ✅ licitacoes
   - ✅ itens_licitacao
   - ✅ favoritos
   - ✅ alertas
   - ✅ historico_buscas
   - ✅ logs_sincronizacao

3. Clique em cada tabela para ver sua estrutura

### Verificação via SQL:

Execute este comando no SQL do phpMyAdmin:

```sql
USE licitapub;

-- Ver todas as tabelas
SHOW TABLES;

-- Ver estrutura de uma tabela
DESCRIBE usuarios;

-- Contar registros (se executou o script de dados iniciais)
SELECT COUNT(*) FROM usuarios;
```

---

## 🐍 Passo 4: Configurar Python + Backend

### 4.1 Instalar Python

1. Baixe Python 3.11+: https://www.python.org/downloads/
2. **IMPORTANTE:** Marque a opção **"Add Python to PATH"** durante instalação
3. Verifique a instalação:
```bash
python --version
# Deve exibir: Python 3.11.x ou superior
```

### 4.2 Criar Ambiente Virtual

Abra o **Prompt de Comando** (cmd) ou **PowerShell**:

```bash
# Navegue até a pasta do backend
cd C:\xampp\htdocs\licita.pub\backend

# Criar ambiente virtual
python -m venv venv

# Ativar ambiente virtual
# No Windows (cmd):
venv\Scripts\activate

# No Windows (PowerShell):
venv\Scripts\Activate.ps1

# No Linux/Mac:
source venv/bin/activate

# Você verá (venv) no início da linha do prompt
```

### 4.3 Instalar Dependências

Com o ambiente virtual ativo:

```bash
pip install -r requirements.txt
```

Aguarde a instalação (pode demorar alguns minutos).

### 4.4 Configurar Variáveis de Ambiente

1. **Copie o arquivo de exemplo:**
```bash
copy .env.example .env
```

2. **Edite o arquivo `.env`** com um editor de texto:

```env
# Database (XAMPP padrão)
DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub

# Se o MySQL do XAMPP tiver senha, use:
# DATABASE_URL=mysql+pymysql://root:SUA_SENHA@localhost:3306/licitapub

# Segurança - GERE UMA CHAVE FORTE!
SECRET_KEY=sua_chave_secreta_aqui_mude_isso

# Email (configure depois)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu_email@gmail.com
SMTP_PASSWORD=senha_de_app
SMTP_FROM=noreply@licita.pub

# URLs
FRONTEND_URL=http://localhost:5173
BACKEND_URL=http://localhost:8000
```

3. **Gerar SECRET_KEY:**
```bash
python -c "import secrets; print(secrets.token_hex(32))"
```
Copie o resultado e cole no `.env` no campo `SECRET_KEY=`

---

## 🚀 Passo 5: Iniciar o Backend

### 5.1 Testar Conexão com Banco

```bash
# Certifique-se de estar na pasta backend com venv ativo
cd backend
venv\Scripts\activate  # Se ainda não ativou

# Testar criação das tabelas (não é necessário se já executou os SQLs)
python init_db.py
```

Deve exibir:
```
✓ Tabelas criadas com sucesso!
```

### 5.2 Testar Integração PNCP

```bash
python test_pncp.py
```

Deve exibir:
```
✓ Integração com PNCP funcionando corretamente!
✓ Encontrados 10 contratos
```

### 5.3 Iniciar Servidor API

```bash
uvicorn app.main:app --reload
```

Você verá:
```
INFO:     Uvicorn running on http://127.0.0.1:8000
INFO:     Application startup complete.
```

### 5.4 Testar API

Abra no navegador:

- **Documentação Swagger:** http://localhost:8000/docs
- **API Health Check:** http://localhost:8000/health
- **ReDoc:** http://localhost:8000/redoc

---

## 🧪 Passo 6: Testar Endpoints

### No Swagger (http://localhost:8000/docs):

1. **Teste de Saúde:**
   - Clique em `GET /health`
   - Clique em "Try it out"
   - Clique em "Execute"
   - Deve retornar: `{"status": "healthy"}`

2. **Registrar Usuário:**
   - Clique em `POST /api/v1/auth/registrar`
   - Clique em "Try it out"
   - Preencha os dados:
   ```json
   {
     "email": "meuemail@teste.com",
     "senha": "Senha123",
     "confirmar_senha": "Senha123",
     "nome": "Meu Nome",
     "telefone": "(11) 98888-8888"
   }
   ```
   - Clique em "Execute"
   - Deve retornar 201 Created

3. **Fazer Login:**
   - Clique em `POST /api/v1/auth/login`
   - Clique em "Try it out"
   - Preencha:
   ```json
   {
     "email": "meuemail@teste.com",
     "senha": "Senha123"
   }
   ```
   - Copie o `access_token` retornado

4. **Sincronizar Licitações do PNCP:**
   - Clique em `POST /api/v1/licitacoes/sincronizar`
   - Clique em "Try it out"
   - Parâmetros:
     - data_inicial: `20250101`
     - data_final: `20250117`
     - max_paginas: `2`
   - Clique em "Execute"
   - Aguarde (pode demorar 30-60 segundos)
   - Deve retornar quantos registros foram sincronizados

5. **Listar Licitações:**
   - Clique em `GET /api/v1/licitacoes`
   - Clique em "Try it out"
   - Clique em "Execute"
   - Deve retornar a lista de licitações sincronizadas

---

## 📁 Estrutura de Pastas no XAMPP

Sugestão de organização:

```
C:\xampp\
├── htdocs\
│   └── licita.pub\          ← Clone o projeto aqui
│       ├── backend\
│       │   ├── app\
│       │   ├── sql\         ← Scripts SQL
│       │   ├── venv\        ← Ambiente virtual Python
│       │   ├── .env         ← Configurações (CRIAR!)
│       │   └── ...
│       └── frontend\
│           └── ...
├── mysql\
│   └── data\
│       └── licitapub\       ← Banco criado aqui
└── phpMyAdmin\
```

---

## 🐛 Troubleshooting

### Erro: "Can't connect to MySQL server"

**Solução:**
1. Verifique se o MySQL está rodando no XAMPP Control Panel
2. Verifique a porta (padrão: 3306)
3. Verifique o `.env`:
   ```env
   DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub
   ```

### Erro: "Access denied for user 'root'"

**Solução:**
Se o MySQL do XAMPP tem senha:
```env
DATABASE_URL=mysql+pymysql://root:SUA_SENHA@localhost:3306/licitapub
```

### Erro: "No module named 'fastapi'"

**Solução:**
1. Ative o ambiente virtual:
   ```bash
   venv\Scripts\activate
   ```
2. Instale as dependências:
   ```bash
   pip install -r requirements.txt
   ```

### Erro: "Table doesn't exist"

**Solução:**
Execute novamente os scripts SQL na ordem correta:
1. `01_criar_banco.sql`
2. `02_criar_tabelas.sql`

### Porta 8000 já em uso

**Solução:**
Use outra porta:
```bash
uvicorn app.main:app --reload --port 8001
```

---

## 📚 Próximos Passos

Após a instalação bem-sucedida:

1. ✅ Banco de dados configurado
2. ✅ Backend rodando
3. ✅ API testada no Swagger
4. ✅ Licitações sincronizadas do PNCP

**Agora você pode:**
- Desenvolver o frontend
- Configurar emails (SMTP)
- Implementar novos recursos
- Fazer deploy em produção (Hostinger)

---

## 💡 Dicas

- **Sempre ative o ambiente virtual** antes de trabalhar:
  ```bash
  cd backend
  venv\Scripts\activate
  ```

- **Para parar o servidor:** Pressione `Ctrl+C` no terminal

- **Ver logs do MySQL:** XAMPP Control Panel → Logs (ao lado de MySQL)

- **Backup do banco:**
  - phpMyAdmin → Selecione "licitapub" → Exportar

- **Resetar banco (CUIDADO!):**
  ```sql
  DROP DATABASE licitapub;
  -- Depois execute os scripts novamente
  ```

---

## 🆘 Suporte

Se encontrar problemas:
1. Verifique os logs no terminal
2. Consulte a documentação: http://localhost:8000/docs
3. Verifique o arquivo README.md
4. Revise este guia passo a passo

**Bom desenvolvimento! 🚀**
