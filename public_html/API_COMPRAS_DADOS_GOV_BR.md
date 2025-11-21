# 🎉 DESCOBERTA: API de Itens de Registro de Preços - compras.dados.gov.br

**Data**: 02/11/2025
**Status**: ✅ **FUNCIONANDO!**

---

## 🚀 GRANDE DESCOBERTA!

Encontramos uma **API alternativa ao PNCP** que **EXPÕE ITENS DE REGISTRO DE PREÇOS**!

### **API**: compras.dados.gov.br
**Mantida por**: Ministério da Economia (antigo ComprasNet/SIASG)
**Status**: ✅ Funcional (com instabilidades relatadas)

---

## ✅ ENDPOINT PRINCIPAL: Itens de Registro de Preço

### **URL**:
```
GET http://compras.dados.gov.br/licitacoes/id/registro_preco/{id}/itens.{formato}
```

### **Formatos Suportados**:
- `.json` - JSON (recomendado)
- `.xml` - XML
- `.csv` - CSV
- `.html` - HTML

### **Parâmetros**:

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `id` | String | ✅ Sim | ID do registro de preço (ex: `01000105000012018`) |
| `offset` | Integer | ❌ Não | Paginação - ignora N registros iniciais |
| `order` | String | ❌ Não | Ordenação: `asc` ou `desc` |
| `order_by` | String | ❌ Não | Campo para ordenar |

---

## 📊 ESTRUTURA DA RESPOSTA

### **Exemplo de Requisição**:
```bash
curl "https://compras.dados.gov.br/licitacoes/id/registro_preco/01000105000012018/itens.json"
```

### **Estrutura JSON**:
```json
{
  "_links": {
    "self": {
      "href": "http://compras.dados.gov.br/licitacoes/doc/registro_preco/01000105000012018/itens.json",
      "title": "Lista de itens da licitação por registro de preço"
    },
    "first": {
      "href": "/licitacoes/doc/registro_preco/01000105000012018/itens.json?offset=0",
      "title": "Primeira página"
    }
  },
  "_embedded": {
    "itensRegistroPreco": [
      {
        "uasg": 10001,
        "modalidade": 5,
        "numero_aviso": 12018,
        "numero_registro_preco": "01000105000012018",
        "numero_item_licitacao": 2,
        "codigo_item_material": 150830,
        "codigo_item_servico": 0,
        "descricao_detalhada": "",
        "marca": "RH ENTERPRISE LINUX",
        "cnpj_fornecedor": "",
        "classificacaoFornecedor": "1",
        "unidade": "SUBSCRIÇÃO",
        "quantidade_empenhada": 0,
        "quantidade_total": 12,
        "quantidade_a_empenhar": 12,
        "valor_unitario": null,
        "valor_total": null,
        "data_assinatura": "2018-03-08",
        "data_inicio_validade": null,
        "data_fim_validade": null,
        "beneficio": "Nao possui tratamento diferenciado para ME/EPP/COOPERATIVA",
        "_links": {
          "self": {
            "href": "/licitacoes/id/registro_preco/01000105000012018/itens/2",
            "title": "Item 2 da licitação por registro de preço 01000105000012018"
          },
          "fornecedores": {
            "href": "/licitacoes/id/registro_preco/01000105000012018/itens/2/fornecedores",
            "title": "Fornecedores do Item"
          },
          "material": {
            "href": "/materiais/id/material/150830",
            "title": "Material 150830"
          },
          "modalidade_licitacao": {
            "href": "/licitacoes/id/modalidade_licitacao/5",
            "title": "Modalidade de Licitação 5: PREGÃO"
          },
          "registro_preco": {
            "href": "/licitacoes/id/registro_preco/01000105000012018",
            "title": "Licitacao por registro de preço 01000105000012018"
          },
          "uasg": {
            "href": "/licitacoes/id/uasg/10001",
            "title": "UASG 10001: CAMARA DOS DEPUTADOS"
          }
        }
      }
    ]
  },
  "count": 1,
  "offset": 0
}
```

---

