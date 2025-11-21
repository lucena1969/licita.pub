# 🌐 Scripts Web para Gerenciar Duplicatas

## 📍 URLs de Acesso

Criamos versões WEB dos scripts para você poder executar pelo navegador:

### **1. Página Principal (Menu)**
```
https://licita.pub/admin_duplicatas.php
```
- Menu com links para verificar e limpar duplicatas
- Instruções de uso

### **2. Verificar Duplicatas**
```
https://licita.pub/verificar_duplicatas_web.php
```
- Mostra se há duplicatas no banco
- Estatísticas visuais
- Tabela detalhada com PNCP IDs duplicados
- **Apenas visualiza**, não altera dados

### **3. Limpar Duplicatas**
```
https://licita.pub/limpar_duplicatas_web.php
```
- Remove duplicatas do banco
- **REQUER SENHA:** `licita2025` (altere no código!)
- Interface com confirmação
- Mostra quais registros serão removidos
- **DELETA dados permanentemente!**

---

## 🔐 Segurança

### **IMPORTANTE:**

1. **Mude a senha do script de limpeza:**
   ```php
   // Em: backend/public/limpar_duplicatas_web.php
   // Linha 10
   $SENHA_ADMIN = 'SUA_SENHA_FORTE_AQUI';
   ```

2. **Após uso, REMOVA ou proteja os arquivos:**
   ```bash
   # Opção 1: Deletar (recomendado)
   rm backend/public/admin_duplicatas.php
   rm backend/public/verificar_duplicatas_web.php
   rm backend/public/limpar_duplicatas_web.php

   # Opção 2: Restringir por IP (via .htaccess)
   # Criar: backend/public/.htaccess
   <FilesMatch "(admin_duplicatas|verificar_duplicatas_web|limpar_duplicatas_web)\.php">
     Order Deny,Allow
     Deny from all
     Allow from SEU_IP_AQUI
   </FilesMatch>
   ```

---

## 📋 Como Usar

### **Passo a Passo:**

1. **Fazer backup do banco** (via cPanel ou phpMyAdmin)

2. **Acessar a página principal:**
   ```
   https://licita.pub/admin_duplicatas.php
   ```

3. **Clicar em "Verificar Agora"**
   - Verá quantas duplicatas existem
   - Poderá ver detalhes de cada uma

4. **Se houver duplicatas, clicar em "Limpar Duplicatas"**
   - Digite a senha: `licita2025`
   - Revise os dados que serão removidos
   - Marque a caixa de confirmação
   - Clique em "Limpar Duplicatas AGORA"

5. **Verificar novamente**
   - Deve mostrar: "✅ NENHUMA DUPLICATA ENCONTRADA"

6. **Executar migration** (via SSH ou phpMyAdmin)
   ```bash
   mysql -u u590097272_neto -p u590097272_licitapub < backend/database/migrations/004_adicionar_unique_pncp_id.sql
   ```

7. **REMOVER os scripts** após uso

---

## 📊 Capturas de Tela (O que você verá)

### **Verificar Duplicatas - Sem Duplicatas:**
```
✅ NENHUMA DUPLICATA ENCONTRADA!
O banco está limpo e pronto para receber o índice UNIQUE.

📊 Estatísticas:
Total de Registros:    150
PNCP IDs Únicos:      150
Duplicatas:             0
```

### **Verificar Duplicatas - Com Duplicatas:**
```
⚠️ DUPLICATAS ENCONTRADAS!
Encontramos 5 PNCP IDs com registros duplicados.
Total de registros que serão removidos: 8

📊 Estatísticas:
Total de Registros:    158
PNCP IDs Únicos:      150
Registros Duplicados:   8

[Tabela com detalhes de cada duplicata]
```

### **Limpar Duplicatas - Interface:**
```
⚠️ ATENÇÃO: LEIA COM ATENÇÃO ANTES DE CONTINUAR:
- Esta ação NÃO PODE SER DESFEITA!
- Será mantido apenas o registro mais RECENTE
- Os registros mais ANTIGOS serão DELETADOS

[Tabela mostrando o que será removido]

☑️ Eu entendo que esta ação irá deletar X registros...

[Botão: 🗑️ Limpar Duplicatas AGORA]
```

---

## 🆚 Diferença: Scripts CLI vs Web

| Aspecto | **CLI (Terminal)** | **WEB (Navegador)** |
|---------|-------------------|---------------------|
| **Acesso** | SSH necessário | Qualquer navegador |
| **Visual** | Texto simples | Interface colorida |
| **Facilidade** | Requer SSH | Mais fácil |
| **Segurança** | Mais seguro | Requer proteção |
| **Confirmação** | Via terminal | Via checkbox |

---

## 🔧 Arquivos Criados

```
backend/public/
├── admin_duplicatas.php           ← Menu principal
├── verificar_duplicatas_web.php   ← Verificar duplicatas
└── limpar_duplicatas_web.php      ← Limpar duplicatas (com senha)
```

---

## ⚠️ Avisos Importantes

1. **SEMPRE faça backup antes de limpar duplicatas**
2. **Mude a senha padrão** em `limpar_duplicatas_web.php`
3. **REMOVA os scripts** após uso
4. **Não compartilhe as URLs** com outras pessoas
5. **Execute em horário de baixo tráfego** (se possível)

---

## 🐛 Solução de Problemas

### **Erro: "Não foi possível conectar ao banco"**
- Verifique se o arquivo `.env` está configurado
- Verifique credenciais do banco de dados

### **Erro: "PDO não encontrado"**
- Verifique se extensão PDO MySQL está instalada
- Entre em contato com suporte da Hostinger

### **Senha não funciona**
- Verifique se digitou: `licita2025`
- Se mudou a senha, use a nova senha definida

### **Script não abre (mostra código PHP)**
- Verifique se arquivos estão na pasta `backend/public/`
- Verifique permissões dos arquivos (644)

---

## 📝 Logs e Auditoria

Os scripts web **não geram logs automaticamente**, mas você pode adicionar:

```php
// Adicionar no final de limpar_duplicatas_web.php após sucesso:
$log = date('[Y-m-d H:i:s]') . " Duplicatas limpas: {$removidos} registros\n";
file_put_contents('/home/u590097272/logs/duplicatas_limpeza.log', $log, FILE_APPEND);
```

---

## 🚀 Próximos Passos

Após limpar as duplicatas:

1. ✅ Verificar que não há mais duplicatas
2. ✅ Executar migration 004 (adicionar índice UNIQUE)
3. ✅ Fazer upload dos arquivos PHP atualizados (LicitacaoRepository e PNCPService)
4. ✅ Testar sincronização: `php backend/cron/sincronizar_pncp.php --ultimos-dias=1`
5. ✅ Remover scripts web

---

**Data:** 28/10/2025
**Versão:** 1.0
