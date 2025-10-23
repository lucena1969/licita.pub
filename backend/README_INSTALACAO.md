# 🚀 Instalação Rápida - Licita.pub Backend

## ⚡ Instalação em 3 Passos (Windows/XAMPP)

### 1️⃣ Pré-requisitos
- [x] XAMPP instalado (MySQL rodando na porta 3306)
- [x] Python 3.11+ instalado e no PATH
- [x] Projeto baixado em `C:\xampp\htdocs\licita.pub`

### 2️⃣ Executar Script de Setup
```bash
# Abra o terminal (cmd) na pasta backend
cd C:\xampp\htdocs\licita.pub\backend

# Execute o script de instalação
setup_windows.bat
```

O script irá:
- ✅ Criar ambiente virtual Python
- ✅ Instalar todas as dependências
- ✅ Gerar SECRET_KEY para você
- ✅ Criar arquivo `.env`

### 3️⃣ Configurar Banco de Dados

**Opção A: Via Script Python (MAIS FÁCIL)**
```bash
# Ative o ambiente virtual
venv\Scripts\activate

# Teste a conexão
python testar_conexao.py

# Crie as tabelas
python init_db.py
```

**Opção B: Via phpMyAdmin**
1. Acesse: http://localhost/phpmyadmin
2. Clique em **SQL**
3. Execute os scripts na ordem:
   - `sql/01_criar_banco.sql`
   - `sql/02_criar_tabelas_simples.sql`

---

## ▶️ Iniciar o Servidor

### Método 1: Script Automático (RECOMENDADO)
```bash
# Duplo clique no arquivo:
INICIAR.bat
```

### Método 2: Manual
```bash
# Ative o ambiente virtual
venv\Scripts\activate

# Inicie o servidor
uvicorn app.main:app --reload
```

**Servidor rodando em:**
- 🌐 API: http://localhost:8000
- 📖 Documentação: http://localhost:8000/docs

---

## 🧪 Testar Instalação

### Teste Automático
```bash
VERIFICAR.bat
```

### Teste Manual
```bash
# 1. Health check
curl http://localhost:8000/health

# 2. Registrar usuário (via Swagger)
# Acesse: http://localhost:8000/docs
# Clique em POST /api/v1/auth/registrar
# Preencha os dados e teste

# 3. Sincronizar licitações do PNCP
curl -X POST "http://localhost:8000/api/v1/licitacoes/sincronizar?data_inicial=20251016&data_final=20251023&limite=100"
```

---

## 📂 Arquivos Importantes

| Arquivo | Descrição |
|---------|-----------|
| `setup_windows.bat` | Script de instalação automática |
| `INICIAR.bat` | Inicia o servidor rapidamente |
| `VERIFICAR.bat` | Verifica se está tudo instalado |
| `testar_conexao.py` | Testa conexão com banco de dados |
| `init_db.py` | Cria as tabelas no banco |
| `test_pncp.py` | Testa integração com PNCP |
| `.env` | Configurações (DATABASE_URL, SECRET_KEY) |

---

## 🔧 Configuração do .env

Edite o arquivo `.env` e configure:

```env
# Banco de dados MySQL (XAMPP sem senha)
DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub

# Se MySQL tem senha
# DATABASE_URL=mysql+pymysql://root:SUA_SENHA@localhost:3306/licitapub

# Secret Key (gerada pelo setup_windows.bat)
SECRET_KEY=cole_a_chave_gerada_aqui

# URLs
FRONTEND_URL=http://localhost:5173
BACKEND_URL=http://localhost:8000

# PNCP API
PNCP_API_URL=https://pncp.gov.br/api/consulta/v1
```

---

## 🐛 Problemas Comuns

### MySQL não conecta
```bash
# Verifique se MySQL está rodando no XAMPP
# Porta deve estar 3306

# Teste a conexão
python testar_conexao.py
```

### Python não encontrado
```bash
# Reinstale Python e marque "Add to PATH"
# Ou adicione manualmente ao PATH do Windows
```

### Módulos não encontrados
```bash
# Ative o venv e reinstale
venv\Scripts\activate
pip install -r requirements.txt
```

### Porta 8000 em uso
```bash
# Use outra porta
uvicorn app.main:app --reload --port 8001
```

---

## 📚 Documentação Completa

Para mais detalhes, consulte:
- [GUIA_LOCALHOST_COMPLETO.md](../GUIA_LOCALHOST_COMPLETO.md) - Guia detalhado
- [SETUP_LOCAL.md](../SETUP_LOCAL.md) - Setup local
- [INSTALACAO_XAMPP.md](../INSTALACAO_XAMPP.md) - Instalação XAMPP

---

## ✅ Checklist Rápido

- [ ] MySQL rodando (XAMPP)
- [ ] `setup_windows.bat` executado
- [ ] `.env` configurado
- [ ] `python init_db.py` executado
- [ ] `INICIAR.bat` funcionando
- [ ] http://localhost:8000/docs abrindo

**Se todos os itens estão marcados, você está pronto! 🎉**

---

## 🎯 Próximos Passos

1. Explore a API em: http://localhost:8000/docs
2. Sincronize mais licitações do PNCP
3. Teste os endpoints de autenticação
4. Configure o frontend React

**Bom desenvolvimento! 🚀**
