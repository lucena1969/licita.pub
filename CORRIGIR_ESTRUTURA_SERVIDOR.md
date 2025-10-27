# 🔧 CORRIGIR ESTRUTURA DO SERVIDOR - PASSO A PASSO

## Situação Atual
❌ `https://licita.pub/backend/public/api/auth/register.php` retorna 404
✅ Isso significa que a estrutura de pastas no servidor está diferente do repositório

---

## PASSO 1: Descobrir a Estrutura Atual

Conecte via SSH e execute:

```bash
ssh u590097272@licita.pub

# Ver onde você está
pwd

# Navegar para o diretório do site
cd ~/domains/licita.pub/public_html

# Listar o que tem na raiz
ls -la

# Ver se existe pasta backend
ls -la backend/ 2>/dev/null || echo "Pasta backend não existe"

# Ver se existe pasta api diretamente
ls -la api/ 2>/dev/null || echo "Pasta api não existe"

# Mostrar estrutura completa (primeiros 2 níveis)
find . -maxdepth 2 -type d
```

**Copie e cole a saída aqui para eu analisar!**

---

## PASSO 2: Reorganizar Estrutura (Execute DEPOIS de ver a saída acima)

### Opção A: Backup e Clone Limpo (RECOMENDADO)

```bash
# Conectar ao servidor
ssh u590097272@licita.pub

# Ir para o diretório de domínios
cd ~/domains/licita.pub/

# Fazer backup do public_html atual
mv public_html public_html_OLD_$(date +%Y%m%d_%H%M%S)

# Clonar o repositório completo
git clone https://github.com/lucena1969/licita.pub.git public_html

# Entrar no diretório
cd public_html

# Verificar estrutura
ls -la
# Deve mostrar: backend/, frontend/, .htaccess, .git/, etc

# Copiar o .env do backup (se existir)
cp ../public_html_OLD_*/backend/.env backend/.env 2>/dev/null || echo "Sem .env anterior"

# Verificar se API existe agora
ls -la backend/public/api/auth/

# Testar permissões
chmod -R 755 backend/public/api/
```

### Opção B: Reorganizar Manualmente (Se não quiser clonar)

```bash
# No servidor
cd ~/domains/licita.pub/public_html

# Criar estrutura correta
mkdir -p backend/public/api
mkdir -p frontend

# Se os arquivos da API estão em /public_html/api/, mover para backend/public/api/
if [ -d "api" ]; then
    mv api backend/public/
fi

# Mover arquivos do backend
# (ajustar conforme sua estrutura atual)

# Fazer git pull
git pull origin main
```

---

## PASSO 3: Verificar .env

```bash
cd ~/domains/licita.pub/public_html/backend

# Verificar se .env existe
cat .env

# Se não existir, criar:
cat > .env << 'EOF'
# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u590097272_licitapub
DB_USERNAME=u590097272_neto
DB_PASSWORD=Numse!2020

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://licita.pub

# JWT Secret (gerar um novo)
JWT_SECRET=sua_chave_secreta_aqui_mudar_123456

# PNCP API
PNCP_API_URL=https://pncp.gov.br/api/consulta/v1
EOF
```

---

## PASSO 4: Executar Migration 003

```bash
cd ~/domains/licita.pub/public_html/backend

# Executar migration
php database/run_migration_003.php

# Se der erro de PDO, executar via phpMyAdmin:
# 1. Acesse phpMyAdmin
# 2. Selecione banco u590097272_licitapub
# 3. Vá em SQL
# 4. Cole o conteúdo de backend/database/migrations/003_atualizar_usuarios_limites.sql
# 5. Executar
```

---

## PASSO 5: Verificar Permissões

```bash
cd ~/domains/licita.pub/public_html

# Ajustar permissões
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# API precisa ser executável
chmod -R 755 backend/public/api/
chmod 644 backend/.env
```

---

## PASSO 6: Testar

### Teste 1: Acessar API diretamente
```
https://licita.pub/backend/public/api/auth/register.php
```
**Deve retornar JSON** (mesmo que erro de método)

### Teste 2: Acessar via /api (com .htaccess)
```
https://licita.pub/api/auth/register.php
```
**Deve retornar JSON também**

### Teste 3: Cadastro no site
```
https://licita.pub/frontend/cadastro.html
```
Preencher formulário e clicar em "Criar conta"

---

## PASSO 7: Debug se Ainda Não Funcionar

```bash
# Ver logs de erro do Apache
tail -100 ~/logs/error_log

# Verificar se mod_rewrite está ativo
php -r "phpinfo();" | grep -i rewrite

# Testar curl local (no servidor)
curl -I http://localhost/backend/public/api/auth/register.php

# Ver se .htaccess está sendo lido
ls -la .htaccess
cat .htaccess
```

---

## 🎯 SOLUÇÃO ALTERNATIVA (Se Opção A não der certo)

### Mover APENAS os arquivos necessários

Se você não quiser fazer backup/clone completo, pode copiar apenas o que mudou:

```bash
# No seu computador local
cd /workspaces/licita.pub

# Criar arquivo tar com apenas as alterações
tar -czf update.tar.gz \
  .htaccess \
  backend/public/api/ \
  backend/src/ \
  backend/database/ \
  backend/cron/ \
  frontend/

# Upload via FTP ou scp
scp update.tar.gz u590097272@licita.pub:~/

# No servidor
cd ~/domains/licita.pub/public_html
tar -xzf ~/update.tar.gz
rm ~/update.tar.gz
```

---

## 📞 PRECISA DE AJUDA?

**Envie-me a saída de:**
```bash
pwd
ls -la
ls -la backend/ 2>/dev/null || echo "Sem backend"
ls -la api/ 2>/dev/null || echo "Sem api"
find . -maxdepth 2 -type d | head -20
```

Daí eu te dou comandos específicos! 🚀