## 📋 CAMPOS RETORNADOS (20 campos)

### **Dados do Registro de Preço**:
- `numero_registro_preco` - ID do registro de preço
- `numero_aviso` - Número do aviso
- `uasg` - Código da UASG (Unidade Administrativa de Serviços Gerais)
- `modalidade` - Código da modalidade (5 = Pregão)
- `data_assinatura` - Data de assinatura
- `data_inicio_validade` - Início da validade
- `data_fim_validade` - Fim da validade

### **Dados do Item**:
- `numero_item_licitacao` - Número do item
- `codigo_item_material` - Código CATMAT (se material)
- `codigo_item_servico` - Código CATSER (se serviço)
- `descricao_detalhada` - Descrição do item
- `marca` - Marca do produto
- `unidade` - Unidade de medida

### **Dados de Quantidade**:
- `quantidade_total` - Quantidade total registrada
- `quantidade_empenhada` - Quantidade já empenhada
- `quantidade_a_empenhar` - Quantidade disponível

### **Dados de Valores**:
- `valor_unitario` - Valor unitário do item ⚠️ **Pode ser NULL**
- `valor_total` - Valor total do item ⚠️ **Pode ser NULL**

### **Dados do Fornecedor**:
- `cnpj_fornecedor` - CNPJ do fornecedor ⚠️ **Pode estar vazio**
- `classificacaoFornecedor` - Classificação (1 = Primeiro colocado)

### **Outros**:
- `beneficio` - Tratamento diferenciado ME/EPP

---

## 🔗 LINKS HATEOAS

Cada item retorna links para recursos relacionados:

- **fornecedores** - Lista completa de fornecedores do item
- **material** - Detalhes do material (CATMAT)
- **servico** - Detalhes do serviço (CATSER)
- **modalidade_licitacao** - Detalhes da modalidade
- **registro_preco** - Detalhes do registro de preço
- **uasg** - Detalhes da UASG

### **Exemplo - Buscar Fornecedores de um Item**:
```bash
curl "https://compras.dados.gov.br/licitacoes/id/registro_preco/01000105000012018/itens/2/fornecedores.json"
```

---

## ⚠️ LIMITAÇÕES IMPORTANTES

### 1. **Valores Podem Ser NULL**
```json
"valor_unitario": null,
"valor_total": null
```

**Problema**: Alguns itens não têm preço informado.

**Solução**: Buscar fornecedores via link `/fornecedores` que pode ter o valor.

### 2. **CNPJ Fornecedor Pode Estar Vazio**
```json
"cnpj_fornecedor": ""
```

**Solução**: Usar link `/fornecedores` para obter dados completos.

### 3. **API Antiga (Dados até ~2020)**

Segundo relatos, essa API contém dados do sistema antigo (SIASG/SISRP) que foi **substituído pelo PNCP**.

**Período de dados**: Aproximadamente até 2020
**Dados pós-2021**: Migrados para o PNCP

### 4. **Instabilidade Relatada**

Usuários relatam que a API `compras.dados.gov.br` apresenta **instabilidade constante**.

**Recomendação**: Implementar retry logic e cache.

### 5. **Como Obter o ID do Registro de Preço?**

O endpoint requer o `id` do registro de preço, mas **não há endpoint de busca/listagem de registros**.

**Possíveis soluções**:
- Buscar por licitações e verificar se têm registro de preço
- Usar dados do PNCP (que tem numeroControlePNCP) e tentar converter
- Download de base completa via dados abertos

---

## 🔄 INTEGRANDO COM PNCP

### **Estratégia Híbrida**:

#### **Dados Novos (2021+)**: PNCP
- Endpoint: `https://pncp.gov.br/api/consulta/v1/atas`
- Retorna: Cabeçalho das atas
- **Problema**: Não tem itens ❌

#### **Dados Antigos (até 2020)**: compras.dados.gov.br
- Endpoint: `http://compras.dados.gov.br/licitacoes/id/registro_preco/{id}/itens.json`
- Retorna: **Itens com preços!** ✅
- **Problema**: Não tem busca/listagem de registros ❌

