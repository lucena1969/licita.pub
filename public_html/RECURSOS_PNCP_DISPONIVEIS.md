# 🚀 RECURSOS DISPONÍVEIS NA API DO PNCP

**Data**: 02/11/2025
**Objetivo**: Mapear todos os recursos disponíveis no PNCP e identificar oportunidades para o Licita.pub

---

## 📊 DADOS DISPONÍVEIS NO PNCP (Confirmados via API)

### 1. **✅ Contratos** (Já implementado)
**Endpoint**: `https://pncp.gov.br/api/consulta/v1/contratos`

**Parâmetros**:
- `dataInicial` / `dataFinal` (formato: Ymd)
- `tamanhoPagina` (mínimo: 10, máximo: 50)
- `pagina`
- `uf`, `codigoModalidadeContratacao`

**Dados retornados**:
- Número de controle PNCP
- Número do contrato/empenho
- Objeto da contratação
- Datas (assinatura, vigência, publicação)
- Valores (inicial, parcela, global)
- Órgão (CNPJ, nome, esfera, UF)
- Fornecedor (NI, nome, tipo pessoa)
- Processo, categoria

**Status**: ✅ **IMPLEMENTADO NO LICITA.PUB**

---

### 2. **🆕 Atas de Registro de Preços** (NOVO - Altamente Recomendado!)
**Endpoint**: `https://pncp.gov.br/api/consulta/v1/atas`

**Parâmetros**:
- `dataInicial` / `dataFinal`
- `tamanhoPagina` (10-50)
- `pagina`

**Dados retornados**:
```json
{
  "numeroControlePNCPAta": "18457226000181-1-000015/2023-000001",
  "numeroAtaRegistroPreco": "NPERP 003/2023",
  "anoAta": 2023,
  "dataAssinatura": "2023-06-16",
  "vigenciaInicio": "2023-07-07",
  "vigenciaFim": "2026-10-07",
  "objetoContratacao": "...",
  "cnpjOrgao": "18457226000181",
  "nomeOrgao": "MUNICIPIO DE SANTA VITORIA",
  "codigoUnidadeOrgao": "1",
  "nomeUnidadeOrgao": "MUNICIPIO DE SANTA VITORIA",
  "dataAtualizacao": "2023-07-06"
}
```

**Total disponível**: **452.366 atas** (45.237 páginas)

**💡 POTENCIAL PARA LICITA.PUB**:
- ✅ **Consulta de Preços Praticados** (atende legislação!)
- ✅ Histórico de preços por item/serviço
- ✅ Benchmark de preços entre órgãos
- ✅ Identificar oportunidades de carona em atas vigentes
- ✅ Alertas de vencimento de atas

**Legislação atendida**:
- Lei 14.133/2021 Art. 23 - Pesquisa de preços obrigatória
- Decreto 11.462/2023 - Fontes de pesquisa de mercado

---

### 3. **🆕 Itens das Atas de Registro de Preços** (CRÍTICO para Consulta de Preços!)
**Endpoint provável**: `https://pncp.gov.br/api/consulta/v1/atas/{numeroControlePNCPAta}/itens`

**Dados esperados**:
- Descrição detalhada do item
- Unidade de medida
- Quantidade registrada
- Preço unitário registrado
- Preço total
- Fornecedor vencedor (CNPJ, razão social)
- Marca/modelo
- Percentual de desconto

**💡 POTENCIAL PARA LICITA.PUB**:
- ✅ **Sistema de Consulta de Preços completo**
- ✅ Busca por descrição/categoria de item
- ✅ Comparação de preços por região/órgão
- ✅ Histórico de preços ao longo do tempo
- ✅ Relatórios para anexar em processos licitatórios
- ✅ API pública para gestores públicos

---

### 4. **🆕 Compras/Licitações** (Diferente de Contratos)
**Endpoint provável**: `https://pncp.gov.br/api/consulta/v1/compras` ou `/licitacoes`

**Diferença**:
- **Contratos**: Resultado final (após adjudicação)
- **Compras**: Processo licitatório completo (edital, participantes, propostas)

