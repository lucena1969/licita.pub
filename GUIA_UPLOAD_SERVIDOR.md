# 📤 GUIA: Upload de Arquivos para o Servidor

## 🎯 Problema Identificado

Os arquivos do projeto **não estão no servidor ainda**!

**Estrutura atual no servidor:**
```
/home/u590097272/domains/licita.pub/
└── public_html/    ← Só tem alguns arquivos PHP aqui
```

**Estrutura necessária:**
```
/home/u590097272/domains/licita.pub/
└── public_html/
    └── backend/    ← PRECISA CRIAR ESSA PASTA E ENVIAR TUDO
```

---

## 📋 Arquivos que Precisam Ser Enviados

### **1. Estrutura de Pastas**
```
public_html/backend/
├── .env                          ← Configurações do banco
├── .htaccess                     ← Configuração Apache
├── src/
│   ├── Config/
│   │   ├── Config.php           ← Carrega .env
│   │   └── Database.php         ← Conexão MySQL
│   ├── Models/
│   │   ├── Licitacao.php
│   │   └── Orgao.php
│   ├── Repositories/
│   │   ├── LicitacaoRepository.php  ← COM UPSERT
│   │   └── OrgaoRepository.php
│   └── Services/
│       └── PNCPService.php      ← USA UPSERT
├── public/
│   ├── .htaccess
│   ├── index.php                ← Landing page
│   ├── admin_duplicatas.php
│   ├── verificar_duplicatas_web.php
│   └── limpar_duplicatas_web.php
├── cron/
│   └── sincronizar_pncp.php
└── database/
    ├── migrations/
    │   └── 004_adicionar_unique_pncp_id.sql
    ├── verificar_duplicatas.php
    └── limpar_duplicatas.php
```

---

## 🔧 OPÇÃO 1: Upload via FTP/SFTP (RECOMENDADO)

### **Passo 1: Conectar via FTP**

Use um cliente FTP como FileZilla, WinSCP ou Cyberduck.

**Credenciais:**
- **Host:** ftp.licita.pub (ou IP do servidor)
- **Usuário:** u590097272
- **Senha:** (sua senha da Hostinger)
- **Porta:** 21 (FTP) ou 22 (SFTP)

### **Passo 2: Navegar até o diretório correto**

No servidor, navegue até:
```
/home/u590097272/domains/licita.pub/public_html/
```

### **Passo 3: Criar pasta backend**

1. Dentro de `public_html/`, crie a pasta: `backend`
2. Entre na pasta `backend`

### **Passo 4: Upload dos arquivos**

Do seu computador local (projeto `/workspaces/licita.pub/`):

1. **Envie a pasta completa `backend/`** para dentro de `public_html/`
2. Certifique-se que a estrutura ficou:
   ```
   public_html/
   └── backend/
       ├── .env
       ├── src/
       ├── public/
       ├── cron/
       └── database/
   ```

### **Passo 5: Verificar permissões**

Após upload, ajuste as permissões:
- **Pastas:** 755
- **Arquivos PHP:** 644
- **Arquivo .env:** 640 (mais seguro)

---

## 🔧 OPÇÃO 2: Upload via cPanel File Manager

### **Passo 1: Acessar cPanel**

1. Acesse: https://hpanel.hostinger.com
2. Entre na sua conta
3. Clique em "File Manager"

### **Passo 2: Navegar até public_html**

1. No File Manager, vá para:
   ```
   home/u590097272/domains/licita.pub/public_html/
   ```

### **Passo 3: Criar estrutura**

1. Clique em "New Folder"
2. Crie a pasta: `backend`
3. Entre na pasta `backend`

### **Passo 4: Upload**

#### **Opção A: Upload individual (arquivos pequenos)**
1. Clique em "Upload"
2. Selecione os arquivos um a um
3. Aguarde upload completar

#### **Opção B: Upload de ZIP (RECOMENDADO)**

**No seu computador local:**
1. Vá para a pasta do projeto
2. Compacte a pasta `backend/` inteira em `backend.zip`
3. Certifique-se que **inclui o .env**!

**No cPanel:**
1. Clique em "Upload"
2. Envie o arquivo `backend.zip`
3. Após upload, clique com botão direito no `backend.zip`
4. Selecione "Extract"
5. Delete o arquivo `backend.zip` após extrair

