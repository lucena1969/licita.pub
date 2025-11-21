# Frontend - Sistema de Autenticação Licita.pub

Documentação dos arquivos criados para o frontend de autenticação.

## 📁 Estrutura de Arquivos Criados

```
frontend/
├── js/
│   ├── api.js           - Cliente da API REST
│   ├── auth.js          - Gerenciamento de autenticação
│   ├── validator.js     - Validações client-side
│   └── masks.js         - Máscaras para inputs
├── css/
│   └── auth.css         - Estilos das páginas de auth
├── cadastro.html        - Página de registro
└── login.html           - Página de login
```

---

## 📄 Descrição dos Arquivos

### 1. **api.js** - Cliente da API

**Responsabilidades:**
- Fazer requisições HTTP para a API
- Gerenciar session_id no localStorage
- Configurar headers e credenciais
- Tratar erros de rede

**Métodos principais:**
```javascript
api.register(userData)          // Cadastrar usuário
api.login(email, senha)         // Fazer login
api.me()                        // Obter dados do usuário
api.logout()                    // Fazer logout
api.listarLicitacoes(filtros)   // Listar licitações (futuro)
```

**Detecção automática de ambiente:**
- Produção: `https://licita.pub/api`
- Desenvolvimento: `http://localhost/api`

---

### 2. **auth.js** - Serviço de Autenticação

**Responsabilidades:**
- Gerenciar estado do usuário
- Verificar autenticação
- Redirecionar usuários
- Atualizar UI com dados do usuário

**Métodos principais:**
```javascript
auth.isAuthenticated()              // Verificar se está logado
auth.getUsuario()                   // Obter usuário atual
auth.carregarUsuario()              // Carregar dados do usuário
auth.register(userData)             // Registrar + auto-login
auth.login(email, senha)            // Login
auth.logout()                       // Logout + redirect
auth.requireAuth()                  // Redirecionar se não autenticado
auth.redirectIfAuthenticated()     // Redirecionar se já autenticado
auth.updateUserUI()                 // Atualizar elementos da UI
```

**Classes CSS especiais:**
- `.auth-only` - Mostrar apenas para autenticados
- `.guest-only` - Mostrar apenas para não autenticados
- `.user-name` - Preenchido com nome do usuário
- `.user-email` - Preenchido com email do usuário
- `.user-plano` - Preenchido com plano do usuário

---

### 3. **validator.js** - Validações Client-Side

**Responsabilidades:**
- Validar dados antes de enviar à API
- Calcular força da senha
- Validar CPF e CNPJ (com dígitos verificadores)
- Fornecer feedback ao usuário

**Métodos principais:**
```javascript
Validator.validateEmail(email)              // Retorna { valid, errors }
Validator.validatePassword(password)        // Retorna { valid, errors, strength }
Validator.validateCPF(cpf)                  // Retorna true/false
Validator.validateCNPJ(cnpj)                // Retorna true/false
Validator.validateCpfCnpj(value)            // Valida CPF ou CNPJ
Validator.validateTelefone(telefone)        // Retorna { valid, errors }
Validator.validateNome(nome)                // Retorna { valid, errors }
Validator.validateRegistro(data)            // Valida formulário completo
Validator.getPasswordStrength(password)     // Retorna 'fraca', 'media', 'forte'
```

**Regras de validação:**
- **Email**: formato válido, único
- **Senha**: mínimo 6 caracteres, letras + números
- **CPF**: 11 dígitos + validação
- **CNPJ**: 14 dígitos + validação
- **Telefone**: 10-11 dígitos (com DDD)
- **Nome**: mínimo 3 caracteres

---

### 4. **masks.js** - Máscaras de Input

**Responsabilidades:**
- Formatar inputs em tempo real
- Aplicar máscaras brasileiras (CPF, CNPJ, telefone, CEP)
- Remover formatação antes de enviar

**Métodos principais:**
```javascript
Masks.cpf(value)                    // 000.000.000-00
Masks.cnpj(value)                   // 00.000.000/0000-00
Masks.cpfCnpj(value)                // Detecta e aplica CPF ou CNPJ
Masks.telefone(value)               // (00) 00000-0000
Masks.cep(value)                    // 00000-000
Masks.money(value)                  // R$ 0.000,00
Masks.removeAll(value)              // Remove toda formatação
Masks.apply(input, maskType)       // Aplica máscara em input
```

**Uso:**
```javascript
// Aplicar máscara em input
const telefoneInput = document.getElementById('telefone');
Masks.apply(telefoneInput, 'telefone');

const cpfCnpjInput = document.getElementById('cpf_cnpj');
Masks.apply(cpfCnpjInput, 'cpf_cnpj');
```

---

### 5. **auth.css** - Estilos das Páginas

**Recursos:**
- Design moderno e responsivo
- Gradiente de fundo animado
- Indicador de força de senha
- Alertas de erro/sucesso
- Loading states nos botões
- Animações suaves

**Variáveis CSS customizáveis:**
```css
--primary-color: #2563eb;
--success-color: #10b981;
--error-color: #ef4444;
--warning-color: #f59e0b;
```

