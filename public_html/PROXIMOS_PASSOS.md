# 🚀 PRÓXIMOS PASSOS - LICITA.PUB

**Data:** 26/10/2025
**Status:** Backend de sincronização funcionando | Frontend freemium em desenvolvimento
**Última atualização:** Após configuração do cron job PNCP

---

## ✅ O QUE JÁ ESTÁ FUNCIONANDO

### Backend - Sincronização PNCP
- ✅ Integração com API do PNCP (`/contratos`)
- ✅ Sincronização automática diária às 6h
- ✅ 100+ licitações no banco de dados
- ✅ Logs funcionando (`/home/u590097272/logs/pncp_sync.log`)
- ✅ Arquivo `.env` configurado
- ✅ Cron job ativo na Hostinger

### Banco de Dados
- ✅ Tabela `licitacoes` (populada)
- ✅ Tabela `orgaos` (populada)
- ✅ Tabela `usuarios` (existente, precisa atualização)
- ✅ Migration 003 criada (pronta para executar)

### Infraestrutura
- ✅ Hostinger configurada
- ✅ Domínio: licita.pub
- ✅ SSL ativo
- ✅ Banco MySQL: `u590097272_licitapub`

---

## 🎯 MODELO DE NEGÓCIO - FREEMIUM

### Planos definidos:

**Anônimo (por IP):**
- 5 consultas detalhadas/dia
- Ver listagem completa
- Busca simples
- Reset: 24h após primeira consulta

**FREE (cadastrado):**
- 10 consultas detalhadas/dia
- Todos recursos do anônimo +
- Filtros básicos (UF, Município)
- Salvar favoritos (em breve)
- Reset: 24h após primeira consulta

**PREMIUM (futuro):**
- Consultas ilimitadas
- Filtros avançados
- Alertas personalizados
- Exportação (Excel, PDF)
- API de integração
- Suporte prioritário

---

## 📋 FASE 1: BACKEND (Estimativa: 6-8h)

### 1.1. Executar Migration do Banco de Dados

**Arquivo:** `backend/database/migrations/003_atualizar_usuarios_limites.sql`

**Ação:**
```bash
# Via phpMyAdmin ou SSH
mysql -u u590097272_neto -p u590097272_licitapub < backend/database/migrations/003_atualizar_usuarios_limites.sql
```

**O que será criado:**
- ✅ Campos novos na tabela `usuarios`: `consultas_hoje`, `primeira_consulta_em`, `limite_diario`
- ✅ Tabela `limites_ip` (controle anônimos)
- ✅ Tabela `historico_consultas` (analytics)
- ✅ Tabela `sessoes` (login)

---

### 1.2. Criar Models

**Arquivos a criar:**

`backend/src/Models/Usuario.php`
- Propriedades: id, email, nome, senha_hash, plano, consultas_hoje, etc
- Métodos: isPremium(), getLimiteRestante(), atingiuLimite()
- Validações de email, senha, etc

`backend/src/Models/LimiteIP.php`
- Propriedades: ip, consultas_hoje, primeira_consulta_em
- Constante: LIMITE_ANONIMO = 5
- Métodos: getLimiteRestante(), atingiuLimite()

---

### 1.3. Criar Repositories

**Arquivos a criar:**

`backend/src/Repositories/UsuarioRepository.php`
- `findByEmail($email)`
- `findById($id)`
- `create($usuario)`
- `update($usuario)`
- `incrementarConsulta($usuarioId)`
- `resetarConsultasDiarias()` (para cron)

`backend/src/Repositories/LimiteIPRepository.php`
- `findByIP($ip)`
- `create($limiteIP)`
- `update($limiteIP)`
- `resetarSePassou24h($ip)`
- `limparRegistrosAntigos()` (para cron)

---

### 1.4. Criar Services

**Arquivos a criar:**

`backend/src/Services/AuthService.php`
- `register($dados)` → Criar conta
- `login($email, $senha)` → Autenticar
- `logout($sessaoId)` → Destruir sessão
- `verificarSessao($token)` → Validar sessão
- `gerarToken($usuario)` → Gerar JWT/Session

`backend/src/Services/LimiteService.php` **(核心)**
- `verificarLimite($usuario, $ip)` → Retorna: `{permitido, restantes, tipo, mensagem}`
- `registrarConsulta($usuario, $ip, $licitacaoId)` → Incrementa contador
- `calcularTempoRestante($timestamp)` → Para exibir "Renova em Xh"
- `passou24h($timestamp)` → Verificar reset

`backend/src/Services/GoogleOAuthService.php` (Fase 2)
- `getAuthUrl()` → URL de redirecionamento Google
- `handleCallback($code)` → Processar retorno
- `getUserInfo($token)` → Obter dados do Google

---

### 1.5. Criar Middleware

**Arquivos a criar:**

