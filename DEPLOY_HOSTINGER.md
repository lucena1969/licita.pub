# 🚀 Deploy na Hostinger - Licita.pub

## 📋 Pré-requisitos

- ✅ Conta na Hostinger ativa
- ✅ Domínio configurado (licita.pub)
- ✅ Acesso ao painel Hostinger
- ✅ Cliente FTP (FileZilla recomendado)

---

## 🎯 Passo 1: Fazer Upload da Home Page (AGORA!)

### Arquivos para Enviar

Você precisa enviar apenas 2 arquivos para começar:

```
public_html/
├── index.php       ← Homepage completa
└── .htaccess       ← Configurações Apache
```

### Como Fazer Upload

#### Opção A: Via Gerenciador de Arquivos da Hostinger (MAIS FÁCIL)

1. **Acesse o painel Hostinger:**
   - Login: https://hpanel.hostinger.com/

2. **Vá para Gerenciador de Arquivos:**
   - Painel → Gerenciador de Arquivos
   - Ou acesse direto: Arquivos → Gerenciador de Arquivos

3. **Navegue até public_html:**
   - Clique na pasta `public_html`

4. **Limpe a pasta (se necessário):**
   - Delete o `index.html` padrão da Hostinger
   - Delete qualquer arquivo de exemplo

5. **Faça upload dos arquivos:**
   - Clique em **Upload**
   - Selecione os 2 arquivos:
     - `backend/public/index.php`
     - `backend/public/.htaccess`
   - Aguarde o upload completar

#### Opção B: Via FTP (FileZilla)

1. **Abra o FileZilla**

2. **Configure a conexão:**
   ```
   Host: ftp.licita.pub (ou IP fornecido pela Hostinger)
   Usuário: seu_usuario_ftp
   Senha: sua_senha_ftp
   Porta: 21
   ```

3. **Conecte-se**

4. **Navegue até public_html** (lado direito)

5. **Arraste os arquivos:**
   - `backend/public/index.php`
   - `backend/public/.htaccess`

---

## ✅ Passo 2: Testar

### Acesse seu site:
**https://licita.pub**

Você deve ver:
- ✅ Home page bonita e profissional
- ✅ Design responsivo
- ✅ Todas as seções funcionando
- ✅ Links de navegação

---

## 🎨 O que está na Home Page

### Seções Incluídas:
- ✅ **Header** com navegação
- ✅ **Hero** com chamada principal
- ✅ **Estatísticas** (5.000+ órgãos, 27 estados)
- ✅ **Sobre** a plataforma
- ✅ **Funcionalidades** (6 cards)
- ✅ **Status** (Em desenvolvimento)
- ✅ **Contato** (email)
- ✅ **Footer** completo

### Recursos:
- ✅ Design minimalista e profissional
- ✅ Responsivo (mobile, tablet, desktop)
- ✅ Tailwind CSS via CDN (sem arquivos extras)
- ✅ Animações suaves
- ✅ SEO otimizado (meta tags)
- ✅ Open Graph (Facebook, Twitter)

---

## 🔧 Passo 3: Deploy Completo (Futuro)

Quando o backend estiver pronto, você precisará enviar:

### Estrutura Completa:
```
public_html/
├── index.php                    ✅ Já enviado
├── .htaccess                    ✅ Já enviado
├── .env                         ⏳ Criar com suas credenciais
├── composer.json                ⏳ Dependências PHP
├── api/
│   └── index.php               ⏳ API REST
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── pages/
│   ├── login.php
│   └── dashboard.php
└── src/                        ⏳ Código PHP (via Composer)
```

### Passos Futuros:

#### 1. Criar Banco de Dados MySQL na Hostinger
```
1. Painel Hostinger → Bancos de Dados → MySQL
2. Criar novo banco: licitapub
3. Criar usuário: licitapub_user
4. Anotar: host, database, username, password
```

#### 2. Importar Tabelas SQL
```
1. Painel → phpMyAdmin
2. Selecionar banco licitapub
3. Importar: backend/sql/02_criar_tabelas_simples.sql
```

#### 3. Configurar .env
```env
DB_HOST=localhost (ou IP fornecido)
DB_PORT=3306
DB_DATABASE=licitapub
DB_USERNAME=licitapub_user
DB_PASSWORD=senha_do_banco

JWT_SECRET=gerar_chave_segura
```

#### 4. Instalar Dependências PHP (via SSH)
```bash
# Se tiver acesso SSH
cd public_html
composer install --no-dev --optimize-autoloader
```

**OU via Hostinger:**
- Fazer upload da pasta `vendor/` já compilada localmente

---

## 📱 URLs Importantes

### Produção:
- **Site:** https://licita.pub
- **API:** https://licita.pub/api/ (quando pronto)
- **Docs:** https://licita.pub/api/docs (quando pronto)

### Painel Hostinger:
- **Login:** https://hpanel.hostinger.com/
- **Gerenciador de Arquivos:** Painel → Arquivos
- **phpMyAdmin:** Painel → Bancos de Dados → phpMyAdmin
- **Email:** Painel → Emails

---

## 🔐 Configurações de Segurança

### SSL/HTTPS
A Hostinger oferece SSL grátis. Ative em:
```
Painel → SSL → Gerenciar SSL → Let's Encrypt (Grátis)
```

Depois ative o redirect HTTPS no `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Firewall
Já configurado no `.htaccess`:
- Bloqueia arquivos sensíveis (.env, .sql, .log)
- Headers de segurança (X-Frame-Options, etc)
- Desabilita listagem de diretórios

---

## 📊 Monitoramento

### Logs de Erro
```
Painel Hostinger → Avançado → Logs de Erro
```

### Estatísticas
```
Painel → Estatísticas → Uso de Recursos
```

### Uptime
A Hostinger monitora automaticamente.

---

## 🐛 Troubleshooting

### Erro 500 Internal Server Error
**Causa:** Problema no `.htaccess` ou PHP

**Solução:**
1. Renomeie temporariamente `.htaccess` para `.htaccess.bak`
2. Se funcionar, o problema está no .htaccess
3. Verifique logs de erro no painel

### Página em branco
**Causa:** Erro de PHP

**Solução:**
1. Ative debug no PHP:
   ```php
   <?php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ?>
   ```
2. Veja os erros no navegador
3. Corrija e remova o debug

### Arquivos não aparecem
**Causa:** Cache ou permissões

**Solução:**
1. Limpe cache do navegador (Ctrl+F5)
2. Verifique permissões:
   - Pastas: 755
   - Arquivos: 644

---

## ✅ Checklist de Deploy

- [ ] Domínio configurado apontando para Hostinger
- [ ] SSL ativado (HTTPS)
- [ ] `index.php` enviado para `public_html/`
- [ ] `.htaccess` enviado para `public_html/`
- [ ] Site acessível via https://licita.pub
- [ ] Testado em mobile/desktop
- [ ] Emails configurados (contato@licita.pub)

---

## 📞 Suporte Hostinger

**Caso precise de ajuda:**
- Chat 24/7 no painel
- Email: suporte@hostinger.com.br
- Base de conhecimento: https://support.hostinger.com/pt-br/

---

## 🎉 Pronto para Enviar!

**Arquivos para enviar AGORA:**
1. `backend/public/index.php` → `public_html/index.php`
2. `backend/public/.htaccess` → `public_html/.htaccess`

**Em 5 minutos seu site estará no ar! 🚀**

---

**Última atualização:** 23/10/2025
**Versão:** 1.0