---

### 6. **cadastro.html** - Página de Cadastro

**Campos do formulário:**
- Nome completo (obrigatório)
- Email (obrigatório)
- Senha (obrigatório) - com indicador de força
- Telefone (opcional) - com máscara
- CPF/CNPJ (opcional) - com validação

**Recursos:**
- Validação em tempo real
- Indicador visual de força da senha
- Auto-login após cadastro
- Máscaras automáticas
- Feedback de erros

---

### 7. **login.html** - Página de Login

**Campos do formulário:**
- Email (obrigatório)
- Senha (obrigatório)

**Recursos:**
- Validação client-side
- Autofocus no email
- Link para recuperar senha (placeholder)
- Redirecionamento automático
- Feedback de erros

---

## 🚀 Como Usar

### 1. Enviar arquivos para o servidor

Faça upload de todos os arquivos mantendo a estrutura de diretórios:

```
seu-servidor/
├── backend/
│   └── (arquivos PHP)
└── frontend/
    ├── js/
    ├── css/
    ├── cadastro.html
    └── login.html
```

### 2. Configurar URL da API

O arquivo `api.js` detecta automaticamente o ambiente, mas você pode ajustar manualmente:

```javascript
// Em api.js, método getBaseURL()
if (hostname === 'licita.pub' || hostname === 'www.licita.pub') {
    return 'https://licita.pub/api';
}
```

### 3. Testar localmente

Se estiver testando localmente, configure um servidor:

```bash
# Opção 1: PHP
cd frontend
php -S localhost:8000

# Opção 2: Python
python3 -m http.server 8000

# Opção 3: Node.js (http-server)
npx http-server -p 8000
```

Acesse:
- Cadastro: `http://localhost:8000/cadastro.html`
- Login: `http://localhost:8000/login.html`

---

## 🔒 Segurança

### Client-Side
✅ Validações completas antes de enviar
✅ Sanitização de inputs
✅ Feedback visual de segurança
✅ Session ID armazenado em localStorage (pode ser melhorado para httpOnly cookies)

### API Integration
✅ Credentials: 'include' - envia/recebe cookies
✅ Headers de autorização (Bearer token)
✅ CORS configurado
✅ HTTPS em produção

---

## 📱 Responsividade

Todas as páginas são 100% responsivas:

- ✅ Desktop (1920px+)
- ✅ Laptop (1366px+)
- ✅ Tablet (768px+)
- ✅ Mobile (375px+)

---

## 🎨 Customização

### Alterar cores

Edite as variáveis CSS em `auth.css`:

```css
:root {
    --primary-color: #2563eb;      /* Cor principal */
    --success-color: #10b981;      /* Verde de sucesso */
    --error-color: #ef4444;        /* Vermelho de erro */
    --warning-color: #f59e0b;      /* Amarelo de aviso */
}
```

### Alterar textos

Os textos estão em português e podem ser alterados diretamente nos arquivos HTML.

---

## 🐛 Debug

### Console do navegador

Todas as funções principais registram logs no console:

```javascript
// Verificar autenticação
console.log(auth.isAuthenticated());

// Ver dados do usuário
console.log(auth.getUsuario());

// Ver session_id
console.log(api.getSessionId());
```

### Testar API manualmente

```javascript
// No console do navegador
await api.register({
    nome: 'Teste',
    email: 'teste@example.com',
    senha: 'Teste123'
});

await api.login('teste@example.com', 'Teste123');

await api.me();

await api.logout();
```

---

## ✅ Checklist de Deploy

- [ ] Enviar todos os arquivos JS, CSS e HTML
- [ ] Verificar que o backend está funcionando
- [ ] Testar cadastro de novo usuário
- [ ] Testar login
- [ ] Testar logout
- [ ] Verificar redirecionamentos
- [ ] Testar validações (CPF, email, senha)
- [ ] Testar máscaras (telefone, CPF/CNPJ)
- [ ] Testar responsividade (mobile/desktop)
- [ ] Verificar HTTPS em produção

---

## 🔄 Fluxo Completo

1. **Usuário acessa cadastro.html**
2. Preenche formulário
3. `Validator.validateRegistro()` valida dados
4. `auth.register()` envia para API
5. API retorna sucesso + session_id
6. `auth.login()` faz login automático
7. Session_id salvo em localStorage
8. Redirecionamento para `/consultas.html`

---

## 📞 Próximos Passos

- [ ] Criar página `/consultas.html` (listagem de licitações)
- [ ] Implementar recuperação de senha
- [ ] Adicionar Google OAuth
- [ ] Implementar página de perfil
- [ ] Criar sistema de favoritos
- [ ] Implementar alertas personalizados

---

## 📚 Recursos Utilizados

- **Vanilla JavaScript** (ES6+)
- **CSS3** (Flexbox, Grid, Animations)
- **HTML5** (Semantic, Accessibility)
- **Fetch API** (AJAX)
- **LocalStorage** (Session management)

Nenhuma dependência externa necessária! 🎉