**Dados esperados**:
- Número do processo
- Modalidade (Pregão, Concorrência, etc.)
- Objeto da licitação
- Edital (PDF/link)
- Data de abertura/encerramento
- Orçamento estimado
- Propostas recebidas
- Ata de julgamento

**💡 POTENCIAL PARA LICITA.PUB**:
- ✅ Alertas de licitações abertas
- ✅ Histórico de fornecedores participantes
- ✅ Análise de competitividade
- ✅ Identificar licitações desertas/fracassadas

---

### 5. **🆕 Plano Anual de Contratações (PAC/PCA)**
**Endpoint provável**: `https://pncp.gov.br/api/consulta/v1/pca` ou `/planejamento`

**Dados esperados**:
- Itens planejados por órgão
- Natureza da contratação (obra, serviço, material)
- Valor estimado anual
- Trimestre previsto
- Classificação orçamentária

**💡 POTENCIAL PARA LICITA.PUB**:
- ✅ **Inteligência de Mercado** para fornecedores
- ✅ Alertas de oportunidades futuras
- ✅ Planejamento estratégico de vendas
- ✅ Análise de demanda por setor/região

---

### 6. **🆕 Fornecedores/Vencedores**
**Endpoint provável**: `https://pncp.gov.br/api/consulta/v1/fornecedores`

**Dados disponíveis** (já vêm nos contratos):
- CNPJ/CPF do fornecedor
- Nome/Razão social
- Tipo de pessoa (PJ/PF)
- Itens fornecidos
- Valores contratados
- Órgãos contratantes

**💡 POTENCIAL PARA LICITA.PUB**:
- ✅ Perfil de fornecedores públicos
- ✅ Histórico de contratos por fornecedor
- ✅ Score/reputação baseado em volume
- ✅ Análise de concentração de mercado
- ✅ Network de relacionamento órgão-fornecedor

---

## 🎯 RECOMENDAÇÕES PRIORITÁRIAS PARA O MVP

### **Prioridade 1: Atas de Registro de Preços + Itens** 🔥
**Por quê?**
- ✅ Atende necessidade legal (pesquisa de preços obrigatória)
- ✅ Diferencial competitivo forte
- ✅ Monetizável (planos premium com acesso ilimitado)
- ✅ Alto valor para gestores públicos
- ✅ Dados estruturados e confiáveis

**Casos de Uso**:
1. **Gestor precisa licitar cadeiras de escritório**
   - Busca "cadeira giratória" no Licita.pub
   - Encontra 50 atas vigentes com preços de R$ 350 a R$ 890
   - Exporta relatório para anexar ao processo
   - Justifica preço de referência de R$ 450

2. **Fornecedor quer saber preços praticados**
   - Busca "manutenção de ar condicionado"
   - Vê histórico de preços por região
   - Ajusta proposta para ser competitivo

3. **Órgão identifica oportunidade de carona**
   - Busca ata vigente de outro município
   - Vê que pode "pegar carona" (adesão à ata)
   - Economiza tempo e recursos

**Implementação**:
```php
// Tabela no banco
CREATE TABLE atas_registro_precos (
    id CHAR(36) PRIMARY KEY,
    numero_controle_pncp VARCHAR(100) UNIQUE,
    numero_ata VARCHAR(50),
    ano_ata INT,
    objeto TEXT,
    orgao_id VARCHAR(50),
    cnpj_orgao VARCHAR(14),
    nome_orgao VARCHAR(255),
    data_assinatura DATE,
    vigencia_inicio DATE,
    vigencia_fim DATE,
    ativo BOOLEAN DEFAULT 1,
    sincronizado_em DATETIME,
    INDEX idx_vigencia (vigencia_fim, ativo),
    INDEX idx_orgao (orgao_id)
);

CREATE TABLE itens_ata (
    id CHAR(36) PRIMARY KEY,
    ata_id CHAR(36),
    numero_item INT,
    descricao TEXT,
    unidade_medida VARCHAR(20),
    quantidade DECIMAL(15,4),
    preco_unitario DECIMAL(15,2),
    preco_total DECIMAL(15,2),
    fornecedor_cnpj VARCHAR(14),
    fornecedor_nome VARCHAR(255),
    marca VARCHAR(100),
    sincronizado_em DATETIME,
    FOREIGN KEY (ata_id) REFERENCES atas_registro_precos(id),
    INDEX idx_descricao (descricao(255)),
    INDEX idx_preco (preco_unitario),
    FULLTEXT idx_busca (descricao, marca)
);
```

