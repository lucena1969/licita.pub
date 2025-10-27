# 🔍 DEBUG: Erro 404 na API

## Problema
```
/api/auth/register.php: Failed to load resource: the server responded with a status of 404
```

## Causa
O endpoint `/api/auth/register.php` não está sendo encontrado porque a estrutura de pastas no servidor pode estar diferente.

---

## ✅ SOLUÇÃO 1: Verificar Estrutura no Servidor

### Passo 1: Conectar via SSH e verificar

```bash
ssh u590097272@licita.pub

# Verificar onde está o projeto
pwd
# Deve retornar: /home/u590097272

# Ver estrutura de pastas
ls -la domains/licita.pub/

# Verificar o que tem em public_html
ls -la domains/licita.pub/public_html/

# Verificar se backend existe
ls -la domains/licita.pub/public_html/backend/

# Verificar se API existe
ls -la domains/licita.pub/public_html/backend/public/api/
```

### Passo 2: Identificar o Cenário

**Cenário A: Projeto inteiro em public_html/**
```
public_html/
├── .htaccess (raiz - o que acabamos de criar)
├── backend/
│   └── public/
│       └── api/
│           └── auth/
│               └── register.php
└── frontend/
    ├── login.html
    └── cadastro.html
```
✅ **Se é esse o caso:** Fazer git pull e testar

**Cenário B: Apenas backend/public em public_html/**
```
public_html/
├── index.php (do backend/public)
├── api/
│   └── auth/
│       └── register.php
└── (outros arquivos do backend/public)
```
✅ **Se é esse o caso:** Precisa reorganizar estrutura

---

## ✅ SOLUÇÃO 2: Reorganizar Estrutura (Se Cenário B)

Se apenas o conteúdo de `backend/public` está em `public_html`, você tem 2 opções:

### Opção 2A: Mover Todo o Projeto (Recomendado)

```bash
# No servidor
cd /home/u590097272/domains/licita.pub/

# Backup do public_html atual
mv public_html public_html_backup

# Clonar projeto completo
git clone https://github.com/lucena1969/licita.pub.git public_html

# Verificar
ls -la public_html/
# Deve mostrar: backend/, frontend/, .htaccess, etc
```

### Opção 2B: Criar Symlinks

```bash
# Criar link simbólico de /api para /backend/public/api
cd /home/u590097272/domains/licita.pub/public_html
ln -s backend/public/api api

# Verificar
ls -la api/
```

---

## ✅ SOLUÇÃO 3: Ajustar .htaccess (Temporário para Teste)

Se nada funcionar, teste acessar diretamente:

```
https://licita.pub/backend/public/api/auth/register.php
```

Se funcionar diretamente, o problema é apenas o .htaccess.

Então ajuste o `frontend/js/api.js`:

```javascript
getBaseURL() {
    const hostname = window.location.hostname;

    // Produção - CAMINHO DIRETO (temporário)
    if (hostname === 'licita.pub' || hostname === 'www.licita.pub') {
        return 'https://licita.pub/backend/public/api';
    }

    // ... resto do código
}
```

---

## ✅ SOLUÇÃO 4: Testar Endpoint Diretamente

Abra o navegador e acesse:

```
https://licita.pub/backend/public/api/auth/register.php
```

**Se retornar JSON** (mesmo que erro de método):
```json
{
  "success": false,
  "error": "METODO_NAO_PERMITIDO",
  "message": "Método GET não permitido. Permitidos: POST"
}
```
✅ **API está funcionando!** Problema é só no .htaccess

**Se retornar 404:**
❌ Estrutura de pastas está errada no servidor

---

## 🔧 COMANDOS ÚTEIS PARA DEBUG

```bash
# Ver conteúdo do .htaccess na raiz
cat /home/u590097272/domains/licita.pub/public_html/.htaccess

# Ver últimos logs de erro do Apache
tail -50 /home/u590097272/logs/error_log

# Testar rewrite do Apache
curl -I https://licita.pub/api/auth/register.php

# Ver se mod_rewrite está ativo
php -i | grep mod_rewrite
```

---

## 📋 CHECKLIST DE RESOLUÇÃO

Faça na ordem:

1. [ ] Conectar via SSH ao servidor
2. [ ] Verificar estrutura com `ls -la public_html/`
3. [ ] Identificar qual Cenário (A ou B)
4. [ ] Se Cenário A: fazer `git pull origin main`
5. [ ] Se Cenário B: reorganizar estrutura (Opção 2A ou 2B)
6. [ ] Testar endpoint direto: `https://licita.pub/backend/public/api/auth/register.php`
7. [ ] Verificar se .htaccess foi atualizado no servidor
8. [ ] Limpar cache do navegador (Ctrl+Shift+R)
9. [ ] Testar cadastro novamente

---

## 🆘 Se Nada Funcionar

Me envie a saída destes comandos:

```bash
pwd
ls -la
ls -la backend/
ls -la backend/public/
ls -la backend/public/api/
cat .htaccess
```

Daí eu te ajudo a resolver! 🚀
