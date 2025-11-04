# 🚨 INSTRUÇÕES PARA UPLOAD CORRETO DO api.js

## ❌ O Problema

O arquivo `api.js` no servidor **NÃO está sendo atualizado** corretamente.

**Evidência:**
```
baseURL: https://licita.pub/api  ← ERRADO
Métodos get/post: ✗  ← Não existem
```

---

## ✅ SOLUÇÃO PASSO A PASSO

### 1️⃣ DELETAR o arquivo antigo PRIMEIRO

**Via cPanel File Manager:**

1. Vá para: `/public_html/frontend/js/`
2. Localize o arquivo `api.js`
3. **Clique com botão direito** → **Delete**
4. Confirme a exclusão
5. **Verifique** que o arquivo sumiu da lista

---

### 2️⃣ FAZER UPLOAD do arquivo novo

1. **AINDA na pasta** `/public_html/frontend/js/`
2. Clique em **Upload**
3. Selecione o arquivo `api.js` do seu computador
   - Caminho local: `/workspaces/licita.pub/frontend/js/api.js`
4. Aguarde o upload completar (100%)
5. Clique em **"Go Back to..."** para voltar

---

### 3️⃣ VERIFICAR as permissões

1. Localize o arquivo `api.js` (agora novo)
2. **Clique com botão direito** → **Change Permissions**
3. Defina: **644** ou marque:
   - Owner: Read ✓ Write ✓
   - Group: Read ✓
   - Public: Read ✓
4. Clique **Change Permissions**

---

### 4️⃣ LIMPAR cache do navegador

**Método 1 - Hard Reload:**
```
Windows: Ctrl + Shift + R
Mac: Cmd + Shift + R
```

**Método 2 - Limpar tudo:**
```
Ctrl + Shift + Delete (Chrome/Firefox)
→ Marcar "Cached images and files"
→ Limpar
```

**Método 3 - Aba anônita:**
```
Ctrl + Shift + N (Chrome)
Ctrl + Shift + P (Firefox)
```

---

### 5️⃣ TESTAR se funcionou

Acesse:
```
https://licita.pub/frontend/teste-final.html
```

No **PASSO 1**, deve mostrar:
```
✓ api object existe

baseURL:
https://licita.pub/backend/public/api  ← DEVE TER /backend/public/

✓ baseURL contém /public/ - CORRETO!

Métodos disponíveis:
  - get: ✓  ← DEVE SER ✓
  - post: ✓  ← DEVE SER ✓
  - me: ✓
  - login: ✓
```

**E no Console (F12) deve aparecer:**
```
API Service v2.0-CORRIGIDO carregado
```

---

## 🔍 VERIFICAÇÃO ALTERNATIVA

Se ainda mostrar erro, abra o Console (F12) e execute:

```javascript
// Ver versão do api carregado
console.log('Versão API:', api.version);
console.log('baseURL:', api.baseURL);

// Deve mostrar:
// Versão API: 2.0-CORRIGIDO
// baseURL: https://licita.pub/backend/public/api
```

Se mostrar `undefined` ou versão antiga = arquivo não foi atualizado!

---

## ⚠️ POSSÍVEIS PROBLEMAS

### Problema 1: Fazendo upload no lugar errado
**Solução:** Confirme que está em `/public_html/frontend/js/`

### Problema 2: Fazendo upload do arquivo errado
**Solução:** Confirme que está usando o arquivo de `/workspaces/licita.pub/frontend/js/api.js`

### Problema 3: Cache agressivo
**Solução:** Adicione `?v=2` na URL:
```
https://licita.pub/frontend/teste-final.html?v=2
```

### Problema 4: CDN/Cloudflare cacheando
**Solução:** Se usar Cloudflare:
- Dashboard → Caching → Purge Everything

---

## 🎯 CHECKLIST

- [ ] Deletei o `api.js` antigo
- [ ] Verifiquei que sumiu da lista
- [ ] Fiz upload do novo `api.js`
- [ ] Verifiquei que apareceu na lista
- [ ] Defini permissões 644
- [ ] Limpei cache do navegador (Ctrl+Shift+Delete)
- [ ] Abri aba anônima para testar
- [ ] Acessei `teste-final.html`
- [ ] PASSO 1 mostra ✓ e `/public/` no caminho
- [ ] Console mostra "API Service v2.0-CORRIGIDO"

---

## 💡 DICA FINAL

Depois que o PASSO 1 mostrar ✓, faça:

1. **PASSO 2:** Login (deve salvar session)
2. **PASSO 3:** Verificar (deve mostrar session)
3. **PASSO 4:** Testar API (deve funcionar)
4. **PASSO 5:** Simular app (deve dar sucesso)

Então:
```
https://licita.pub/frontend/app.html
```

**DEVE FUNCIONAR!** 🎉

---

**Siga esses passos EXATAMENTE nessa ordem e vai funcionar!** 🚀