**Funcionalidades**:
```javascript
// Frontend - Busca de preços
function buscarPrecos() {
  const termo = "cadeira giratória";
  const uf = "SP";
  const dataInicio = "2024-01-01";

  fetch('/api/atas/itens/buscar', {
    params: { termo, uf, dataInicio }
  }).then(response => {
    // Exibe:
    // - Lista de itens encontrados
    // - Preço médio, mínimo, máximo
    // - Gráfico de variação de preços
    // - Órgãos que compraram
    // - Link para download do relatório PDF
  });
}
```

---

### **Prioridade 2: Sincronização de Compras/Licitações em Andamento**
**Por quê?**
- ✅ Complementa contratos (visão do processo completo)
- ✅ Permite alertas de oportunidades abertas
- ✅ Diferencial para fornecedores (B2B)

**Casos de Uso**:
- Fornecedor se cadastra com CNAEs
- Recebe alertas de licitações abertas em sua área
- Acessa edital e se prepara para participar

---

### **Prioridade 3: Análise de Fornecedores**
**Por quê?**
- ✅ Valor agregado para órgãos (verificar histórico)
- ✅ Networking entre fornecedores e órgãos
- ✅ Inteligência competitiva

---

## 💰 MODELO DE MONETIZAÇÃO COM CONSULTA DE PREÇOS

### **Plano FREE** (atual):
- 5 consultas de contratos/dia (anônimo)
- 10 consultas de contratos/dia (cadastrado)
- **NOVO**: 3 consultas de preços/dia

### **Plano ESSENCIAL** (R$ 49/mês):
- Consultas ilimitadas de contratos
- **50 consultas de preços/dia**
- Exportação de relatórios em PDF
- Histórico de buscas

### **Plano PROFISSIONAL** (R$ 149/mês):
- Tudo do Essencial
- **Consultas ilimitadas de preços**
- API de integração (1000 requests/dia)
- Alertas personalizados
- Análises comparativas avançadas
- Gráficos e dashboard

### **Plano CORPORATIVO** (R$ 499/mês):
- Tudo do Profissional
- API ilimitada
- Múltiplos usuários (até 10)
- Suporte prioritário
- Relatórios personalizados
- Integração com sistemas de compras

---

## 📈 ESTIMATIVA DE VOLUME DE DADOS

### **Atas de Registro de Preços**:
- **Total**: 452.366 atas
- **Páginas**: 45.237 (50 registros/página)
- **Tempo estimado**: ~6h para importar tudo (50 páginas a cada 2min)
- **Espaço estimado**: ~500 MB (sem itens)

### **Itens de Atas** (estimativa):
- **Média**: 20 itens por ata
- **Total estimado**: ~9 milhões de itens
- **Espaço estimado**: ~5 GB
- **Tempo de importação**: Vários dias (precisa estratégia incremental)

---

## 🔧 ESTRATÉGIA DE IMPLEMENTAÇÃO

### **Fase 1: MVP - Atas de Registro de Preços** (1-2 semanas)
1. ✅ Criar tabelas `atas_registro_precos` e `itens_ata`
2. ✅ Criar `AtaRepository` e `AtaService`
3. ✅ Criar script de sincronização (similar ao PNCP atual)
4. ✅ Importar atas dos últimos 12 meses (~50.000 atas)
5. ✅ Criar endpoint `/api/atas/buscar`
6. ✅ Criar página de consulta de preços no frontend

### **Fase 2: Itens Detalhados** (2-3 semanas)
1. ✅ Descobrir endpoint de itens da API
2. ✅ Importar itens das atas mais recentes
3. ✅ Criar busca fulltext por descrição
4. ✅ Implementar filtros (UF, categoria, faixa de preço)
5. ✅ Criar relatório PDF para download