`backend/src/Middleware/AuthMiddleware.php`
- Verificar se usuário está autenticado
- Carregar dados do usuário na request
- Retornar 401 se não autenticado (quando necessário)

`backend/src/Middleware/LimiteConsultaMiddleware.php` **(CRÍTICO)**
- Verificar se usuário/IP pode fazer consulta
- Retornar 429 (Too Many Requests) se excedeu
- Adicionar headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`
- Passar informações de limite para a request

`backend/src/Middleware/CorsMiddleware.php`
- Configurar headers CORS
- Permitir origens específicas
- Métodos: GET, POST, OPTIONS

---

### 1.6. Criar Controllers

**Arquivos a criar:**

`backend/src/Controllers/AuthController.php`
- `register()` → POST /api/auth/register.php
- `login()` → POST /api/auth/login.php
- `logout()` → POST /api/auth/logout.php
- `me()` → GET /api/auth/me.php

`backend/src/Controllers/LicitacaoController.php`
- `listar()` → GET /api/licitacoes/listar.php (sem limite)
- `buscar()` → GET /api/licitacoes/buscar.php (sem limite)
- `detalhes()` → GET /api/licitacoes/detalhes.php **(COM LIMITE)**

---

### 1.7. Criar API Endpoints

**Estrutura:**
```
backend/api/
├── bootstrap.php (autoload, config, CORS)
├── .htaccess (rewrite rules)
├── auth/
│   ├── register.php
│   ├── login.php
│   ├── logout.php
│   ├── me.php
│   ├── google.php (OAuth redirect)
│   └── google/callback.php (OAuth callback)
└── licitacoes/
    ├── listar.php
    ├── buscar.php
    └── detalhes.php (aplica LimiteConsultaMiddleware)
```

**Exemplo de endpoint:**

`backend/api/licitacoes/detalhes.php`
```php
<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Middleware\LimiteConsultaMiddleware;
use App\Controllers\LicitacaoController;

// Aplicar middlewares
$authMiddleware = new AuthMiddleware();
$limiteMiddleware = new LimiteConsultaMiddleware();

// Pipeline: Auth (opcional) → Limite → Controller
$request = new stdClass();
$request = $authMiddleware->handle($request, function($req) {
    return $req;
});

$request = $limiteMiddleware->handle($request, function($req) {
    return $req;
});

// Controller
$controller = new LicitacaoController();
$response = $controller->detalhes($_GET['id'] ?? null);

// Output JSON
header('Content-Type: application/json');
echo json_encode($response);
```

---

### 1.8. Criar Cron de Limpeza

**Arquivo a criar:**

`backend/cron/limpar_limites_expirados.php`

```php
<?php
// Executar todo dia às 3h: 0 3 * * *
// Remove IPs inativos há mais de 7 dias
// Remove sessões expiradas
```

**Adicionar no cPanel:**
```
0 3 * * * /usr/bin/php /home/u590097272/domains/licita.pub/public_html/backend/cron/limpar_limites_expirados.php >> /home/u590097272/logs/limpeza.log 2>&1
```

---

## 🎨 FASE 2: FRONTEND (Estimativa: 6-8h)

### 2.1. Atualizar Landing Page

**Arquivo:** `index.php` (já existe em `/backend/public/`)

**Adicionar:**
- Botão CTA: "Começar Grátis" → `/public/cadastro.html`
- Botão: "Já tenho conta" → `/public/login.html`
- Badge: "10 consultas/dia grátis"

---

### 2.2. Criar Estrutura Frontend

**Criar pasta:**
```
public/
├── index.html (ou mover do backend/public/)
├── cadastro.html
├── login.html
├── consultas.html (página principal após login)
├── detalhes.html
├── css/
│   ├── reset.css
│   ├── variables.css
│   └── style.css
└── js/
    ├── config.js
    ├── api.js
    ├── auth.js
    ├── limites.js
    └── app.js
```

---

### 2.3. Design System

**Usar Lucide Icons:**
```html
<!-- CDN -->
<script src="https://unpkg.com/lucide@latest"></script>