### **Solução Combinada**:

1. **Importar atas do PNCP** (dados novos)
2. **Permitir cadastro manual de itens** (para atas do PNCP)
3. **Importar itens históricos** do compras.dados.gov.br (dados antigos)
4. **Unificar na mesma base de dados**

---

## 🛠️ PRÓXIMOS ENDPOINTS A TESTAR

### 1. **Listar Registros de Preço**:
```bash
# Tentar diferentes endpoints
curl "https://compras.dados.gov.br/licitacoes/v1/registro_preco.json?limit=10"
curl "https://compras.dados.gov.br/licitacoes/v1/pregoes.json?limit=10"
```

### 2. **Buscar por UASG**:
```bash
curl "https://compras.dados.gov.br/licitacoes/v1/uasg/10001/registro_preco.json"
```

### 3. **Buscar Fornecedores do Item**:
```bash
curl "https://compras.dados.gov.br/licitacoes/id/registro_preco/{id}/itens/{numeroItem}/fornecedores.json"
```

### 4. **Detalhes do Registro de Preço**:
```bash
curl "https://compras.dados.gov.br/licitacoes/id/registro_preco/{id}.json"
```

---

## 📚 DOCUMENTAÇÃO COMPLETA

### **Portal de Dados Abertos**:
https://compras.dados.gov.br/docs/home.html

### **Documentação Específica de Itens**:
https://compras.dados.gov.br/docs/licitacoes/v1/itens_registro_preco.html

### **Swagger/OpenAPI**:
https://compras.dados.gov.br/docs/swagger.html

---

## 💡 RECOMENDAÇÃO FINAL

### **Estratégia em 4 Camadas**:

#### **Camada 1: Dados Históricos (até 2020)**
- **Fonte**: compras.dados.gov.br
- **Método**: Importação em lote (se houver endpoint de listagem)
- **Vantagem**: Itens completos com preços ✅

#### **Camada 2: Atas Recentes (2021+)**
- **Fonte**: PNCP API
- **Método**: Sincronização diária
- **Limitação**: Apenas cabeçalho das atas ⚠️

#### **Camada 3: Cadastro Manual**
- **Fonte**: Usuários do sistema
- **Método**: Formulário web
- **Vantagem**: Preenche lacunas ✅

#### **Camada 4: Monitoramento PNCP**
- **Objetivo**: Aguardar liberação de endpoint de itens
- **Método**: Verificação mensal de novos endpoints
- **Quando disponível**: Migrar para importação automática

---

## 🎯 PRÓXIMOS PASSOS IMEDIATOS

1. ✅ **Testar endpoint de listagem** de registros de preço
2. ✅ **Testar endpoint de fornecedores** (para obter preços completos)
3. ✅ **Criar script de importação** para dados históricos
4. ✅ **Criar AtaService** híbrido (PNCP + compras.dados.gov.br)
5. ✅ **Implementar cache** (API instável)

---

## 🔧 EXEMPLO DE IMPLEMENTAÇÃO

### **PHP - Buscar Itens de um Registro de Preço**:

```php
<?php

class ComprasDadosGovService
{
    private string $baseUrl = 'https://compras.dados.gov.br';

    public function buscarItensRegistroPreco(string $registroPrecoId): array
    {
        $url = "{$this->baseUrl}/licitacoes/id/registro_preco/{$registroPrecoId}/itens.json";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Erro ao buscar itens: HTTP {$httpCode}");
        }

        $data = json_decode($response, true);

        if (!isset($data['_embedded']['itensRegistroPreco'])) {
            return [];
        }

        return $data['_embedded']['itensRegistroPreco'];
    }

    public function buscarFornecedoresItem(string $registroPrecoId, int $numeroItem): array
    {
        $url = "{$this->baseUrl}/licitacoes/id/registro_preco/{$registroPrecoId}/itens/{$numeroItem}/fornecedores.json";

        // ... mesmo código de requisição
    }
}
```

---

**Gerado automaticamente por Claude Code**
Data: 02/11/2025 22:15
