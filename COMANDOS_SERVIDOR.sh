#!/bin/bash
# Comandos para executar no servidor Hostinger
# Copie e cole cada bloco no terminal SSH

echo "=== PASSO 1: Fazer backup do .htaccess e .env ==="
cd ~/domains/licita.pub/public_html
cp .htaccess .htaccess.backup
cp backend/.env backend/.env.backup 2>/dev/null || echo "Sem .env para backup"

echo "=== PASSO 2: Fazer git pull para atualizar ==="
cd ~/domains/licita.pub/public_html
git status
git pull origin main

echo "=== PASSO 3: Verificar se .htaccess foi atualizado ==="
cat .htaccess | head -10

echo "=== PASSO 4: Verificar estrutura da API ==="
ls -la backend/public/api/auth/

echo "=== PASSO 5: Ajustar permissões ==="
chmod 644 .htaccess
chmod -R 755 backend/public/api/

echo "=== PASSO 6: Testar endpoint localmente ==="
curl -I http://localhost/backend/public/api/auth/register.php

echo "=== PASSO 7: Verificar logs de erro ==="
tail -20 ~/logs/error_log

echo "=== CONCLUÍDO! ==="
echo "Agora teste no navegador:"
echo "1. https://licita.pub/backend/public/api/auth/register.php"
echo "2. https://licita.pub/api/auth/register.php"
echo "3. https://licita.pub/frontend/cadastro.html"
