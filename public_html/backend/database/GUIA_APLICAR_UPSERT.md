# 🚀 GUIA: Aplicar UPSERT em Produção

**Data:** 28/10/2025
**Objetivo:** Implementar UPSERT na sincronização PNCP para evitar duplicatas e atualizar todos os campos
**Tempo estimado:** 15-20 minutos

---

## 📋 CHECKLIST PRÉ-EXECUÇÃO

Antes de começar, certifique-se de ter:

- [ ] Acesso SSH ao servidor Hostinger
- [ ] Acesso ao cPanel / phpMyAdmin
- [ ] Backup do banco de dados (recomendado)
- [ ] Arquivos atualizados no servidor

---

## 🔧 PASSO A PASSO

### **1. Fazer backup do banco de dados (RECOMENDADO)**

Via cPanel:
1. Acesse: https://hpanel.hostinger.com
2. Vá em: **Databases** → **phpMyAdmin**
3. Selecione o banco: `u590097272_licitapub`
4. Clique em **Export** → **Quick** → **Go**
5. Salve o arquivo `.sql` em local seguro

**OU** via SSH:
```bash
mysqldump -u u590097272_neto -p u590097272_licitapub > backup_antes_upsert_$(date +%Y%m%d_%H%M%S).sql
```

---

### **2. Conectar ao servidor via SSH**

```bash
ssh u590097272@licita.pub
cd /home/u590097272/domains/licita.pub/public_html
```

---

### **3. Verificar duplicatas existentes**

```bash
php backend/database/verificar_duplicatas.php
```

**Resultados possíveis:**

✅ **Se aparecer "NENHUMA DUPLICATA ENCONTRADA":**
- Pule para o **Passo 5** (executar migration)

⚠️ **Se aparecer "DUPLICATAS ENCONTRADAS":**
- Continue no **Passo 4** (limpar duplicatas)

---

### **4. Limpar duplicatas (se houver)**

**4.1. Primeiro, fazer dry-run (simular sem deletar):**
```bash
php backend/database/limpar_duplicatas.php --dry-run
```

Isso mostrará quais registros serão removidos SEM deletar nada.

**4.2. Se estiver tudo ok, executar a limpeza real:**
```bash
php backend/database/limpar_duplicatas.php
```

**ATENÇÃO:** O script pedirá confirmação. Digite `SIM` (maiúsculas) para confirmar.

**4.3. Verificar se limpeza funcionou:**
```bash
php backend/database/verificar_duplicatas.php
```

Deve aparecer: "✅ NENHUMA DUPLICATA ENCONTRADA"

---

### **5. Executar migration (adicionar índice UNIQUE)**

**5.1. Via SSH + MySQL:**
```bash
mysql -u u590097272_neto -p u590097272_licitapub < backend/database/migrations/004_adicionar_unique_pncp_id.sql
```

Quando solicitado, digite a senha do banco: `SenhaForte123!`

**OU 5.2. Via phpMyAdmin:**
1. Acesse phpMyAdmin
2. Selecione o banco `u590097272_licitapub`
3. Vá na aba **Import**
4. Escolha o arquivo: `backend/database/migrations/004_adicionar_unique_pncp_id.sql`
5. Clique em **Go**

**5.3. Verificar se índice foi criado:**
```bash
mysql -u u590097272_neto -p u590097272_licitapub -e "SHOW INDEXES FROM licitacoes WHERE Key_name = 'idx_pncp_id_unique';"
```

Deve aparecer uma linha com o índice `idx_pncp_id_unique`.

---

### **6. Atualizar arquivos PHP no servidor**

**6.1. Fazer upload dos arquivos atualizados:**

Via FTP/SFTP, envie estes arquivos:
- `backend/src/Repositories/LicitacaoRepository.php` (com método upsert)
- `backend/src/Services/PNCPService.php` (usando upsert)

**OU** via Git (se estiver usando):
```bash
cd /home/u590097272/domains/licita.pub/public_html
git pull origin main
```

**6.2. Verificar permissões:**
```bash
chmod 644 backend/src/Repositories/LicitacaoRepository.php
chmod 644 backend/src/Services/PNCPService.php
```

---

### **7. Testar sincronização com UPSERT**

**7.1. Executar sincronização manual (1 dia):**
```bash
php backend/cron/sincronizar_pncp.php --ultimos-dias=1
```

