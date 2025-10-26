# ⏰ Configurar Cron Jobs na Hostinger (cPanel)

Guia completo para configurar a sincronização automática com o PNCP usando cron jobs no cPanel da Hostinger.

---

## 📋 **Pré-requisitos**

Antes de configurar o cron, certifique-se de que:

✅ Todos os arquivos foram enviados para o servidor
✅ As migrações SQL foram executadas
✅ O arquivo `backend/cron/sincronizar_pncp.php` existe no servidor
✅ As permissões estão corretas

---

## 🔍 **PASSO 1: Descobrir o Caminho Completo do PHP**

### **Via cPanel → Terminal (ou SSH):**

```bash
which php
# ou
which php-cli
# ou
which php8.2
```

**Resultado esperado:**
```
/usr/bin/php
# ou
/usr/local/bin/php
# ou
/opt/alt/php82/usr/bin/php
```

**⚠️ Anote esse caminho! Você vai usar no cron.**

---

## 🗂️ **PASSO 2: Descobrir o Caminho Completo do Projeto**

### **Via cPanel → Gerenciador de Arquivos:**

1. Acesse o Gerenciador de Arquivos
2. Navegue até `public_html`
3. Abra a pasta `backend/cron`
4. Clique com botão direito em `sincronizar_pncp.php`
5. Escolha **"Copy Path"** ou veja a barra de endereço

**Exemplo de caminho:**
```
/home/u590097272/public_html/backend/cron/sincronizar_pncp.php
```

**⚠️ Anote esse caminho completo!**

---

## ⚙️ **PASSO 3: Configurar o Cron Job**

### **Via cPanel → Cron Jobs:**

1. **Acesse o cPanel da Hostinger**
2. **Procure por "Cron Jobs"** (geralmente em "Advanced" ou "Avançado")
3. **Clique em "Cron Jobs"**

### **Configuração do Cron:**

#### **Opção A: Interface Comum (Common Settings)**

Escolha: **"Once Per Day"** (Uma vez por dia)

Depois ajuste:
- **Minuto:** `0`
- **Hora:** `6` (6h da manhã)
- **Dia:** `*`
- **Mês:** `*`
- **Dia da Semana:** `*`

#### **Opção B: Interface Simplificada**

Se o cPanel mostrar opções simples como:
- **Every Hour**
- **Every Day**
- **Custom**

Escolha **"Custom"** e insira:
```
0 6 * * *
```

### **Comando a Executar:**

**Formato Básico:**
```bash
/usr/bin/php /home/u590097272/public_html/backend/cron/sincronizar_pncp.php
```

**Formato com Log (RECOMENDADO):**
```bash
/usr/bin/php /home/u590097272/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1
```

**⚠️ IMPORTANTE:** Substitua:
- `/usr/bin/php` → Pelo caminho que você descobriu no Passo 1
- `/home/u590097272/` → Pelo seu usuário real

### **Exemplo Completo:**

```
Hora: 0 6 * * *
Comando: /usr/bin/php /home/u590097272/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1
```

4. **Clique em "Add New Cron Job"** ou **"Adicionar Cron Job"**

---

## 📧 **PASSO 4: Desabilitar Emails de Notificação (Opcional)**

O cPanel envia um email toda vez que o cron roda. Para desabilitar:

1. Na página de Cron Jobs, procure por **"Cron Email"**
2. **Delete o email** ou deixe em branco
3. **Salve**

Ou adicione isso no início do comando:
```bash
MAILTO=""
/usr/bin/php /caminho/do/script.php
```

---

## 🧪 **PASSO 5: Testar Manualmente ANTES do Cron**

**MUITO IMPORTANTE:** Teste o script manualmente primeiro!

### **Via Terminal SSH:**

```bash
# 1. Conectar via SSH
ssh u590097272@seu-servidor.com

# 2. Navegar até o diretório
cd /home/u590097272/public_html/backend/cron

# 3. Executar manualmente
php sincronizar_pncp.php --ultimos-dias=1

# 4. Ver se dá erro
```

### **Via cPanel → Terminal:**

1. Acesse **Terminal** no cPanel
2. Execute:
```bash
cd public_html/backend/cron
php sincronizar_pncp.php --ultimos-dias=1
```

### **Possíveis Erros e Soluções:**

#### **Erro: "php: command not found"**
**Solução:** Use o caminho completo:
```bash
/usr/bin/php sincronizar_pncp.php --ultimos-dias=1
```

#### **Erro: "No such file or directory"**
**Solução:** Verifique se o arquivo existe:
```bash
ls -la sincronizar_pncp.php
```

#### **Erro: "Permission denied"**
**Solução:** Dê permissão de execução:
```bash
chmod +x sincronizar_pncp.php
```

#### **Erro: "require_once: failed opening"**
**Solução:** Verifique se todos os arquivos foram enviados:
```bash
ls -la ../src/Services/PNCPService.php
ls -la ../src/Models/Licitacao.php
```

#### **Erro: "Connection refused" ou "Timeout"**
**Solução:** É normal, a API do PNCP às vezes está lenta. Tente novamente.

---

## 🔍 **PASSO 6: Verificar se o Cron Está Funcionando**

### **Opção 1: Ver Logs do Cron**

Se você configurou com log (`>> /logs/pncp_sync.log`):

