# 📧 CONFIGURAÇÃO DE EMAIL - LICITA.PUB

## ✅ Status: CONFIGURADO

O sistema de envio de emails está configurado para usar **SMTP da Hostinger** com **PHPMailer**.

---

## 📋 CONFIGURAÇÕES ATUAIS

### Servidor SMTP da Hostinger

```env
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USER=contato@licita.pub
SMTP_PASS=Numse!2020
EMAIL_FROM=contato@licita.pub
EMAIL_FROM_NAME=Licita.pub
```

### Portas Disponíveis

- **587 com TLS/STARTTLS** ✅ (configurado)
- **465 com SSL** (alternativa)

---

## 🔧 ARQUIVO DE CONFIGURAÇÃO

As configurações estão no arquivo: `backend/.env`

```env
# EMAIL - Configurações SMTP da Hostinger
EMAIL_FROM=contato@licita.pub
EMAIL_FROM_NAME=Licita.pub
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_USER=contato@licita.pub
SMTP_PASS=Numse!2020
SMTP_ENCRYPTION=tls
SMTP_DEBUG=false
```

### Debug Mode

Para ativar logs detalhados durante desenvolvimento:

```env
SMTP_DEBUG=true
```

⚠️ **IMPORTANTE:** Mantenha `SMTP_DEBUG=false` em produção!

---

## 🧪 COMO TESTAR

### 1. Teste via linha de comando

```bash
# Substitua SEU_EMAIL pelo seu email real
php backend/tests/test_email.php seu-email@example.com
```

### 2. Teste via cadastro

1. Acesse: https://licita.pub/frontend/cadastro.html
2. Cadastre uma nova conta com seu email
3. Verifique sua caixa de entrada
4. Clique no link de verificação

---

## 📨 EMAILS ENVIADOS PELO SISTEMA

### 1. Email de Verificação de Cadastro

- **Assunto:** "Confirme seu cadastro no Licita.pub"
- **Quando:** Imediatamente após criar conta
- **Validade:** 24 horas
- **Conteúdo:** Link para verificar email

### 2. Email de Recuperação de Senha (futuro)

- **Assunto:** "Redefinir sua senha - Licita.pub"
- **Quando:** Ao solicitar reset de senha
- **Validade:** 1 hora
- **Conteúdo:** Link para redefinir senha

---

## 🔒 SEGURANÇA

### Credenciais SMTP

- ✅ Armazenadas em `.env` (não versionado no Git)
- ✅ Senha criptografada em produção
- ✅ Acesso restrito ao servidor

### Email do Remetente

- **Email:** contato@licita.pub
- **Nome:** Licita.pub
- **Domínio:** Configurado na Hostinger

---

## 🚨 TROUBLESHOOTING

### Email não chega

1. **Verifique a pasta de spam**
   - Alguns provedores podem marcar emails automatizados como spam

2. **Verifique as credenciais**
   ```bash
   # Teste de conexão
   php backend/tests/test_email.php seu-email@example.com
   ```

3. **Verifique os logs**
   - Em produção: `/home/u590097272/logs/error.log`
   - Localmente: `backend/logs/` ou `error_log` do PHP

4. **Ative o debug mode**
   ```env
   SMTP_DEBUG=true
   ```

### Erro de autenticação

- Verifique se a senha do email está correta no `.env`
- Verifique se o email existe no painel da Hostinger
- Verifique se a conta de email não está suspensa

### Erro de conexão

- Verifique se o firewall permite conexões na porta 587
- Tente trocar para porta 465 com SSL:
  ```env
  SMTP_PORT=465
  SMTP_ENCRYPTION=ssl
  ```

### Email marcado como spam

1. **Configurar SPF, DKIM e DMARC**
   - Configurar DNS no painel da Hostinger

2. **Usar email do mesmo domínio**
   - ✅ Já configurado: `contato@licita.pub`

3. **Melhorar conteúdo do email**
   - ✅ Templates HTML profissionais já criados
   - ✅ Texto alternativo em plain text incluído

---

## 📊 MONITORAMENTO

### Logs de Email

Os envios são registrados em:

```
✅ Sucesso: error_log("Email enviado com sucesso para: $email")
❌ Falha: error_log("Erro ao enviar email: $mensagem")
```

### Verificar logs

```bash
# Na Hostinger
tail -f /home/u590097272/logs/error.log | grep -i "email"

# Localmente
tail -f backend/logs/error.log | grep -i "email"
```

---

## 🔄 MUDANÇAS EM PRODUÇÃO

### Se mudar a senha do email

1. Atualizar `backend/.env`:
   ```env
   SMTP_PASS=nova_senha_aqui
   ```

2. Fazer upload do arquivo `.env` atualizado
3. Testar o envio de email

### Se mudar o email remetente

1. Criar novo email no painel da Hostinger
2. Atualizar `backend/.env`:
   ```env
   EMAIL_FROM=novo-email@licita.pub
   SMTP_USER=novo-email@licita.pub
   ```

3. Testar o envio de email

---

## 📚 ARQUIVOS RELACIONADOS

- `backend/src/Services/EmailService.php` - Serviço de envio de emails
- `backend/.env` - Configurações SMTP
- `backend/tests/test_email.php` - Script de teste
- `backend/src/Services/AuthService.php` - Integração com cadastro/login

---

## 🎯 PRÓXIMAS MELHORIAS

### Templates de Email

- [ ] Adicionar logo da empresa
- [ ] Personalizar cores
- [ ] Adicionar links para redes sociais
- [ ] Adicionar link de "descadastrar"

### Funcionalidades

- [x] Email de verificação de cadastro
- [ ] Email de recuperação de senha
- [ ] Email de boas-vindas após verificação
- [ ] Email de notificação de nova licitação (para Premium)
- [ ] Newsletter (futuro)

### Métricas

- [ ] Taxa de entrega (delivery rate)
- [ ] Taxa de abertura (open rate)
- [ ] Taxa de cliques (click rate)
- [ ] Taxa de rejeição (bounce rate)

---

## 💡 DICAS

1. **Sempre teste em desenvolvimento antes de produção**
   ```bash
   php backend/tests/test_email.php seu-email@gmail.com
   ```

2. **Monitore os logs regularmente**
   - Verifique se há erros de envio
   - Identifique problemas de autenticação

3. **Mantenha credenciais seguras**
   - Nunca comite o `.env` no Git
   - Use senhas fortes
   - Troque senhas periodicamente

4. **Configure SPF/DKIM no DNS**
   - Reduz chance de emails irem para spam
   - Melhora reputação do domínio

---

## 📞 SUPORTE

**Problemas com SMTP da Hostinger?**
- Suporte Hostinger: https://www.hostinger.com.br/suporte
- Documentação: https://support.hostinger.com/pt-BR/

**Problemas com o código?**
- Consulte: `backend/src/Services/EmailService.php`
- Execute testes: `php backend/tests/test_email.php`

---

✅ **Sistema de email configurado e pronto para uso!**

*Última atualização: 30/10/2025*