<!-- Uso -->
<i data-lucide="search"></i>
<i data-lucide="user"></i>
<i data-lucide="log-in"></i>
```

**Paleta de Cores:**
```css
:root {
    --primary: #2563eb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-900: #111827;
}
```

**Ícones principais:**
- `search` - Busca
- `user` - Usuário
- `log-in` - Login
- `user-plus` - Cadastro
- `building-2` - Órgão
- `calendar` - Datas
- `map-pin` - Localização
- `file-text` - Documento
- `external-link` - Link externo
- `alert-circle` - Aviso
- `check-circle` - Sucesso
- `x-circle` - Erro
- `crown` - Premium

---

### 2.4. Páginas HTML

**cadastro.html:**
- Form: nome, email, senha
- Validação client-side
- Botão "Continuar com Google" (Fase 3)
- Link para login
- Mensagem: "✅ 10 consultas/dia grátis"

**login.html:**
- Form: email, senha
- "Esqueci minha senha" (futuro)
- Botão "Continuar com Google" (Fase 3)
- Link para cadastro

**consultas.html** (Estilo Google):
- Barra de busca centralizada
- Indicador: "Consultas: 7/10 restantes"
- Menu usuário (dropdown)
- Resultados em cards
- Paginação
- Responsivo

**detalhes.html:**
- Se dentro do limite: mostrar detalhes completos
- Se atingiu limite: modal de bloqueio com CTA
- Contador: "Restam X consultas"
- CTAs progressivos baseado no uso

---

### 2.5. JavaScript

**config.js:**
```javascript
const API_BASE_URL = 'https://licita.pub/backend/api';
const TOKEN_KEY = 'licita_token';
```

**api.js:**
```javascript
class API {
    async get(endpoint) { ... }
    async post(endpoint, data) { ... }
    getHeaders() { ... }
}
```

**auth.js:**
```javascript
class Auth {
    isLoggedIn() { ... }
    getUser() { ... }
    login(email, senha) { ... }
    logout() { ... }
    saveToken(token) { ... }
}
```

**limites.js:**
```javascript
class Limites {
    async verificar() { ... }
    exibirContador(restantes, limite) { ... }
    exibirBloqueio(mensagem) { ... }
}
```

**app.js:**
```javascript
// Lógica principal da aplicação
// Busca, filtros, paginação, etc
```

---

## 🔐 FASE 3: GOOGLE OAUTH (Estimativa: 3-4h)

### 3.1. Configurar Google Cloud Console

**Passos:**
1. Acessar: https://console.cloud.google.com/
2. Criar novo projeto: "Licita.pub"
3. Ativar "Google+ API"
4. Criar credenciais OAuth 2.0
5. Configurar URLs autorizadas:
   - `https://licita.pub`
   - `https://licita.pub/backend/api/auth/google/callback.php`

**Obter:**
- Client ID
- Client Secret

**Salvar em `.env`:**
```env
GOOGLE_CLIENT_ID=xxx
GOOGLE_CLIENT_SECRET=xxx
GOOGLE_REDIRECT_URI=https://licita.pub/backend/api/auth/google/callback.php
```

---

### 3.2. Implementar Backend OAuth

**Criar:**

`backend/src/Services/GoogleOAuthService.php`
- Gerar URL de autorização
- Trocar code por token
- Obter informações do usuário
- Criar/logar usuário automaticamente

`backend/api/auth/google.php`
- Redireciona para Google OAuth

`backend/api/auth/google/callback.php`
- Recebe code do Google
- Cria/autentica usuário
- Redireciona para `/consultas.html`

---

### 3.3. Adicionar Botão no Frontend

**Em `cadastro.html` e `login.html`:**
```html
<button onclick="loginComGoogle()" class="btn-google">
    <svg><!-- Logo Google --></svg>
    Continuar com Google
</button>

<script>
function loginComGoogle() {
    window.location.href = '/backend/api/auth/google.php';
}
</script>
```

---

## 📚 FASE 4: DOCUMENTAÇÃO (Estimativa: 2-3h)

### Criar arquivos em `/docs/`:

1. **README.md** - Visão geral do projeto
2. **ARQUITETURA.md** - Diagrama e estrutura técnica
3. **API.md** - Endpoints documentados (estilo OpenAPI)
4. **BANCO_DE_DADOS.md** - Schema, relacionamentos, queries
5. **AUTENTICACAO.md** - Como funciona auth + sessões
6. **CONTROLE_LIMITES.md** - Lógica de rate limiting
7. **FRONTEND.md** - Componentes, design system
8. **DEPLOY.md** - Como fazer deploy
9. **DESENVOLVIMENTO.md** - Setup local
10. **GOOGLE_OAUTH.md** - Configuração OAuth

---

## 🧪 FASE 5: TESTES (Estimativa: 2-3h)

### Checklist de Testes:

**Backend:**
- [ ] Migration executa sem erros
- [ ] Cadastro de usuário funciona
- [ ] Login funciona
- [ ] Logout funciona
- [ ] API retorna licitações
- [ ] Controle de limite (anônimo) funciona
- [ ] Controle de limite (FREE) funciona
- [ ] Reset de 24h funciona
- [ ] Headers CORS corretos
- [ ] Erros retornam JSON apropriado

**Frontend:**
- [ ] Cadastro valida campos
- [ ] Login autentica corretamente
- [ ] Logout limpa sessão
- [ ] Busca funciona
- [ ] Filtros aplicam corretamente
- [ ] Paginação funciona
- [ ] Contador de limites aparece
- [ ] Modal de bloqueio aparece ao atingir limite
- [ ] Responsivo funciona (mobile, tablet, desktop)
- [ ] Lucide Icons renderizam

