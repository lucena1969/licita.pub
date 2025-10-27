# 🚀 DEPLOY RÁPIDO - LICITA.PUB

## Problema Atual

Os arquivos foram commitados no GitHub, mas o servidor Hostinger ainda não tem as atualizações.

## Solução: Atualizar Servidor

### Opção 1: Via SSH (Mais Rápido)

```bash
# 1. Conectar via SSH ao servidor Hostinger
ssh u590097272@licita.pub

# 2. Navegar até o diretório do projeto
cd /home/u590097272/domains/licita.pub/public_html

# 3. Fazer pull das últimas alterações
git pull origin main

# 4. Verificar se os arquivos foram atualizados
ls -la frontend/css/
ls -la frontend/js/

# 5. Sair
exit
```

### Opção 2: Via cPanel File Manager

1. Acesse https://hpanel.hostinger.com
2. Vá em **File Manager**
3. Navegue até `/domains/licita.pub/public_html`
4. Abra o terminal embutido ou use a opção "Git Pull"

### Opção 3: Via FTP (Manual)

1. Conecte via FTP (FileZilla ou similar)
2. Navegue até `/domains/licita.pub/public_html/frontend`
3. Faça upload manual das pastas:
   - `frontend/css/`
   - `frontend/js/`
   - `frontend/login.html`
   - `frontend/cadastro.html`

---

## Verificar se Funcionou

Após atualizar, teste:

1. **Login:** https://licita.pub/frontend/login.html
   - CSS deve carregar (fundo gradiente roxo/azul)
   - Scripts devem funcionar

2. **Cadastro:** https://licita.pub/frontend/cadastro.html
   - CSS deve carregar
   - Máscaras de CPF/CNPJ devem funcionar

3. **Home:** https://licita.pub/
   - Nova seção de Planos deve aparecer
   - CTAs devem direcionar para `/frontend/cadastro.html`

---

## 🔧 Comandos Úteis para Verificação

```bash
# Verificar última atualização do repositório no servidor
cd /home/u590097272/domains/licita.pub/public_html
git log -1 --oneline

# Verificar se os arquivos CSS existem
ls -lh frontend/css/auth.css

# Verificar permissões (devem ser 644)
stat frontend/css/auth.css

# Forçar update se necessário
git fetch origin
git reset --hard origin/main
```

---

## 📋 Checklist de Deploy

- [ ] Conectar ao servidor via SSH ou cPanel
- [ ] Executar `git pull origin main`
- [ ] Verificar se arquivos foram atualizados
- [ ] Testar https://licita.pub/frontend/login.html
- [ ] Testar https://licita.pub/frontend/cadastro.html
- [ ] Verificar https://licita.pub/ (home com planos)
- [ ] **EXECUTAR MIGRATION 003** (se ainda não executou)

---

## ⚠️ IMPORTANTE: Migration do Banco

Se ainda não executou a migration 003, faça agora:

```bash
# Via SSH no servidor
cd /home/u590097272/domains/licita.pub/public_html/backend
php database/run_migration_003.php
```

Ou via phpMyAdmin:
1. Acesse phpMyAdmin
2. Selecione banco `u590097272_licitapub`
3. Vá em SQL
4. Copie e cole o conteúdo de `backend/database/migrations/003_atualizar_usuarios_limites.sql`
5. Executar

---

## 🎯 Próximos Passos Após Deploy

1. ✅ Testar cadastro de novo usuário
2. ✅ Testar login
3. ✅ Verificar se API de limites funciona
4. ✅ Configurar cron de limpeza (se ainda não configurou)

---

**Última atualização:** 27/10/2025
