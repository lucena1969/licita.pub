# 🔧 Diagnóstico do Erro 500

## 📍 Problema Atual

Você está recebendo **HTTP ERROR 500** ao tentar acessar os scripts web.

Erro 500 = **Erro Interno do Servidor** (problema no PHP, extensões ou configuração)

---

## 🔍 Passo a Passo para Diagnosticar

### **TESTE 1: Verificar se PHP básico funciona**

Acesse:
```
https://licita.pub/info.php
```

**Resultado esperado:** Página com todas as informações do PHP

**Se der erro 500 aqui também:**
- O problema é mais grave (configuração do servidor)
- Entre em contato com suporte da Hostinger

**Se funcionar:**
- PHP está ok
- Vá para o TESTE 2

---

### **TESTE 2: Verificar teste simples**

Acesse:
```
https://licita.pub/teste_simples.php
```

**Procure por:**
- ✅ PDO MySQL instalado?
- ✅ Arquivos existem (Config.php, Database.php, .env)?

**Se aparecer ❌ PDO MySQL: NÃO instalado:**
- **Este é o problema!**
- PDO MySQL precisa ser instalado
- Vá para **SOLUÇÃO 1** abaixo

**Se todos os arquivos mostrarem ❌ (não encontrados):**
- Problema de estrutura de pastas
- Vá para **SOLUÇÃO 2** abaixo

---

## 🛠️ SOLUÇÕES

### **SOLUÇÃO 1: Instalar PDO MySQL**

O PDO MySQL não está instalado no servidor. Existem 2 opções:

#### **Opção A: Via suporte Hostinger (RECOMENDADO)**
1. Abra ticket no suporte da Hostinger
2. Peça para instalar/habilitar extensão: **PDO MySQL (pdo_mysql)**
3. Informe que precisa para conectar ao banco MySQL via PHP
4. Aguarde resposta (geralmente rápido)

#### **Opção B: Via cPanel (se disponível)**
1. Acesse cPanel da Hostinger
2. Procure por "Select PHP Version" ou "MultiPHP Manager"
3. Selecione a versão do PHP em uso
4. Procure extensão: `pdo_mysql`
5. Marque como habilitada
6. Salvar

---

### **SOLUÇÃO 2: Verificar estrutura de pastas**

Se os arquivos não forem encontrados, verifique a estrutura:

#### **Via FTP/Arquivos:**
```
/home/u590097272/domains/licita.pub/public_html/
├── .htaccess
└── backend/
    ├── .env              ← DEVE EXISTIR
    ├── public/
    │   ├── index.php
    │   ├── info.php
    │   ├── teste_simples.php
    │   ├── test_conexao.php
    │   ├── admin_duplicatas.php
    │   ├── verificar_duplicatas_web.php
    │   └── limpar_duplicatas_web.php
    └── src/
        └── Config/
            ├── Config.php
            └── Database.php
```

**Verifique:**
1. Todos os arquivos existem?
2. Estão nos lugares corretos?
3. `.env` existe em `backend/`?

---

### **SOLUÇÃO 3: Ativar exibição de erros**

Para ver o erro exato, crie o arquivo:

**Arquivo:** `backend/public/debug.php`
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste de Debug</h1>";

echo "<h2>1. Caminhos</h2>";
echo "DIR: " . __DIR__ . "<br>";
echo "Base: " . dirname(__DIR__) . "<br>";

echo "<h2>2. Carregando Config</h2>";
try {
    require_once dirname(__DIR__) . '/src/Config/Config.php';
    echo "✅ Config.php carregado<br>";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Carregando Database</h2>";
try {
    require_once dirname(__DIR__) . '/src/Config/Database.php';
    echo "✅ Database.php carregado<br>";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Testando Conexão</h2>";
try {
    use App\Config\Config;
    use App\Config\Database;

    Config::load();
    echo "✅ Config carregado<br>";

    $db = Database::getConnection();
    echo "✅ Conexão estabelecida<br>";

    $stmt = $db->query("SELECT COUNT(*) as total FROM licitacoes");
    $result = $stmt->fetch();
    echo "✅ Query executada: " . $result['total'] . " licitações<br>";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
```

Acesse: `https://licita.pub/debug.php`

---

## 📞 Verificar Logs de Erro

Os logs podem conter o erro exato:

### **Via cPanel:**
1. Acesse cPanel
2. Vá em "Metrics" → "Errors"
3. Procure por erros recentes
4. Veja qual é o erro exato

### **Via SSH:**
```bash
tail -n 50 /home/u590097272/logs/error_log
```

---

## 🎯 Resumo dos URLs para Testar

**Em ordem de complexidade (do mais simples ao mais complexo):**

1. `https://licita.pub/info.php` - Mostra phpinfo
2. `https://licita.pub/teste_simples.php` - Teste básico com checagens
3. `https://licita.pub/debug.php` - Debug com erros visíveis
4. `https://licita.pub/test_conexao.php` - Teste completo com banco
5. `https://licita.pub/verificar_duplicatas_web.php` - Script de verificação

**Teste na ordem acima. Pare no primeiro que der erro e me mostre o resultado.**

---

## ⚠️ IMPORTANTE: Segurança

**Após resolver o problema, REMOVA estes arquivos:**
```bash
rm backend/public/info.php
rm backend/public/teste_simples.php
rm backend/public/debug.php
```

Eles expõem informações sensíveis do servidor!

---

## 💡 Causa Mais Provável

**90% de chance:** Extensão PDO MySQL não está instalada/habilitada

**Como resolver:**
1. Contate suporte Hostinger
2. Peça para habilitar `pdo_mysql`
3. Após habilitarem, teste novamente

---

## 📝 O Que Fazer Agora

1. **Acesse:** `https://licita.pub/info.php`
2. **Procure por:** "pdo_mysql" na página
3. **Se não encontrar:** Peça ao suporte para instalar
4. **Me avise o resultado**

---

**Data:** 28/10/2025
**Problema:** HTTP ERROR 500 nos scripts web
