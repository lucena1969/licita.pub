# 🔍 DESCOBERTAS - API PNCP para Atas de Registro de Preços

**Data**: 02/11/2025
**Status**: Teste de Endpoints Concluído

---

## ✅ O QUE FUNCIONA

### 1. **Endpoint de Consulta de Atas**

✅ **FUNCIONANDO**:
```
GET https://pncp.gov.br/api/consulta/v1/atas?dataInicial=AAAAMMDD&dataFinal=AAAAMMDD&pagina=1
```

**Parâmetros**:
- `dataInicial` (obrigatório): Data inicial no formato `AAAAMMDD` (ex: `20250101`)
- `dataFinal` (obrigatório): Data final no formato `AAAAMMDD` (ex: `20250131`)
- `pagina` (opcional): Número da página (default: 1)
- `cnpjOrgao` (opcional): Filtrar por CNPJ do órgão
- `idUsuario` (opcional): ID do usuário que publicou

**Exemplo de Requisição**:
```bash
curl "https://pncp.gov.br/api/consulta/v1/atas?dataInicial=20250101&dataFinal=20250131&pagina=1"
```

**Estrutura de Resposta**:
```json
{
  "data": [
    {
      "numeroControlePNCPAta": "01612781000138-1-000021/2022-000001",
      "numeroAtaRegistroPreco": "3",
      "anoAta": 2023,
      "numeroControlePNCPCompra": "01612781000138-1-000021/2022",
      "cancelado": false,
      "dataCancelamento": null,
      "dataAssinatura": "2023-01-12",
      "vigenciaInicio": "2023-01-12",
      "vigenciaFim": "2025-01-12",
      "dataPublicacaoPncp": "2023-01-12",
      "dataInclusao": "2023-01-12",
      "dataAtualizacao": "2024-01-08",
      "dataAtualizacaoGlobal": "2024-01-08",
      "usuario": "Governançabrasil Tecnologia e Gestão em Serviços",
      "objetoContratacao": "Despesa empenhada para prestacao de servico...",
      "cnpjOrgao": "01612781000138",
      "nomeOrgao": "MUNICIPIO DE SANTIAGO DO SUL",
      "cnpjOrgaoSubrogado": null,
      "nomeOrgaoSubrogado": null,
      "codigoUnidadeOrgao": "0000",
      "nomeUnidadeOrgao": "Prefeitura Municipal de Santiago do Sul",
      "codigoUnidadeOrgaoSubrogado": null,
      "nomeUnidadeOrgaoSubrogado": null
    }
  ]
}
```

**⚠️ Limitação Importante**:
- **Não há paginação automática** (sem `totalPages` ou `totalElements`)
- Continuar buscando até retornar array vazio

---

### 2. **Endpoint de Detalhes de Uma Ata**

✅ **FUNCIONANDO**:
```
GET https://pncp.gov.br/api/pncp/v1/orgaos/{cnpj}/compras/{ano}/{sequencial}/atas/{sequencialAta}
```

**Exemplo**:
```bash
curl "https://pncp.gov.br/api/pncp/v1/orgaos/01612781000138/compras/2022/21/atas/1"
```

**Retorna Dados Adicionais**:
```json
{
  "numeroAtaRegistroPreco": "3",
  "anoAta": 2023,
  "dataAssinatura": "2023-01-12",
  "dataVigenciaInicio": "2023-01-12",
  "dataVigenciaFim": "2025-01-12",
  "cancelado": false,
  "sequencialAta": 1,
  "numeroControlePNCP": "01612781000138-1-000021/2022-000001",
  "orgaoEntidade": {
    "cnpj": "01612781000138",
    "razaoSocial": "MUNICIPIO DE SANTIAGO DO SUL",
    "esferaId": "M",
    "poderId": "N"
  },
  "unidadeOrgao": {
    "codigoUnidade": "0000",
    "nomeUnidade": "Prefeitura Municipal de Santiago do Sul",
    "municipioNome": "Santiago do Sul",
    "codigoIbge": "4215695",
    "ufSigla": "SC",
    "ufNome": "Santa Catarina"
  },
  "modalidadeNome": "Pregão - Eletrônico",
  "objetoCompra": "Despesa empenhada para prestacao de servico...",
  "informacaoComplementarCompra": " ",
  "usuarioNome": "Governançabrasil Tecnologia e Gestão em Serviços",
  "numeroControlePncpCompra": "01612781000138-1-000021/2022"
}
```

**💡 Dados Extras**:
- UF (ufSigla)
- Município
- Modalidade
- Informações completas do órgão

---

## ❌ O QUE NÃO FUNCIONA

### **Endpoints de Itens das Atas - TODOS RETORNAM 404**

Testamos 8 variações diferentes de endpoints para buscar itens:

❌ `GET /api/pncp/v1/orgaos/{cnpj}/compras/{ano}/{sequencial}/atas/{sequencialAta}/itens`
❌ `GET /api/pncp/v1/orgaos/{cnpj}/compras/{ano}/{sequencial}/atas/{sequencialAta}/fornecedores`
❌ `GET /api/pncp/v1/orgaos/{cnpj}/compras/{ano}/{sequencial}/atas/{sequencialAta}/produtos`
❌ `GET /api/pncp/v1/orgaos/{cnpj}/compras/{ano}/{sequencial}/atas/{sequencialAta}/items`
❌ `GET /api/consulta/v1/atas/{numeroControlePNCP}/itens`
❌ `GET /api/consulta/v1/atas/{numeroControlePNCP}/fornecedores`
❌ `GET /api/consulta/v1/atas/itens?numeroControlePNCP={numeroControlePNCP}`
❌ `GET /api/consulta/v1/atas/{numeroControlePNCPEncoded}/itens`

**Conclusão**: A API do PNCP **NÃO EXPÕE PUBLICAMENTE** os itens das atas de registro de preços.

---

## 🔍 INVESTIGAÇÃO ADICIONAL

### **Por que os itens não estão disponíveis?**

Possíveis razões:

1. **Privacidade/Segurança**: Dados sensíveis de preços e fornecedores
2. **API em Desenvolvimento**: Recurso ainda não implementado
3. **Dados Abertos Separados**: Disponíveis apenas via download CSV/ZIP
4. **Restrição por Autenticação**: Requer login/token para acessar

### **O que diz a Documentação Oficial?**

Segundo o site oficial do PNCP:
> "Essas ferramentas são comumente chamadas de APIs e permitem encontrar diferentes tipos de informações, tais como itens de plano de contratação anual, contratos e atas de registro de preços."

**Porém**: A menção é às **ATAS** (cabeçalho), não aos **ITENS** das atas.

---

## 🎯 CONCLUSÃO E IMPACTO NO PROJETO

### ❌ **Má Notícia**:
Não podemos importar automaticamente os itens das atas via API pública do PNCP.

### 💡 **Alternativas Viáveis**:

#### **Alternativa 1: Dados Abertos (CSV/ZIP)**
- Portal de Dados Abertos: https://www.gov.br/pncp/pt-br/acesso-a-informacao/dados-abertos
- Baixar dumps periódicos
- Importar via script batch
- **Problema**: Não encontramos link de download específico para itens de atas

#### **Alternativa 2: Web Scraping da Interface Web**
- URL da ata: `https://pncp.gov.br/app/atas/{numeroControlePNCPAta}`
- Extrair dados da página HTML
- **Problema**: Violação de termos de uso, instável, lento
- **Não recomendado**

#### **Alternativa 3: Alimentação Manual pelos Usuários**
- Criar interface para usuários cadastrarem itens de atas
- Usuários consultam PNCP manualmente e inserem no sistema
- **Vantagem**: 100% legal e confiável
- **Desvantagem**: Trabalho manual

#### **Alternativa 4: Parcerias com Órgãos Públicos**
- Contatar órgãos diretamente para compartilhamento de dados
- Convênios para acesso a dados estruturados
- **Vantagem**: Dados de qualidade
- **Desvantagem**: Burocrático, demorado

#### **Alternativa 5: Aguardar Evolução da API do PNCP**
- Monitorar releases do PNCP
- Comunicado nº 01/2025 menciona "consulta incremental de atas" (já existe)
- Possível que itens sejam expostos em futuras versões
- **Vantagem**: Solução definitiva quando disponível
- **Desvantagem**: Incerto quando/se acontecerá

---

## 📋 RECOMENDAÇÃO PARA O PROJETO

### **Estratégia em 3 Fases**:

#### **Fase 1: MVP com Dados Limitados (Imediato)**
- Importar **apenas atas** (cabeçalho) via API
- Criar interface de cadastro manual de itens
- Permitir que usuários cadastrem seus próprios itens
- **Tempo**: 1-2 semanas

**Fluxo**:
```
1. Sistema importa atas do PNCP (via API)
2. Usuário seleciona uma ata
3. Usuário cadastra manualmente os itens (via formulário)
4. Sistema armazena e disponibiliza para consulta
```

**Vantagem**: Sistema funcionando rapidamente, usuários criam valor.

#### **Fase 2: Crowdsourcing e Gamificação (Médio Prazo)**
- Incentivar usuários a cadastrarem itens
- Ranking de contribuidores
- Badges e recompensas
- Validação cruzada (múltiplos usuários confirmam dados)
- **Tempo**: 2-4 semanas

**Vantagem**: Base de dados cresce organicamente.

#### **Fase 3: Integração Automática (Quando Disponível)**
- Monitorar API do PNCP
- Quando endpoint de itens for liberado, ativar sincronização
- Migrar dados manuais para validados
- **Tempo**: Indefinido (depende do PNCP)

---

## 🛠️ IMPLEMENTAÇÃO RECOMENDADA

### **Arquivos a Criar**:

