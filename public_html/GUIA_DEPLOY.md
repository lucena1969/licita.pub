# 🚀 Guia Completo de Deploy para Produção

Este guia apresenta **5 métodos diferentes** para enviar arquivos para o servidor de produção (Hostinger). Escolha o que for mais conveniente para você.

---

## 📋 **Método 1: FTP via FileZilla (MAIS FÁCIL)** ⭐

### Passo a Passo:

1. **Baixe e instale o FileZilla:**
   - Windows: https://filezilla-project.org/download.php?type=client
   - Mac/Linux: Disponível no gerenciador de pacotes

2. **Configure a conexão:**
   ```
   Host:     ftp.licita.pub (ou ftp.seudominio.com)
   Usuário:  u590097272 (ou seu usuário cPanel)
   Senha:    [sua senha do cPanel]
   Porta:    21 (FTP) ou 22 (SFTP - mais seguro)
   ```

3. **Conecte e envie os arquivos:**
   - **Painel esquerdo:** Seu computador
   - **Painel direito:** Servidor

4. **Arraste os diretórios:**
   ```
   📁 database/migrations  →  /public_html/database/migrations
   📁 backend/src          →  /public_html/backend/src
   📁 backend/public       →  /public_html/backend/public
   ```

5. **Aguarde o upload completar** ✅

---

## 💻 **Método 2: Script PHP Automatizado (RECOMENDADO)** ⭐⭐

### Como Usar:

1. **Abra o arquivo `deploy.php` na raiz do projeto**

2. **Configure suas credenciais (linha 22):**
   ```php
   'ftp_pass' => 'SUA_SENHA_REAL_AQUI',  // Coloque sua senha
   ```

3. **Execute o script:**
   ```bash
   php deploy.php
   ```

### Opções disponíveis:
```bash
# Deploy completo (tudo)
php deploy.php

# Apenas migrações SQL
php deploy.php --migrations

# Apenas backend PHP
php deploy.php --backend
```

### ⚠️ **IMPORTANTE:**
Após usar, **DELETE** o arquivo `deploy.php` por segurança (contém sua senha).

---

## 🌐 **Método 3: Upload via cPanel File Manager**

### Passo a Passo:

1. **Acesse o cPanel:**
   - URL: https://licita.pub:2083 (ou seu painel Hostinger)
   - Faça login com suas credenciais

2. **Abra o File Manager:**
   - Procure por "Gerenciador de Arquivos" ou "File Manager"
   - Navegue até `/public_html/`

3. **Crie as pastas necessárias:**
   ```
   public_html/
   ├── database/
   │   └── migrations/
   ├── backend/
   │   ├── src/
   │   └── public/
   ```

4. **Faça upload dos arquivos:**
   - Clique em **"Upload"**
   - Selecione os arquivos das migrações
   - Aguarde o upload completar

5. **Extraia (se for ZIP):**
   - Se compactou os arquivos, clique com botão direito
   - Escolha "Extract" ou "Extrair"

---

## 📦 **Método 4: Git Deploy (AVANÇADO)**

### Pré-requisitos:
- Git instalado no servidor
- Acesso SSH

### Como Configurar:

1. **No servidor (via SSH):**
   ```bash
   cd /public_html/
   git init
   git remote add origin https://github.com/seu-usuario/licita.pub.git
   ```

2. **No seu computador:**
   ```bash
   git add .
   git commit -m "feat: adicionar migrações do banco"
   git push origin main
   ```

3. **No servidor:**
   ```bash
   git pull origin main
   ```

### Vantagens:
- ✅ Controle de versão
- ✅ Deploy rápido
- ✅ Rollback fácil

---

## 🔒 **Método 5: SFTP via Terminal (Linux/Mac)**

### Usando SCP (Secure Copy):

```bash
# Copiar diretório de migrações
scp -r database/migrations u590097272@ftp.licita.pub:/public_html/database/

# Copiar backend
scp -r backend/src u590097272@ftp.licita.pub:/public_html/backend/
scp -r backend/public u590097272@ftp.licita.pub:/public_html/backend/
```

### Usando SFTP:

```bash
# Conectar
sftp u590097272@ftp.licita.pub

# Enviar arquivos
put -r database/migrations /public_html/database/
put -r backend/src /public_html/backend/

# Sair
exit
```

---

## 🔄 **Método BÔNUS: Rsync (Sincronização Automática)**

### Windows (via WSL ou Git Bash):
```bash
rsync -avz --progress database/migrations/ \
  u590097272@ftp.licita.pub:/public_html/database/migrations/
```

### Linux/Mac:
```bash
rsync -avz --progress --delete \
  database/migrations/ \
  u590097272@ftp.licita.pub:/public_html/database/migrations/
```

