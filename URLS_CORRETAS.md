# 🌐 URLs CORRETAS para Acessar os Scripts

## ⚠️ ATENÇÃO: URLs Corrigidas

Você estava tentando acessar URLs incorretas. Use as URLs abaixo:

---

## ✅ URLs CORRETAS

### **1. Teste de Conexão (comece por aqui!)**
```
https://licita.pub/test_conexao.php
```
Este script testa se tudo está funcionando antes de usar os outros.

### **2. Menu Principal**
```
https://licita.pub/admin_duplicatas.php
```

### **3. Verificar Duplicatas**
```
https://licita.pub/verificar_duplicatas_web.php
```

### **4. Limpar Duplicatas**
```
https://licita.pub/limpar_duplicatas_web.php
```

---

## ❌ URLs ERRADAS (NÃO USE)

❌ `https://licita.pub/database/verificar_duplicatas_web.php` (ERRADO)
❌ `https://licita.pub/backend/public/admin_duplicatas.php` (ERRADO)

---

## 🔍 Como Funciona o Roteamento

O arquivo `.htaccess` na raiz redireciona automaticamente:

```
https://licita.pub/arquivo.php
   ↓
backend/public/arquivo.php
```

Então você acessa **SEM** o `/backend/public/` na URL!

---

## 🚀 Passo a Passo Atualizado

1. **Teste a conexão primeiro:**
   ```
   https://licita.pub/test_conexao.php
   ```
   - Deve mostrar ✅ em todos os testes
   - Se aparecer ❌, veja o erro

2. **Se conexão estiver OK, vá para:**
   ```
   https://licita.pub/verificar_duplicatas_web.php
   ```

3. **Se houver duplicatas:**
   ```
   https://licita.pub/limpar_duplicatas_web.php
   ```
   - Senha: `licita2025`

---

## 🐛 Solução de Problemas

### **Erro 500 (Internal Server Error)**

**Causas possíveis:**
1. Extensão PDO MySQL não instalada
2. Arquivo `.env` não encontrado
3. Permissões incorretas
4. Erro de sintaxe PHP

**Solução:**
1. Acesse: `https://licita.pub/test_conexao.php`
2. Veja qual teste falha
3. Corrija o problema indicado

### **Erro 404 (Not Found)**

Você está usando URL errada. Use as URLs CORRETAS acima.

### **Página em branco**

1. Verifique logs de erro PHP
2. Pode ser erro de sintaxe ou require
3. Use `test_conexao.php` para diagnosticar

---

## 📝 Arquivos no Servidor

Os arquivos estão em:
```
/home/u590097272/domains/licita.pub/public_html/
└── backend/
    └── public/
        ├── test_conexao.php           ← NOVO (teste primeiro)
        ├── admin_duplicatas.php
        ├── verificar_duplicatas_web.php
        └── limpar_duplicatas_web.php
```

Mas você acessa via:
```
https://licita.pub/test_conexao.php
https://licita.pub/admin_duplicatas.php
https://licita.pub/verificar_duplicatas_web.php
https://licita.pub/limpar_duplicatas_web.php
```

---

## ✅ Checklist de Upload

Se estiver fazendo upload via FTP, certifique-se que enviou:

- [ ] `backend/public/test_conexao.php`
- [ ] `backend/public/admin_duplicatas.php`
- [ ] `backend/public/verificar_duplicatas_web.php`
- [ ] `backend/public/limpar_duplicatas_web.php`
- [ ] `backend/src/Config/Config.php`
- [ ] `backend/src/Config/Database.php`
- [ ] `backend/.env` (com credenciais corretas)

---

## 🔐 Permissões Recomendadas

```bash
chmod 644 backend/public/*.php
chmod 640 backend/.env
chmod 755 backend/src/Config/
```

---

**Data:** 28/10/2025
**Última atualização:** Após correção de caminhos