#### 1. **AtaService.php** (Sincronização de Atas)
```php
class AtaService
{
    /**
     * Sincronizar atas do PNCP (apenas cabeçalho)
     */
    public function sincronizarAtas(string $dataInicial, string $dataFinal): array
    {
        // Buscar atas via API consulta
        $response = $this->pncpService->fazerRequisicao('/atas', [
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal
        ]);

        $atasSincronizadas = 0;

        foreach ($response['data'] as $ataData) {
            $ata = AtaRegistroPreco::fromPNCP($ataData);

            // Salvar no banco
            $this->ataRepository->upsert($ata);

            $atasSincronizadas++;
        }

        return ['total' => $atasSincronizadas];
    }
}
```

#### 2. **ItemAtaController.php** (Cadastro Manual de Itens)
```php
class ItemAtaController
{
    /**
     * POST /api/atas/{ataId}/itens
     * Usuário cadastra item manualmente
     */
    public function cadastrarItem(Request $request, string $ataId)
    {
        // Validar dados
        $validacao = $this->validarDadosItem($request->getBody());

        if (!empty($validacao)) {
            return $this->jsonResponse(['errors' => $validacao], 400);
        }

        // Criar item
        $item = ItemAta::fromArray([
            'ata_id' => $ataId,
            'numero_item' => $request->numero_item,
            'descricao' => $request->descricao,
            'unidade' => $request->unidade,
            'fornecedor_nome' => $request->fornecedor_nome,
            'fornecedor_cnpj' => $request->fornecedor_cnpj,
            'valor_unitario' => $request->valor_unitario,
            'quantidade_total' => $request->quantidade_total,
            'quantidade_disponivel' => $request->quantidade_disponivel ?? $request->quantidade_total
        ]);

        // Salvar
        $itemSalvo = $this->itemRepository->create($item);

        return $this->jsonResponse($itemSalvo->toArray(), 201);
    }
}
```

#### 3. **Frontend: Formulário de Cadastro**
```html
<form id="form-cadastrar-item">
    <h3>Cadastrar Item da Ata</h3>

    <label>Número do Item:</label>
    <input type="number" name="numero_item" required>

    <label>Descrição:</label>
    <textarea name="descricao" required></textarea>

    <label>Unidade:</label>
    <select name="unidade">
        <option value="UN">Unidade (UN)</option>
        <option value="KG">Quilograma (KG)</option>
        <option value="M">Metro (M)</option>
        <option value="L">Litro (L)</option>
    </select>

    <label>Fornecedor:</label>
    <input type="text" name="fornecedor_nome" required>

    <label>CNPJ Fornecedor:</label>
    <input type="text" name="fornecedor_cnpj" required>

    <label>Valor Unitário:</label>
    <input type="number" step="0.01" name="valor_unitario" required>

    <label>Quantidade Total:</label>
    <input type="number" step="0.001" name="quantidade_total" required>

    <button type="submit">Cadastrar Item</button>
</form>
```

---

## 📊 COMPARAÇÃO: Automático vs Manual

| Aspecto | Importação Automática | Cadastro Manual |
|---------|----------------------|-----------------|
| **Velocidade** | ⚡ Instantânea | 🐢 Lenta |
| **Custo** | 💰 Zero | 👥 Trabalho humano |
| **Precisão** | ✅ 100% (se API funcionar) | ⚠️ Variável (depende do usuário) |
| **Cobertura** | 📊 Todas as atas do PNCP | 📉 Apenas o que usuários cadastram |
| **Legalidade** | ✅ Totalmente legal | ✅ Totalmente legal |
| **Viabilidade Hoje** | ❌ Impossível (API não disponível) | ✅ Possível |

---

## 🎯 DECISÃO FINAL

**Recomendo implementar Fase 1 (MVP com cadastro manual)**:

### **Por quê?**:
1. ✅ Podemos lançar o módulo de consulta de preços AGORA
2. ✅ Usuários começam a gerar valor imediatamente
3. ✅ Base de dados cresce organicamente
4. ✅ Quando API do PNCP liberar itens, fazemos migração

### **Próximos Passos**:
1. Criar AtaService para importar atas (apenas cabeçalho)
2. Criar endpoints de cadastro manual de itens
3. Criar interface de cadastro no frontend
4. Implementar validações e segurança
5. (Futuro) Monitorar API do PNCP para endpoint de itens

---

## 📞 CONTATO COM PNCP

Se quiser confirmar sobre a disponibilidade futura do endpoint de itens:

**Central de Atendimento PNCP**:
- 📞 Telefone: 0800-978-9001
- 🌐 Portal: https://www.gov.br/pncp/pt-br

**Pergunta Sugerida**:
> "Olá, gostaria de saber se há previsão para disponibilizar via API pública os itens (produtos/serviços) das atas de registro de preços, incluindo descrição, fornecedor, valor unitário e quantidade. Atualmente a API só retorna o cabeçalho das atas."

---

## 📝 HISTÓRICO DE TESTES

**Data**: 02/11/2025 22:00-22:30
**Endpoints Testados**: 8
**Status HTTP**:
- 404 Not Found: 7 endpoints
- 400 Bad Request: 1 endpoint
- 200 OK: 0 endpoints ❌

**Conclusão**: Nenhum endpoint de itens funciona.

---

**Gerado automaticamente por Claude Code**
Data: 02/11/2025 22:30