```bash
# Via Terminal
tail -f /home/u590097272/logs/pncp_sync.log

# Ou via Gerenciador de Arquivos
# Navegue até /logs/ e abra pncp_sync.log
```

### **Opção 2: Verificar no Banco de Dados**

```sql
-- Ver última sincronização
SELECT *
FROM logs_sincronizacao
WHERE fonte = 'PNCP'
ORDER BY iniciado DESC
LIMIT 1;

-- Ver licitações sincronizadas hoje
SELECT COUNT(*) AS total
FROM licitacoes
WHERE DATE(sincronizado_em) = CURDATE();
```

### **Opção 3: Verificar Email**

Se não desabilitou os emails, você receberá um email toda vez que o cron rodar.

---

## 📝 **Exemplos de Configuração Completa**

### **Exemplo 1: Diário às 06:00**
```
Frequência: 0 6 * * *
Comando: /usr/bin/php /home/u590097272/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1
```

### **Exemplo 2: A cada 12 horas (06:00 e 18:00)**
```
Frequência: 0 6,18 * * *
Comando: /usr/bin/php /home/u590097272/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1
```

### **Exemplo 3: A cada 6 horas**
```
Frequência: 0 */6 * * *
Comando: /usr/bin/php /home/u590097272/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1
```

### **Exemplo 4: Somente dias úteis (segunda a sexta)**
```
Frequência: 0 6 * * 1-5
Comando: /usr/bin/php /home/u590097272/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1
```

---

## 🛠️ **PASSO 7: Criar a Pasta de Logs**

Se a pasta `/logs/` não existir, crie:

### **Via Gerenciador de Arquivos:**
1. Navegue até `/home/u590097272/`
2. Clique em **"New Folder"** ou **"Nova Pasta"**
3. Nome: `logs`
4. Clique em **"Create"**

### **Via Terminal:**
```bash
mkdir -p /home/u590097272/logs
chmod 755 /home/u590097272/logs
```

---

## 🔧 **Troubleshooting**

### **O cron não está rodando**

1. **Verifique se o cron foi salvo:**
   - Volte em Cron Jobs
   - Veja se ele aparece na lista

2. **Verifique as permissões:**
   ```bash
   chmod +x /home/u590097272/public_html/backend/cron/sincronizar_pncp.php
   ```

3. **Teste o caminho do PHP:**
   ```bash
   /usr/bin/php -v
   ```
   Se der erro, tente:
   ```bash
   /usr/local/bin/php -v
   /opt/alt/php82/usr/bin/php -v
   ```

4. **Verifique se o script tem erros:**
   ```bash
   php -l /home/u590097272/public_html/backend/cron/sincronizar_pncp.php
   ```
   Deve retornar: `No syntax errors detected`

### **O cron roda mas dá erro**

Verifique o log:
```bash
cat /home/u590097272/logs/pncp_sync.log
```

Ou execute manualmente e veja o erro:
```bash
cd /home/u590097272/public_html/backend/cron
php sincronizar_pncp.php --ultimos-dias=1
```

---

## 📊 **Monitoramento**

### **Script para monitorar o cron:**

Crie um arquivo `monitorar_cron.sh`:

```bash
#!/bin/bash
echo "=== Status do Cron PNCP ==="
echo ""
echo "Última execução:"
tail -n 20 /home/u590097272/logs/pncp_sync.log
echo ""
echo "Licitações sincronizadas hoje:"
mysql -u u590097272_licitapub -p u590097272_licitapub -e "SELECT COUNT(*) FROM licitacoes WHERE DATE(sincronizado_em) = CURDATE();"
```

Execute:
```bash
bash monitorar_cron.sh
```

---

## 🎯 **Checklist Final**

Antes de considerar concluído, verifique:

- [ ] ✅ Descobri o caminho do PHP (`which php`)
- [ ] ✅ Descobri o caminho do script (`/home/u590097272/public_html/...`)
- [ ] ✅ Testei o script manualmente (`php sincronizar_pncp.php --ultimos-dias=1`)
- [ ] ✅ O teste manual funcionou sem erros
- [ ] ✅ Criei a pasta `/logs/`
- [ ] ✅ Configurei o cron job no cPanel
- [ ] ✅ Desabilitei emails de notificação (opcional)
- [ ] ✅ Aguardei o cron rodar (ou forçar execução)
- [ ] ✅ Verifiquei os logs (`tail -f /logs/pncp_sync.log`)
- [ ] ✅ Verifiquei dados no banco (`SELECT COUNT(*) FROM licitacoes`)

---

## 🆘 **Ainda com Dúvidas?**

Se você está tendo problemas específicos, me envie:

1. **Qual erro aparece** quando executa manualmente
2. **Print do cPanel** (Cron Jobs)
3. **Conteúdo do log** (`cat /logs/pncp_sync.log`)
4. **Versão do PHP** (`php -v`)

---

## 🎉 **Quando Funcionar**

Você saberá que está funcionando quando:

1. ✅ O log `/logs/pncp_sync.log` for atualizado diariamente
2. ✅ A tabela `licitacoes` receber novos registros
3. ✅ A tabela `logs_sincronizacao` mostrar execuções bem-sucedidas
4. ✅ Nenhum email de erro for enviado

---

**Boa sorte!** 🚀

Se precisar de ajuda, é só me chamar com os detalhes do erro! 😊