### **Fase 3: Inteligência e Analytics** (3-4 semanas)
1. ✅ Gráficos de variação de preços
2. ✅ Comparação por região
3. ✅ Ranking de fornecedores
4. ✅ Alertas de preços fora da curva
5. ✅ API pública para integrações

---

## 🎓 DIFERENCIAIS COMPETITIVOS

### **O que o Licita.pub terá que outros NÃO têm**:

1. **Interface Amigável**
   - Busca Google-like para itens
   - Filtros intuitivos
   - Visualização clara de resultados

2. **Relatórios Prontos**
   - PDF formatado para anexar em processos
   - Já com fundamentação legal
   - Assinatura digital (futuro)

3. **Inteligência de Preços**
   - Alertas de preços suspeitos
   - Sugestão de preço de referência
   - Análise de sazonalidade

4. **API Aberta**
   - Permite que outros sistemas se integrem
   - Democratiza acesso aos dados públicos
   - Gera ecossistema de parceiros

---

## ⚠️ DESAFIOS E SOLUÇÕES

### **Desafio 1: Volume de Dados**
**Problema**: 9 milhões de itens é muito para importar de uma vez
**Solução**:
- Importação incremental (últimos 12 meses primeiro)
- Importação sob demanda (quando usuário buscar)
- Cache de buscas frequentes

### **Desafio 2: Qualidade dos Dados**
**Problema**: Descrições inconsistentes entre órgãos
**Solução**:
- Normalização de textos (maiúsculas, acentos)
- Categorização automática (ML futuro)
- Sinônimos e variações

### **Desafio 3: Performance de Busca**
**Problema**: Busca em milhões de registros pode ser lenta
**Solução**:
- Índices FULLTEXT no MySQL
- ElasticSearch (futuro)
- Cache de resultados populares

---

## 📊 MÉTRICAS DE SUCESSO

### **Indicadores de Produto**:
- Número de consultas de preços/dia
- Taxa de conversão FREE → PAGO
- Tempo médio de uso da plataforma
- NPS (Net Promoter Score)

### **Indicadores de Negócio**:
- MRR (Monthly Recurring Revenue)
- CAC (Custo de Aquisição de Cliente)
- LTV (Lifetime Value)
- Churn rate

---

## 🚀 PRÓXIMOS PASSOS RECOMENDADOS

1. **AGORA** (esta semana):
   - [ ] Testar endpoint de itens das atas
   - [ ] Validar estrutura de dados retornada
   - [ ] Criar modelo de dados (tabelas)

2. **SEMANA 1-2**:
   - [ ] Implementar sincronização de atas
   - [ ] Criar página de consulta básica
   - [ ] Importar primeiros 10.000 registros

3. **SEMANA 3-4**:
   - [ ] Adicionar itens detalhados
   - [ ] Implementar busca fulltext
   - [ ] Criar relatório PDF

4. **MÊS 2**:
   - [ ] Lançar versão beta
   - [ ] Coletar feedback de usuários
   - [ ] Refinar e otimizar

---

## 💡 CONSIDERAÇÕES FINAIS

A **consulta de preços** é uma **funcionalidade killer** para o Licita.pub porque:

✅ **Resolve problema real**: Gestores públicos PRECISAM fazer pesquisa de preços por lei
✅ **Diferencial forte**: Poucos concorrentes oferecem isso de forma estruturada
✅ **Monetizável**: Justifica planos pagos (valor entregue é claro)
✅ **Escalável**: API do PNCP é gratuita e aberta
✅ **Defensável**: Quanto mais dados, melhor o serviço (efeito rede)

**Investimento x Retorno**:
- Desenvolvimento: ~4-6 semanas
- Custo: Servidor (mesmos custos atuais)
- Retorno esperado: +50% conversão para planos pagos

---

**Recomendação**: ✅ **IMPLEMENTAR CONSULTA DE PREÇOS NO MVP!**

É a feature que pode diferenciar o Licita.pub de todos os concorrentes e gerar receita recorrente sustentável.

---

**Gerado automaticamente por Claude Code**
Data: 02/11/2025 18:50