**Integração:**
- [ ] Fluxo completo: Cadastro → Login → Busca → Detalhe → Limite
- [ ] Google OAuth funciona
- [ ] Cron de sincronização continua funcionando
- [ ] Cron de limpeza funciona

---

## 🚀 FASE 6: DEPLOY FINAL (Estimativa: 1-2h)

### Checklist de Deploy:

1. **Banco de Dados:**
   - [ ] Executar migration 003
   - [ ] Verificar tabelas criadas
   - [ ] Backup do banco

2. **Backend:**
   - [ ] Upload de todos arquivos PHP
   - [ ] Verificar `.env` correto
   - [ ] Testar endpoints via Postman/cURL
   - [ ] Verificar logs de erro

3. **Frontend:**
   - [ ] Upload de HTML/CSS/JS
   - [ ] Verificar CDNs carregam (Lucide Icons)
   - [ ] Testar em navegadores diferentes

4. **Crons:**
   - [ ] Confirmar cron PNCP ativo
   - [ ] Adicionar cron de limpeza
   - [ ] Verificar logs

5. **SSL/HTTPS:**
   - [ ] Confirmar SSL ativo
   - [ ] Redirect HTTP → HTTPS
   - [ ] Mixed content resolvido

6. **Performance:**
   - [ ] Comprimir CSS/JS (minify)
   - [ ] Otimizar imagens
   - [ ] Cache configurado

---

## ⏱️ ESTIMATIVA TOTAL DE TEMPO

| Fase | Descrição | Tempo |
|------|-----------|-------|
| 1 | Backend (Models, Services, API) | 6-8h |
| 2 | Frontend (HTML, CSS, JS) | 6-8h |
| 3 | Google OAuth | 3-4h |
| 4 | Documentação | 2-3h |
| 5 | Testes | 2-3h |
| 6 | Deploy | 1-2h |
| **TOTAL** | | **20-28h** |

**Divisão sugerida:**
- 2-3 sessões de 8-10 horas cada
- Ou 4-5 sessões de 5-6 horas cada

---

## 📝 NOTAS IMPORTANTES

### Ordem de Implementação Sugerida:

1. **Backend primeiro** (sem ele, frontend não funciona)
2. **Frontend básico** (cadastro, login, listagem)
3. **Controle de limites** (núcleo do freemium)
4. **Google OAuth** (nice to have, não bloqueante)
5. **Documentação** (ao longo do processo)
6. **Testes** (validar tudo funciona)
7. **Deploy** (publicar)

### Prioridades:

**CRÍTICO (fazer primeiro):**
- ✅ Migration do banco
- ✅ Models e Repositories
- ✅ LimiteService (核心 do freemium)
- ✅ API de autenticação
- ✅ API de licitações
- ✅ Frontend básico

**IMPORTANTE (fazer depois):**
- Google OAuth
- CTAs otimizados
- Documentação completa

**NICE TO HAVE (futuro):**
- Alertas por email
- Exportação de dados
- Dashboard de analytics
- Plano PREMIUM (pagamento)

---

## 🔗 LINKS ÚTEIS

- **Repositório:** (adicionar URL do Git)
- **Servidor:** https://licita.pub
- **cPanel:** https://hpanel.hostinger.com
- **PNCP API:** https://pncp.gov.br/api/consulta/swagger-ui/index.html
- **Lucide Icons:** https://lucide.dev/icons/
- **Tailwind CSS:** https://tailwindcss.com/docs

---

## 📞 CONTATOS E SUPORTE

- **Email:** contato@licita.pub
- **Banco de Dados:** u590097272_licitapub
- **Usuário DB:** u590097272_neto
- **Servidor:** Hostinger

---

## ✅ CHECKLIST RÁPIDO (Próxima Sessão)

Ao começar a próxima sessão:

1. [ ] Executar migration 003 no banco
2. [ ] Criar Model Usuario.php
3. [ ] Criar Model LimiteIP.php
4. [ ] Criar UsuarioRepository.php
5. [ ] Criar LimiteIPRepository.php
6. [ ] Criar LimiteService.php (核心)
7. [ ] Criar AuthService.php
8. [ ] Testar serviços básicos
9. [ ] Criar API endpoints de autenticação
10. [ ] Testar endpoints com Postman

**Depois que backend básico funcionar:**
11. [ ] Criar página cadastro.html
12. [ ] Criar página login.html
13. [ ] Integrar frontend com backend
14. [ ] Testar fluxo completo

---

**Última atualização:** 26/10/2025
**Próxima revisão:** Após conclusão da Fase 1

---

🤖 **Este documento foi gerado para facilitar a continuidade do desenvolvimento do projeto Licita.pub**
