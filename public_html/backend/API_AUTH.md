# API de Autenticação - Licita.pub

Documentação dos endpoints de autenticação do sistema Licita.pub.

## Base URL

```
https://licita.pub/api/auth
```

## Endpoints

### 1. Registrar Usuário

Cria uma nova conta de usuário no sistema.

**Endpoint:** `POST /api/auth/register.php`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "email": "usuario@exemplo.com",
  "senha": "senha123",
  "nome": "João Silva",
  "telefone": "(11) 98765-4321",  // Opcional
  "cpf_cnpj": "123.456.789-00"     // Opcional
}
```

**Resposta de Sucesso (201 Created):**
```json
{
  "success": true,
  "usuario": {
    "id": "uuid-do-usuario",
    "email": "usuario@exemplo.com",
    "nome": "João Silva",
    "telefone": "11987654321",
    "cpf_cnpj": "12345678900",
    "email_verificado": false,
    "ativo": true,
    "plano": "FREE",
    "created_at": "2025-10-27 10:30:00",
    "updated_at": "2025-10-27 10:30:00"
  },
  "message": "Usuário cadastrado com sucesso"
}
```

**Resposta de Erro (400 Bad Request):**
```json
{
  "success": false,
  "errors": [
    "Email já cadastrado"
  ]
}
```

---

### 2. Login

Autentica um usuário no sistema.

**Endpoint:** `POST /api/auth/login.php`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "email": "usuario@exemplo.com",
  "senha": "senha123"
}
```

**Resposta de Sucesso (200 OK):**
```json
{
  "success": true,
  "session_id": "token-de-sessao-64-caracteres",
  "usuario": {
    "id": "uuid-do-usuario",
    "email": "usuario@exemplo.com",
    "nome": "João Silva",
    "telefone": "11987654321",
    "cpf_cnpj": "12345678900",
    "email_verificado": false,
    "ativo": true,
    "plano": "FREE",
    "created_at": "2025-10-27 10:30:00",
    "updated_at": "2025-10-27 10:30:00"
  },
  "expires_in": 2592000,
  "message": "Login realizado com sucesso"
}
```

**Cookie Definido:**
- Nome: `session_id`
- Valor: token de sessão
- Expires: 30 dias
- HttpOnly: true
- Secure: true (apenas em HTTPS)
- SameSite: Lax

**Resposta de Erro (401 Unauthorized):**
```json
{
  "success": false,
  "errors": [
    "Email ou senha inválidos"
  ]
}
```

---

### 3. Obter Usuário Atual

Retorna os dados do usuário autenticado.

**Endpoint:** `GET /api/auth/me.php`

**Headers:**
```
Authorization: Bearer {session_id}
```

**Ou usar cookie `session_id`**

**Resposta de Sucesso (200 OK):**
```json
{
  "success": true,
  "usuario": {
    "id": "uuid-do-usuario",
    "email": "usuario@exemplo.com",
    "nome": "João Silva",
    "telefone": "11987654321",
    "cpf_cnpj": "12345678900",
    "email_verificado": false,
    "ativo": true,
    "plano": "FREE",
    "created_at": "2025-10-27 10:30:00",
    "updated_at": "2025-10-27 10:30:00"
  }
}
```

**Resposta de Erro (401 Unauthorized):**
```json
{
  "success": false,
  "errors": [
    "Não autenticado. Faça login para continuar."
  ]
}
```

---

### 4. Logout

Encerra a sessão do usuário.

**Endpoint:** `POST /api/auth/logout.php`

**Headers:**
```
Authorization: Bearer {session_id}
```

**Ou usar cookie `session_id`**

**Resposta de Sucesso (200 OK):**
```json
{
  "success": true,
  "message": "Logout realizado com sucesso"
}
```

---

## Regras de Validação

### Email
- Obrigatório
- Formato válido de email
- Máximo 255 caracteres
- Único no sistema

### Senha
- Obrigatório
- Mínimo 6 caracteres
- Máximo 100 caracteres
- Deve conter letras e números

### Nome
- Obrigatório
- Mínimo 3 caracteres
- Máximo 255 caracteres

### Telefone (Opcional)
- Deve ter 10 ou 11 dígitos (com DDD)
- Formato: (11) 98765-4321 ou 11987654321

### CPF/CNPJ (Opcional)
- CPF: 11 dígitos
- CNPJ: 14 dígitos
- Validação de dígitos verificadores

---

## Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| 200 | OK - Requisição bem-sucedida |
| 201 | Created - Usuário criado com sucesso |
| 400 | Bad Request - Dados inválidos |
| 401 | Unauthorized - Não autenticado |
| 405 | Method Not Allowed - Método HTTP incorreto |
| 500 | Internal Server Error - Erro no servidor |

---

## Segurança

### Autenticação
- Sistema de sessões com tokens de 64 caracteres
- Sessões expiram após 30 dias de inatividade
- Renovação automática de sessão (sliding expiration)

### Senhas
- Hasheadas com bcrypt (PASSWORD_BCRYPT)
- Nunca retornadas em respostas da API

### Cookies
- HttpOnly: previne acesso via JavaScript
- Secure: apenas transmitido em HTTPS
- SameSite: proteção contra CSRF

### CORS
- Origens permitidas configuradas
- Credenciais permitidas (cookies)

---

## Planos de Usuário

| Plano | Consultas/dia | Recursos |
|-------|---------------|----------|
| FREE | 10 | Filtros básicos, favoritos |
| PREMIUM | Ilimitado | Filtros avançados, alertas, exportação |

---

## Exemplos de Uso

### cURL

**Registrar:**
```bash
curl -X POST https://licita.pub/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@exemplo.com",
    "senha": "senha123",
    "nome": "João Silva"
  }'
```

**Login:**
```bash
curl -X POST https://licita.pub/api/auth/login.php \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{
    "email": "usuario@exemplo.com",
    "senha": "senha123"
  }'
```

**Me:**
```bash
curl -X GET https://licita.pub/api/auth/me.php \
  -b cookies.txt
```

**Logout:**
```bash
curl -X POST https://licita.pub/api/auth/logout.php \
  -b cookies.txt
```

### JavaScript (Fetch API)

**Registrar:**
```javascript
const response = await fetch('https://licita.pub/api/auth/register.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'usuario@exemplo.com',
    senha: 'senha123',
    nome: 'João Silva'
  })
});

const data = await response.json();
console.log(data);
```

**Login:**
```javascript
const response = await fetch('https://licita.pub/api/auth/login.php', {
  method: 'POST',
  credentials: 'include', // Importante para cookies
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'usuario@exemplo.com',
    senha: 'senha123'
  })
});

const data = await response.json();
console.log(data);
```

**Me:**
```javascript
const response = await fetch('https://licita.pub/api/auth/me.php', {
  credentials: 'include' // Envia cookies automaticamente
});

const data = await response.json();
console.log(data);
```

**Logout:**
```javascript
const response = await fetch('https://licita.pub/api/auth/logout.php', {
  method: 'POST',
  credentials: 'include'
});

const data = await response.json();
console.log(data);
```

---

## Próximos Passos

- [ ] Implementar verificação de email
- [ ] Implementar reset de senha por email
- [ ] Adicionar Google OAuth
- [ ] Implementar rate limiting
- [ ] Adicionar logs de auditoria