**7.2. Analisar o resultado:**

Você deve ver algo como:
```
Sincronizando página 1 de 1...
  ✓ Nova: ABC123
  ↻ Atualizada: XYZ789
  ...

📊 Estatísticas:
  • Novas licitações:        X
  • Licitações atualizadas:  Y
  • Erros:                   0
  • Puladas:                 0
```

**7.3. Executar NOVAMENTE (deve atualizar tudo):**
```bash
php backend/cron/sincronizar_pncp.php --ultimos-dias=1
```

Agora você deve ver:
```
📊 Estatísticas:
  • Novas licitações:        0
  • Licitações atualizadas:  X  ← TODOS atualizados
  • Erros:                   0
```

✅ **Se deu tudo certo:** Passou no teste!

---

### **8. Verificar se não há duplicatas**

```bash
mysql -u u590097272_neto -p u590097272_licitapub -e "
SELECT pncp_id, COUNT(*) as duplicatas
FROM licitacoes
GROUP BY pncp_id
HAVING COUNT(*) > 1;
"
```

**Resultado esperado:** `Empty set` (nenhuma duplicata)

---

### **9. Verificar cron job (se necessário)**

O cron já está configurado para rodar às 6h, mas vamos garantir que está ok:

```bash
crontab -l | grep sincronizar_pncp
```

Deve aparecer:
```
0 6 * * * /usr/bin/php /home/u590097272/domains/licita.pub/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1
```

Se NÃO aparecer, adicione manualmente via cPanel:
1. Acesse cPanel → **Cron Jobs**
2. Verifique se o cron existe
3. Se não existir, crie:
   - **Minuto:** 0
   - **Hora:** 6
   - **Dia:** *
   - **Mês:** *
   - **Dia da semana:** *
   - **Comando:** `/usr/bin/php /home/u590097272/domains/licita.pub/public_html/backend/cron/sincronizar_pncp.php >> /home/u590097272/logs/pncp_sync.log 2>&1`

---

### **10. Monitorar logs (próximas 24h)**

No dia seguinte (após cron rodar às 6h), verifique o log:

```bash
tail -n 100 /home/u590097272/logs/pncp_sync.log
```

Procure por:
- ✅ "Sincronização concluída com sucesso"
- ✅ Estatísticas de novos vs atualizados
- ❌ Erros (não deve ter)

---

## ✅ CHECKLIST PÓS-EXECUÇÃO

Após a implementação, confirme:

- [ ] Índice UNIQUE criado (`SHOW INDEXES FROM licitacoes`)
- [ ] Nenhuma duplicata no banco (`SELECT ... GROUP BY pncp_id HAVING COUNT(*) > 1`)
- [ ] Sincronização manual funcionando
- [ ] Segunda execução atualiza (não cria duplicatas)
- [ ] Arquivos PHP atualizados no servidor
- [ ] Cron job ativo
- [ ] Logs sem erros

---

## 🚨 ROLLBACK (se algo der errado)

Se houver algum problema:

**1. Restaurar índice:**
```sql
DROP INDEX idx_pncp_id_unique ON licitacoes;
```

**2. Restaurar código PHP:**
- Substitua os arquivos pelos backups anteriores

**3. Restaurar banco de dados (última opção):**
```bash
mysql -u u590097272_neto -p u590097272_licitapub < backup_antes_upsert_YYYYMMDD_HHMMSS.sql
```

---

## 📞 SUPORTE

Em caso de dúvidas ou problemas:
- Verifique os logs: `/home/u590097272/logs/pncp_sync.log`
- Verifique erros PHP: `/home/u590097272/logs/php_errors.log`
- Entre em contato com suporte Hostinger (se problema de infraestrutura)

---

## 📊 BENEFÍCIOS APÓS IMPLEMENTAÇÃO

Após o UPSERT estar ativo, você terá:

✅ **Sem duplicatas** - Cada `pncp_id` aparece apenas 1 vez
✅ **Dados sempre atualizados** - Se PNCP corrigir algo, seu banco atualiza
✅ **Sincronização mais rápida** - 1 query ao invés de 2-3
✅ **Banco mais limpo** - Sem registros órfãos ou desatualizados
✅ **Confiabilidade** - Índice UNIQUE garante integridade

---

**Última atualização:** 28/10/2025
**Versão:** 1.0
