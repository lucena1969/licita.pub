# Licita.pub

Plataforma de licitações públicas do Brasil - Agregador e sistema de alertas integrado com PNCP.

## 🚀 Stack Tecnológica

- **Backend:** Python (FastAPI) + MySQL + Redis
- **Frontend:** React + TypeScript + Tailwind CSS
- **Arquitetura:** Service Layer
- **Deploy:** Hostinger VPS

## 📋 Pré-requisitos

- Python 3.11+
- MySQL 8.0+
- Node.js 18+ (para frontend)
- Git

## ⚙️ Instalação - Backend

### 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/licita.pub.git
cd licita.pub
```

### 2. Execute o script de setup

```bash
chmod +x setup_project.sh
./setup_project.sh
```

### 3. Configure o ambiente Python

```bash
cd backend
python3 -m venv venv

# Linux/Mac
source venv/bin/activate

# Windows
venv\Scripts\activate
```

### 4. Instale as dependências

```bash
pip install -r requirements.txt
```

### 5. Configure as variáveis de ambiente

```bash
cp .env.example .env
nano .env  # ou use seu editor preferido
```

**Variáveis obrigatórias no .env:**

```env
# Database
DATABASE_URL=mysql+pymysql://root:senha@localhost:3306/licitapub

# Segurança - IMPORTANTE: Gere uma chave forte!
SECRET_KEY=sua_chave_secreta_aqui

# Email SMTP (para testes use Gmail com senha de app)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu_email@gmail.com
SMTP_PASSWORD=sua_senha_de_app
SMTP_FROM=noreply@licita.pub
```

**Como gerar SECRET_KEY:**

```bash
python -c "import secrets; print(secrets.token_hex(32))"
```

### 6. Crie o banco de dados MySQL

```bash
mysql -u root -p
```

```sql
CREATE DATABASE licitapub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 7. Inicialize as tabelas

```bash
python init_db.py
```

Você verá:

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

### 8. Execute o servidor

```bash
# Desenvolvimento (com hot reload)
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000

# Ou simplesmente
python -m app.main
```

Acesse:
- **API:** http://localhost:8000
- **Documentação Swagger:** http://localhost:8000/docs
- **ReDoc:** http://localhost:8000/redoc

## 🧪 Testando a API

### 1. Registrar novo usuário

```bash
curl -X POST "http://localhost:8000/api/v1/auth/registrar" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@exemplo.com",
    "senha": "Senha123",
    "confirmar_senha": "Senha123",
    "nome": "João Silva",
    "telefone": "(11) 98888-8888"
  }'
```

### 2. Fazer login

```bash
curl -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@exemplo.com",
    "senha": "Senha123"
  }'
```

Resposta:
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "bearer"
}
```

### 3. Obter dados do usuário autenticado

```bash
curl -X GET "http://localhost:8000/api/v1/auth/me" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

## 📁 Estrutura do Projeto

```
backend/
├── app/
│   ├── api/
│   │   ├── v1/
│   │   │   └── auth.py          # Endpoints de autenticação
│   │   └── deps.py              # Dependencies (auth, etc)
│   ├── core/
│   │   ├── database.py          # Configuração SQLAlchemy
│   │   └── security.py          # JWT, bcrypt, tokens
│   ├── models/                  # Models SQLAlchemy
│   │   ├── usuario.py
│   │   ├── licitacao.py
│   │   ├── favorito.py
│   │   └── ...
│   ├── schemas/                 # Schemas Pydantic
│   │   └── usuario.py
│   ├── services/                # Lógica de negócio
│   │   └── usuario_service.py
│   ├── repositories/            # Acesso a dados
│   │   └── usuario_repository.py
│   ├── utils/                   # Utilitários
│   │   └── email.py
│   ├── config.py                # Configurações
│   └── main.py                  # Entry point
├── init_db.py                   # Script para criar tabelas
├── requirements.txt
└── .env.example
```

## 🔐 Segurança Implementada

- ✅ Senhas com hash bcrypt (custo 12)
- ✅ Tokens JWT com expiração configurável
- ✅ Validação de senha forte (mínimo 8 chars, maiúscula, minúscula, número)
- ✅ Verificação de email obrigatória
- ✅ Reset de senha seguro com tokens temporários
- ✅ Proteção contra email enumeration
- ✅ CORS configurado

## 📧 Configuração de Email

### Gmail (Desenvolvimento)

1. Ative a verificação em 2 etapas
2. Gere uma senha de app: https://myaccount.google.com/apppasswords
3. Use no .env:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu_email@gmail.com
SMTP_PASSWORD=senha_de_app_gerada
```

### Hostinger (Produção)

```env
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_USER=contato@licita.pub
SMTP_PASSWORD=sua_senha_email
```

## 🐛 Troubleshooting

### Erro: "Can't connect to MySQL server"

Verifique se o MySQL está rodando:
```bash
sudo systemctl status mysql  # Linux
# ou
mysql.server status  # Mac
```

### Erro: "Access denied for user"

Verifique credenciais no .env e crie o usuário:
```sql
CREATE USER 'licitapub_user'@'localhost' IDENTIFIED BY 'senha_forte';
GRANT ALL PRIVILEGES ON licitapub.* TO 'licitapub_user'@'localhost';
FLUSH PRIVILEGES;
```

### Erro ao enviar email

Verifique as credenciais SMTP no .env. Para Gmail, certifique-se de usar senha de app.

## 📝 Próximos Passos

- [ ] Implementar integração com API do PNCP
- [ ] Criar endpoints de licitações
- [ ] Implementar sistema de alertas
- [ ] Desenvolver frontend React
- [ ] Configurar Redis para cache
- [ ] Implementar testes automatizados

## 📄 Licença

Este projeto está sob licença MIT.

## 👥 Contato

Para dúvidas ou sugestões, entre em contato.