---

## 🔧 OPÇÃO 3: Upload via Git (SE CONFIGURADO)

Se você tem Git configurado no servidor:

```bash
# Conectar via SSH
ssh u590097272@licita.pub

# Navegar até o diretório
cd /home/u590097272/domains/licita.pub/public_html/

# Clonar repositório ou fazer pull
git clone <seu-repositorio> backend
# OU
cd backend && git pull
```

---

## ⚠️ ATENÇÃO: Arquivo .env

O arquivo `.env` contém informações sensíveis. **Certifique-se de enviá-lo!**

**Verifique se contém:**
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u590097272_licitapub
DB_USERNAME=u590097272_neto
DB_PASSWORD=SenhaForte123!

APP_ENV=production
APP_DEBUG=false
```

**Se não tiver o .env no servidor:**
1. Crie manualmente via File Manager
2. Copie o conteúdo do `.env` local
3. Cole no servidor
4. Salve como `.env` (com o ponto na frente!)
5. Ajuste permissão para 640

---

## ✅ Checklist Pós-Upload

Após enviar todos os arquivos, verifique:

### **1. Estrutura de Pastas**
```bash
public_html/
└── backend/
    ├── .env              ← ✅ Existe?
    ├── .htaccess         ← ✅ Existe?
    ├── src/              ← ✅ Existe?
    │   └── Config/
    │       ├── Config.php
    │       └── Database.php
    ├── public/           ← ✅ Existe?
    │   ├── index.php
    │   ├── admin_duplicatas.php
    │   ├── verificar_duplicatas_web.php
    │   └── limpar_duplicatas_web.php
    ├── cron/             ← ✅ Existe?
    └── database/         ← ✅ Existe?
```

### **2. Testar no navegador**

Acesse novamente:
```
https://licita.pub/teste_simples.php
```

**Agora deve mostrar:**
```
✅ src/Config/Config.php
   Caminho: /home/u590097272/domains/licita.pub/public_html/backend/src/Config/Config.php
✅ src/Config/Database.php
   Caminho: /home/u590097272/domains/licita.pub/public_html/backend/src/Config/Database.php
✅ .env
   Caminho: /home/u590097272/domains/licita.pub/public_html/backend/.env
```

### **3. Testar scripts**

Se tudo ok, teste os scripts:
```
https://licita.pub/verificar_duplicatas_web.php
https://licita.pub/admin_duplicatas.php
```

---

## 🐛 Solução de Problemas

### **Problema: .env não aparece no FTP**

Arquivos com ponto (.) são ocultos. No FileZilla:
1. Menu "Server" → "Force showing hidden files"
2. OU via cPanel File Manager: Ativar "Show Hidden Files"

### **Problema: Permissões negadas**

```bash
# Via SSH
chmod 755 public_html/backend/
chmod 755 public_html/backend/src/
chmod 755 public_html/backend/src/Config/
chmod 644 public_html/backend/src/Config/*.php
chmod 640 public_html/backend/.env
```

### **Problema: Estrutura errada após upload**

Se ficou:
```
public_html/
├── src/      ← ERRADO (falta backend/)
├── public/   ← ERRADO
└── .env      ← ERRADO
```

**Corrigir:**
1. Crie pasta `backend/` dentro de `public_html/`
2. Mova todas as pastas para dentro de `backend/`

---

## 📊 Resumo

| Passo | Descrição | Status |
|-------|-----------|--------|
| 1 | Conectar ao servidor (FTP/cPanel) | ⏳ |
| 2 | Criar pasta `backend/` em `public_html/` | ⏳ |
| 3 | Upload de todos os arquivos | ⏳ |
| 4 | Verificar `.env` existe | ⏳ |
| 5 | Ajustar permissões | ⏳ |
| 6 | Testar `teste_simples.php` | ⏳ |
| 7 | Testar scripts de duplicatas | ⏳ |

---

## 🎯 Próximo Passo

**Após fazer o upload:**

1. Acesse: `https://licita.pub/teste_simples.php`
2. Veja se os arquivos aparecem como ✅
3. Se sim, pode usar os scripts de duplicatas
4. Se não, me avise qual erro apareceu

---

**Data:** 28/10/2025
**Problema:** Arquivos não estão no servidor
**Solução:** Fazer upload da pasta `backend/` completa