### Vantagens:
- ✅ Envia apenas arquivos modificados
- ✅ Muito mais rápido em re-deploys
- ✅ Barra de progresso

---

## 📊 **Comparação dos Métodos**

| Método | Dificuldade | Velocidade | Segurança | Recomendado |
|--------|-------------|------------|-----------|-------------|
| FileZilla | ⭐ Fácil | ⭐⭐ Média | ⭐⭐ Boa | Iniciantes |
| Script PHP | ⭐⭐ Média | ⭐⭐⭐ Rápida | ⭐⭐ Boa | Sim ✅ |
| cPanel | ⭐ Fácil | ⭐ Lenta | ⭐⭐⭐ Ótima | Para poucos arquivos |
| Git | ⭐⭐⭐ Difícil | ⭐⭐⭐ Rápida | ⭐⭐⭐ Ótima | Projetos grandes |
| SFTP/SCP | ⭐⭐⭐ Difícil | ⭐⭐⭐ Rápida | ⭐⭐⭐ Ótima | Usuários avançados |
| Rsync | ⭐⭐⭐ Difícil | ⭐⭐⭐⭐ Muito rápida | ⭐⭐⭐ Ótima | Re-deploys frequentes |

---

## 🎯 **Recomendação por Situação**

### **Primeira vez / Poucos conhecimentos técnicos:**
→ Use **FileZilla** (Método 1)

### **Desenvolvedor / Quer automatizar:**
→ Use **Script PHP** (Método 2)

### **Servidor não permite FTP:**
→ Use **cPanel File Manager** (Método 3)

### **Trabalho em equipe / Controle de versão:**
→ Use **Git** (Método 4)

### **Usuário avançado Linux/Mac:**
→ Use **SFTP ou Rsync** (Métodos 5 e 6)

---

## ✅ **Checklist Pós-Deploy**

Após enviar os arquivos, verifique:

1. **Permissões dos arquivos:**
   ```bash
   chmod 644 database/migrations/*.sql
   chmod 755 database/migrations/
   ```

2. **Teste de conectividade do banco:**
   - Acesse: `https://licita.pub/backend/test-db.php`

3. **Execute as migrações:**
   - Via phpMyAdmin (recomendado)
   - Via script PHP
   - Via MySQL CLI

4. **Verifique se funcionou:**
   ```sql
   SHOW TABLES;
   SELECT COUNT(*) FROM orgaos;
   ```

---

## 🚨 **Possíveis Problemas e Soluções**

### **Erro: "530 Login authentication failed"**
**Causa:** Senha incorreta
**Solução:** Verifique usuário e senha no cPanel

### **Erro: "550 Permission denied"**
**Causa:** Sem permissão de escrita
**Solução:** Altere permissões da pasta para 755

### **Erro: "Connection timed out"**
**Causa:** Firewall bloqueando
**Solução:**
- Use porta 21 (FTP) ou 22 (SFTP)
- Desabilite VPN temporariamente
- Verifique firewall

### **Erro: "No such file or directory"**
**Causa:** Caminho incorreto
**Solução:** Certifique-se de estar em `/public_html/`

### **Upload muito lento:**
**Solução:**
- Use SFTP em vez de FTP
- Compacte arquivos antes (ZIP)
- Use Rsync (envia apenas diferenças)

---

## 📞 **Precisa de Ajuda?**

### **Credenciais necessárias:**
Para qualquer método acima, você precisará de:
- ✅ **Host FTP:** ftp.licita.pub (ou IP do servidor)
- ✅ **Usuário:** u590097272 (ou seu usuário cPanel)
- ✅ **Senha:** [sua senha do cPanel/Hostinger]
- ✅ **Porta:** 21 (FTP) ou 22 (SFTP)
- ✅ **Diretório remoto:** /public_html/

### **Onde encontrar essas informações:**
1. Acesse o painel da **Hostinger**
2. Vá em **Arquivos → FTP/SSH**
3. Lá estarão todas as credenciais

---

## ⏱️ **Tempo Estimado**

| Método | Primeira vez | Próximos deploys |
|--------|--------------|------------------|
| FileZilla | 10-15 min | 5-10 min |
| Script PHP | 2-5 min | 1-2 min |
| cPanel | 15-20 min | 10-15 min |
| Git | 15-30 min (setup) | 30 segundos |
| SFTP/Rsync | 5-10 min | 1-2 min |

---

## 🎉 **Próximos Passos (após deploy bem-sucedido)**

1. ✅ **Executar migrações no phpMyAdmin**
2. ✅ **Desenvolver integração com PNCP**
3. ✅ **Criar endpoints da API**
4. ✅ **Configurar cron job de sincronização**

---

**Escolha o método que preferir e mãos à obra!** 🚀

Qualquer dúvida, é só perguntar! 😊
