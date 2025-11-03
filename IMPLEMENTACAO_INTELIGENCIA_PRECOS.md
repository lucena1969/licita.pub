# 🛠️ IMPLEMENTAÇÃO TÉCNICA - INTELIGÊNCIA DE PREÇOS

**Projeto:** Pesquisa de Preços Governamentais para PMEs
**Stack:** PHP 8.1+, MySQL 8.0, JavaScript ES6+
**Status:** 🟡 Em Planejamento

---

## 📋 ÍNDICE

1. [Arquitetura Geral](#arquitetura-geral)
2. [Banco de Dados](#banco-de-dados)
3. [Backend - API](#backend-api)
4. [Frontend - Interface](#frontend-interface)
5. [Sincronização PNCP](#sincronização-pncp)
6. [Algoritmos de Análise](#algoritmos-de-análise)
7. [Sistema de Alertas](#sistema-de-alertas)
8. [Performance e Otimização](#performance-e-otimização)

---

## 🏗️ ARQUITETURA GERAL

```
┌─────────────────────────────────────────────────────────────┐
│                        FRONTEND                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Pesquisa   │  │  Comparador  │  │   Dashboard  │      │
│  │   de Preços  │  │  de Produtos │  │ Oportunidades│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                       API REST                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │     ARPs     │  │    Itens     │  │   Análise    │      │
│  │  Controller  │  │  Controller  │  │  Controller  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│          │                  │                  │             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │     ARP      │  │    Item      │  │   Analytics  │      │
│  │  Repository  │  │  Repository  │  │   Service    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      DATABASE MySQL                          │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ atas_registro    │  │   itens_ata      │                │
│  │     _preco       │  │  (★ CORE ★)      │                │
│  └──────────────────┘  └──────────────────┘                │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │  adesoes_ata     │  │  alertas_preco   │                │
│  └──────────────────┘  └──────────────────┘                │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   PNCP API (Fonte de Dados)                  │
│              https://pncp.gov.br/api/consulta/v1/atas       │
└─────────────────────────────────────────────────────────────┘
```

---

## 💾 BANCO DE DADOS

### Tabelas Existentes (Migration 003)

#### `atas_registro_preco` - ARPs do PNCP

```sql
CREATE TABLE `atas_registro_preco` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `pncp_id` VARCHAR(100) NOT NULL COMMENT 'ID único da ARP no PNCP',
  `licitacao_id` CHAR(36) DEFAULT NULL COMMENT 'ID da licitação origem',
  `numero` VARCHAR(50) NOT NULL COMMENT 'Número da ARP',
  `objeto` TEXT NOT NULL COMMENT 'Descrição do objeto da ARP',

  -- Órgão gerenciador
  `orgao_gerenciador_id` VARCHAR(50) NOT NULL,
  `orgao_gerenciador_nome` VARCHAR(255) NOT NULL,
  `orgao_gerenciador_cnpj` VARCHAR(18) NOT NULL,

  -- Datas e vigência
  `data_assinatura` DATE NOT NULL,
  `data_vigencia_inicio` DATE NOT NULL,
  `data_vigencia_fim` DATE NOT NULL,

  -- Status
  `situacao` VARCHAR(30) NOT NULL, -- ATIVA, ENCERRADA, SUSPENSA, CANCELADA
  `permite_adesao` TINYINT(1) NOT NULL DEFAULT 1,

  -- Localização
  `uf` CHAR(2) NOT NULL,
  `municipio` VARCHAR(100) DEFAULT NULL,

  -- URLs
  `url_ata` TEXT DEFAULT NULL,
  `url_pncp` TEXT NOT NULL,

  -- Metadados
  `sincronizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pncp_id` (`pncp_id`),
  KEY `idx_situacao` (`situacao`),
  KEY `idx_vigencia` (`data_vigencia_fim`),
  KEY `idx_uf` (`uf`),
  FULLTEXT KEY `idx_objeto` (`objeto`),
  FULLTEXT KEY `idx_orgao_nome` (`orgao_gerenciador_nome`)
) ENGINE=InnoDB;
```

---

#### `itens_ata` - Itens com Preços (★ CORE DO NEGÓCIO)

```sql
CREATE TABLE `itens_ata` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `ata_id` CHAR(36) NOT NULL COMMENT 'ID da ARP',
  `numero_item` INT NOT NULL COMMENT 'Número sequencial do item',

  -- Descrição do produto/serviço
  `descricao` TEXT NOT NULL COMMENT 'Descrição completa do item',
  `unidade` VARCHAR(20) NOT NULL COMMENT 'Unidade de medida (UN, KG, M2, etc)',

  -- Fornecedor (★ INFORMAÇÃO VALIOSA)
  `fornecedor_nome` VARCHAR(255) NOT NULL,
  `fornecedor_cnpj` VARCHAR(18) NOT NULL,

  -- Preços e quantidades (★ CORE)
  `valor_unitario` DECIMAL(15,2) NOT NULL COMMENT 'Preço unitário registrado',
  `quantidade_total` DECIMAL(15,3) NOT NULL,
  `quantidade_disponivel` DECIMAL(15,3) NOT NULL COMMENT 'Disponível para adesão',
  `valor_total` DECIMAL(15,2) GENERATED ALWAYS AS (`valor_unitario` * `quantidade_total`) STORED,

  -- Metadados
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ata_id` (`ata_id`),
  KEY `idx_fornecedor_cnpj` (`fornecedor_cnpj`),
  KEY `idx_valor_unitario` (`valor_unitario`), -- Para ordenação por preço
  FULLTEXT KEY `idx_descricao` (`descricao`), -- Para busca
  FULLTEXT KEY `idx_fornecedor_nome` (`fornecedor_nome`),

  CONSTRAINT `fk_itens_ata`
    FOREIGN KEY (`ata_id`) REFERENCES `atas_registro_preco` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
```

---

### Novas Tabelas a Criar (Migration 005)

#### `alertas_preco` - Alertas de Preços para Usuários

```sql
CREATE TABLE `alertas_preco` (
  `id` CHAR(36) NOT NULL,
  `usuario_id` CHAR(36) NOT NULL,

  -- Configuração do alerta
  `tipo` ENUM('NOVO_PRODUTO', 'MUDANCA_PRECO', 'NOVA_ARP', 'ARP_EXPIRANDO') NOT NULL,
  `palavra_chave` VARCHAR(255) NOT NULL COMMENT 'Produto a monitorar',

  -- Filtros opcionais
  `uf` CHAR(2) DEFAULT NULL,
  `preco_maximo` DECIMAL(15,2) DEFAULT NULL COMMENT 'Alertar se preço <= este valor',
  `preco_minimo` DECIMAL(15,2) DEFAULT NULL,

  -- Configurações
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `frequencia` ENUM('IMEDIATO', 'DIARIO', 'SEMANAL') NOT NULL DEFAULT 'DIARIO',
  `ultimo_envio` DATETIME DEFAULT NULL,

  -- Metadados
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_tipo` (`tipo`),
  FULLTEXT KEY `idx_palavra_chave` (`palavra_chave`),

  CONSTRAINT `fk_alertas_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
```

---

#### `historico_precos` - Histórico de Preços para Análise

```sql
CREATE TABLE `historico_precos` (
  `id` CHAR(36) NOT NULL,
  `item_ata_id` CHAR(36) NOT NULL,

  -- Snapshot do preço
  `valor_unitario` DECIMAL(15,2) NOT NULL,
  `data_registro` DATE NOT NULL,

  -- Contexto
  `ata_id` CHAR(36) NOT NULL,
  `uf` CHAR(2) NOT NULL,
  `fornecedor_cnpj` VARCHAR(18) NOT NULL,

  -- Para agregações
  `descricao_normalizada` VARCHAR(255) NOT NULL COMMENT 'Descrição simplificada',

  PRIMARY KEY (`id`),
  KEY `idx_descricao` (`descricao_normalizada`),
  KEY `idx_data` (`data_registro`),
  KEY `idx_uf` (`uf`),
  KEY `idx_item_ata` (`item_ata_id`),

  CONSTRAINT `fk_historico_item`
    FOREIGN KEY (`item_ata_id`) REFERENCES `itens_ata` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
```

---

#### `produtos_agregados` - Cache de Produtos Agregados

```sql
CREATE TABLE `produtos_agregados` (
  `id` CHAR(36) NOT NULL,

  -- Identificação do produto
  `descricao_normalizada` VARCHAR(255) NOT NULL COMMENT 'Ex: mouse optico usb',
  `categoria` VARCHAR(100) DEFAULT NULL,

  -- Estatísticas de preço
  `preco_minimo` DECIMAL(15,2) NOT NULL,
  `preco_maximo` DECIMAL(15,2) NOT NULL,
  `preco_medio` DECIMAL(15,2) NOT NULL,
  `preco_mediana` DECIMAL(15,2) NOT NULL,
  `desvio_padrao` DECIMAL(15,2) DEFAULT NULL,

  -- Estatísticas de mercado
  `total_arps` INT NOT NULL COMMENT 'Quantas ARPs têm este produto',
  `total_fornecedores` INT NOT NULL,
  `total_ufs` INT NOT NULL,

  -- Principais fornecedores (JSON)
  `top_fornecedores` JSON DEFAULT NULL,

  -- Metadados
  `ultima_atualizacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_itens` INT NOT NULL COMMENT 'Total de itens agregados',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_descricao` (`descricao_normalizada`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_preco_medio` (`preco_medio`)
) ENGINE=InnoDB;
```

---

## 🔧 BACKEND - API

### Estrutura de Arquivos

```
backend/src/
├── Controllers/
│   ├── AtaController.php              ← Listar ARPs
│   ├── ItemAtaController.php          ← Pesquisar itens/preços ★
│   ├── AlertaController.php           ← Gerenciar alertas
│   └── AnalyticsController.php        ← Dashboard e estatísticas
│
├── Repositories/
│   ├── AtaRepository.php
│   ├── ItemAtaRepository.php          ★
│   ├── AlertaRepository.php
│   └── ProdutoAgregadoRepository.php
│
├── Services/
│   ├── PNCPService.php                ← Sincronizar ARPs do PNCP
│   ├── AnalisePrecosService.php      ← Algoritmos de análise ★
│   ├── AlertaService.php              ← Enviar alertas
│   └── NormalizacaoService.php        ← Normalizar descrições
│
└── Models/
    ├── AtaRegistroPreco.php
    ├── ItemAta.php                    ★
    ├── AlertaPreco.php
    └── ProdutoAgregado.php

backend/public/api/
├── atas/
│   ├── listar.php                    ← GET /api/atas/listar
│   ├── buscar.php                    ← GET /api/atas/buscar
│   └── detalhes.php                  ← GET /api/atas/detalhes?id=xxx
│
├── precos/
│   ├── pesquisar.php                 ← GET /api/precos/pesquisar?q=xxx ★
│   ├── comparar.php                  ← GET /api/precos/comparar?ids=1,2,3
│   ├── historico.php                 ← GET /api/precos/historico?produto=xxx
│   └── estatisticas.php              ← GET /api/precos/estatisticas?produto=xxx
│
├── alertas/
│   ├── criar.php                     ← POST /api/alertas/criar
│   ├── listar.php                    ← GET /api/alertas/listar
│   ├── atualizar.php                 ← PUT /api/alertas/atualizar
│   └── deletar.php                   ← DELETE /api/alertas/deletar
│
└── analytics/
    ├── oportunidades.php              ← GET /api/analytics/oportunidades
    ├── top-produtos.php               ← GET /api/analytics/top-produtos
    └── fornecedores.php               ← GET /api/analytics/fornecedores
```

---

### API Endpoints Principais

#### 1. Pesquisar Preços (★ CORE)

```
GET /api/precos/pesquisar?q=mouse+optico&uf=SP&preco_max=50&limite=20&pagina=1
```

**Parâmetros:**
- `q` (obrigatório): Palavra-chave do produto
- `uf`: Filtrar por estado
- `preco_min`: Preço mínimo
- `preco_max`: Preço máximo
- `vigente`: Apenas ARPs ativas (default: true)
- `permite_adesao`: Apenas ARPs que permitem carona
- `ordenar`: preço_asc, preço_desc, data_asc, data_desc
- `limite`: Itens por página (max: 50)
- `pagina`: Número da página

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "item_id": "abc123",
      "descricao": "Mouse Óptico USB 1000 DPI Preto",
      "unidade": "UNIDADE",
      "preco_unitario": 15.50,
      "quantidade_disponivel": 5000,
      "fornecedor": {
        "nome": "EMPRESA XYZ LTDA",
        "cnpj": "12.345.678/0001-90"
      },
      "ata": {
        "id": "def456",
        "numero": "ARP-001/2025",
        "orgao": "Prefeitura de São Paulo",
        "uf": "SP",
        "vigencia_fim": "2025-12-31",
        "permite_adesao": true,
        "url_pncp": "https://..."
      },
      "estatisticas": {
        "preco_medio_mercado": 18.20,
        "economia_potencial": "15%",
        "posicao_ranking": "2º menor preço"
      }
    }
  ],
  "agregacao": {
    "total_resultados": 45,
    "preco_medio": 18.50,
    "preco_minimo": 12.00,
    "preco_maximo": 28.00,
    "total_fornecedores": 12,
    "total_ufs": 8
  },
  "paginacao": {
    "pagina": 1,
    "limite": 20,
    "total": 45,
    "total_paginas": 3
  }
}
```

---

#### 2. Comparar Produtos

```
GET /api/precos/comparar?ids=item1,item2,item3
```

**Resposta:**
```json
{
  "success": true,
  "comparacao": {
    "campo": "preco_unitario",
    "ordem": "asc",
    "items": [
      {
        "item_id": "item1",
        "descricao": "Mouse Óptico USB - Marca A",
        "preco_unitario": 12.00,
        "fornecedor": "Empresa A",
        "uf": "SP",
        "vigencia_fim": "2025-12-31"
      },
      {
        "item_id": "item2",
        "descricao": "Mouse Óptico USB - Marca B",
        "preco_unitario": 15.50,
        "fornecedor": "Empresa B",
        "uf": "RJ",
        "vigencia_fim": "2026-01-15"
      }
    ],
    "analise": {
      "diferenca_percentual": "29%",
      "economia_potencial": "R$ 3.50 por unidade",
      "recomendacao": "Item 1 é 29% mais barato"
    }
  }
}
```

---

#### 3. Histórico de Preços

```
GET /api/precos/historico?produto=mouse+optico&periodo=12
```

**Resposta:**
```json
{
  "success": true,
  "produto": "mouse optico",
  "periodo": "12 meses",
  "historico": [
    {
      "mes": "2025-01",
      "preco_medio": 18.50,
      "preco_minimo": 12.00,
      "preco_maximo": 28.00,
      "total_registros": 45
    },
    {
      "mes": "2025-02",
      "preco_medio": 17.80,
      "preco_minimo": 11.50,
      "preco_maximo": 26.00,
      "total_registros": 52
    }
  ],
  "tendencia": {
    "variacao_percentual": "-3.8%",
    "direcao": "queda",
    "previsao_proximo_mes": 17.20
  }
}
```

---

#### 4. Dashboard de Oportunidades

```
GET /api/analytics/oportunidades?usuario_id=xxx
```

**Resposta:**
```json
{
  "success": true,
  "oportunidades": {
    "alta_demanda": [
      {
        "produto": "notebook dell",
        "total_arps": 120,
        "preco_medio": 2800.00,
        "total_fornecedores": 15,
        "oportunidade_score": 85
      }
    ],
    "poucos_fornecedores": [
      {
        "produto": "switch gerenciavel 24 portas",
        "total_arps": 45,
        "preco_medio": 1200.00,
        "total_fornecedores": 3,
        "oportunidade_score": 92
      }
    ],
    "maior_margem": [
      {
        "produto": "papel a4",
        "preco_governo": 28.00,
        "preco_mercado_estimado": 18.00,
        "margem_estimada": "55%",
        "oportunidade_score": 78
      }
    ]
  }
}
```

---

## 🔄 SINCRONIZAÇÃO PNCP

### Endpoint PNCP de ARPs

```
https://pncp.gov.br/api/consulta/v1/atas?
  dataInicial=20250101&
  dataFinal=20250131&
  uf=SP&
  pagina=1&
  tamanhoPagina=50
```

### Service de Sincronização

```php
<?php

namespace App\Services;

use App\Repositories\AtaRepository;
use App\Repositories\ItemAtaRepository;

class PNCPService
{
    const BASE_URL = 'https://pncp.gov.br/api/consulta/v1';

    public function sincronizarARPs(array $params = []): array
    {
        $dataInicial = $params['dataInicial'] ?? date('Ymd', strtotime('-7 days'));
        $dataFinal = $params['dataFinal'] ?? date('Ymd');
        $uf = $params['uf'] ?? null;

        $stats = [
            'arps_novas' => 0,
            'arps_atualizadas' => 0,
            'itens_novos' => 0,
            'itens_atualizados' => 0,
            'erros' => 0
        ];

        $pagina = 1;
        $maxPaginas = 100;

        while ($pagina <= $maxPaginas) {
            // Buscar ARPs do PNCP
            $url = self::BASE_URL . "/atas?" . http_build_query([
                'dataInicial' => $dataInicial,
                'dataFinal' => $dataFinal,
                'uf' => $uf,
                'pagina' => $pagina,
                'tamanhoPagina' => 50
            ]);

            $response = $this->fetchPNCP($url);

            if (!$response || empty($response['data'])) {
                break;
            }

            // Processar cada ARP
            foreach ($response['data'] as $ataData) {
                try {
                    // Salvar ARP
                    $ata = $this->salvarARP($ataData);

                    // Buscar itens da ARP
                    $itens = $this->buscarItensARP($ataData['id']);

                    // Salvar itens
                    foreach ($itens as $itemData) {
                        $this->salvarItemARP($ata->id, $itemData);
                        $stats['itens_novos']++;
                    }

                    $stats['arps_novas']++;

                } catch (\Exception $e) {
                    error_log("Erro ao processar ARP: " . $e->getMessage());
                    $stats['erros']++;
                }
            }

            $pagina++;
            sleep(1); // Rate limiting
        }

        // Atualizar produtos agregados
        $this->atualizarProdutosAgregados();

        return $stats;
    }

    private function buscarItensARP(string $ataId): array
    {
        $url = self::BASE_URL . "/atas/{$ataId}/itens";
        $response = $this->fetchPNCP($url);

        return $response['data'] ?? [];
    }

    private function salvarItemARP(string $ataId, array $itemData): void
    {
        $itemRepo = new ItemAtaRepository();

        $item = [
            'ata_id' => $ataId,
            'numero_item' => $itemData['numeroItem'],
            'descricao' => $itemData['descricao'],
            'unidade' => $itemData['unidadeMedida'],
            'fornecedor_nome' => $itemData['fornecedor']['nome'],
            'fornecedor_cnpj' => $itemData['fornecedor']['cnpj'],
            'valor_unitario' => $itemData['valorUnitario'],
            'quantidade_total' => $itemData['quantidadeTotal'],
            'quantidade_disponivel' => $itemData['quantidadeDisponivel'] ?? $itemData['quantidadeTotal']
        ];

        $itemRepo->upsert($item);
    }
}
```

---

## 🧮 ALGORITMOS DE ANÁLISE

### 1. Normalização de Descrições

**Problema:** Descrições variam muito entre órgãos
- "Mouse óptico USB 1000 DPI preto"
- "MOUSE OPTICO C/ CABO USB PRETO 1000DPI"
- "Mouse USB Óptico - Preto (1000 dpi)"

**Solução: Algoritmo de normalização**

```php
<?php

namespace App\Services;

class NormalizacaoService
{
    private static $stopwords = ['de', 'com', 'para', 'e', 'o', 'a', 'com', 'c/'];
    private static $sinonimos = [
        'mouse' => ['rato', 'apontador'],
        'optico' => ['óptico'],
        'notebook' => ['laptop', 'computador portatil']
    ];

    public static function normalizarDescricao(string $descricao): string
    {
        // 1. Lowercase
        $norm = mb_strtolower($descricao, 'UTF-8');

        // 2. Remover acentos
        $norm = self::removerAcentos($norm);

        // 3. Remover pontuação
        $norm = preg_replace('/[^a-z0-9\s]/', ' ', $norm);

        // 4. Remover stopwords
        $palavras = explode(' ', $norm);
        $palavras = array_filter($palavras, function($palavra) {
            return !in_array($palavra, self::$stopwords) && strlen($palavra) > 2;
        });

        // 5. Aplicar sinônimos
        $palavras = array_map(function($palavra) {
            foreach (self::$sinonimos as $canonical => $sinonimos) {
                if (in_array($palavra, $sinonimos)) {
                    return $canonical;
                }
            }
            return $palavra;
        }, $palavras);

        // 6. Ordenar alfabeticamente (para agrupar variações)
        sort($palavras);

        // 7. Juntar
        return implode(' ', $palavras);
    }

    // Resultado: "1000 dpi mouse optico preto usb"
}
```

---

### 2. Cálculo de Oportunidade Score

```php
<?php

class AnalisePrecosService
{
    /**
     * Calcula score de oportunidade (0-100)
     *
     * Fatores:
     * - Alta demanda (muitas ARPs) = +30 pontos
     * - Poucos fornecedores = +25 pontos
     * - Alta margem potencial = +25 pontos
     * - ARPs próximas de expirar (novas licitações) = +20 pontos
     */
    public function calcularOportunidadeScore(array $produto): int
    {
        $score = 0;

        // Fator 1: Demanda (0-30 pontos)
        $totalArps = $produto['total_arps'];
        if ($totalArps >= 100) $score += 30;
        elseif ($totalArps >= 50) $score += 20;
        elseif ($totalArps >= 20) $score += 10;
        elseif ($totalArps >= 5) $score += 5;

        // Fator 2: Concorrência (0-25 pontos)
        $totalFornecedores = $produto['total_fornecedores'];
        if ($totalFornecedores <= 3) $score += 25;
        elseif ($totalFornecedores <= 5) $score += 20;
        elseif ($totalFornecedores <= 10) $score += 10;
        elseif ($totalFornecedores <= 20) $score += 5;

        // Fator 3: Margem (0-25 pontos)
        if (isset($produto['margem_estimada'])) {
            $margem = $produto['margem_estimada'];
            if ($margem >= 50) $score += 25;
            elseif ($margem >= 30) $score += 20;
            elseif ($margem >= 20) $score += 10;
            elseif ($margem >= 10) $score += 5;
        }

        // Fator 4: Renovação (0-20 pontos)
        if (isset($produto['arps_expirando_60dias'])) {
            $expirando = $produto['arps_expirando_60dias'];
            if ($expirando >= 10) $score += 20;
            elseif ($expirando >= 5) $score += 15;
            elseif ($expirando >= 2) $score += 10;
        }

        return min(100, $score);
    }
}
```

---

### 3. Agregação de Produtos

```sql
-- Query para agregar produtos similares
INSERT INTO produtos_agregados (
    id,
    descricao_normalizada,
    preco_minimo,
    preco_maximo,
    preco_medio,
    preco_mediana,
    desvio_padrao,
    total_arps,
    total_fornecedores,
    total_ufs,
    top_fornecedores,
    total_itens
)
SELECT
    UUID() as id,
    descricao_normalizada,
    MIN(valor_unitario) as preco_minimo,
    MAX(valor_unitario) as preco_maximo,
    AVG(valor_unitario) as preco_medio,
    -- Mediana (aproximação)
    (
        SELECT AVG(valor_unitario)
        FROM (
            SELECT valor_unitario
            FROM itens_ata i2
            WHERE NORMALIZE_DESCRICAO(i2.descricao) = itens_ata.descricao_normalizada
            ORDER BY valor_unitario
            LIMIT 2 - (COUNT(*) % 2)
            OFFSET (COUNT(*) - 1) / 2
        ) AS median_values
    ) as preco_mediana,
    STDDEV(valor_unitario) as desvio_padrao,
    COUNT(DISTINCT ata_id) as total_arps,
    COUNT(DISTINCT fornecedor_cnpj) as total_fornecedores,
    COUNT(DISTINCT a.uf) as total_ufs,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'nome', fornecedor_nome,
            'cnpj', fornecedor_cnpj,
            'total_contratos', COUNT(*)
        )
    ) as top_fornecedores,
    COUNT(*) as total_itens
FROM itens_ata i
JOIN atas_registro_preco a ON i.ata_id = a.id
WHERE a.situacao = 'ATIVA'
GROUP BY descricao_normalizada
HAVING COUNT(*) >= 3  -- Mínimo 3 registros para agregar
ON DUPLICATE KEY UPDATE
    preco_minimo = VALUES(preco_minimo),
    preco_maximo = VALUES(preco_maximo),
    preco_medio = VALUES(preco_medio),
    ultima_atualizacao = CURRENT_TIMESTAMP;
```

---

## 🎨 FRONTEND - INTERFACE

### Página Principal: Pesquisa de Preços

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Pesquisa de Preços - Licita.pub</title>
</head>
<body>
    <!-- Barra de Busca Centralizada (estilo Google) -->
    <div class="search-container">
        <h1>Descubra os preços praticados pelo governo</h1>
        <input
            type="text"
            id="busca-produto"
            placeholder="Ex: mouse óptico, papel A4, notebook..."
            autocomplete="off"
        >
        <button id="btn-pesquisar">Pesquisar Preços</button>

        <!-- Filtros Avançados (collapse) -->
        <div id="filtros-avancados" class="hidden">
            <select id="filtro-uf">
                <option value="">Todos os estados</option>
                <!-- UFs -->
            </select>

            <input type="number" id="preco-min" placeholder="Preço mín">
            <input type="number" id="preco-max" placeholder="Preço máx">

            <label>
                <input type="checkbox" id="apenas-vigentes" checked>
                Apenas ARPs ativas
            </label>

            <label>
                <input type="checkbox" id="permite-adesao">
                Permite adesão (carona)
            </label>
        </div>
    </div>

    <!-- Resultados -->
    <div id="resultados-container">
        <!-- Cards de produtos com preços -->
    </div>

    <script src="/frontend/js/precos.js"></script>
</body>
</html>
```

---

### JavaScript: Pesquisa

```javascript
// frontend/js/precos.js

class PesquisaPrecos {
    constructor() {
        this.apiUrl = '/backend/api/precos/pesquisar';
        this.init();
    }

    init() {
        document.getElementById('btn-pesquisar')
            .addEventListener('click', () => this.pesquisar());

        // Autocomplete
        this.setupAutocomplete();
    }

    async pesquisar() {
        const query = document.getElementById('busca-produto').value;

        if (!query || query.length < 3) {
            alert('Digite pelo menos 3 caracteres');
            return;
        }

        // Mostrar loading
        this.showLoading();

        // Construir parâmetros
        const params = new URLSearchParams({
            q: query,
            uf: document.getElementById('filtro-uf').value,
            preco_min: document.getElementById('preco-min').value,
            preco_max: document.getElementById('preco-max').value,
            vigente: document.getElementById('apenas-vigentes').checked ? '1' : '0',
            permite_adesao: document.getElementById('permite-adesao').checked ? '1' : '0',
            limite: 20,
            pagina: 1
        });

        try {
            const response = await fetch(`${this.apiUrl}?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderResultados(data.data, data.agregacao);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Erro ao pesquisar preços');
        }
    }

    renderResultados(itens, agregacao) {
        const container = document.getElementById('resultados-container');

        // Resumo
        let html = `
            <div class="resumo-busca">
                <h2>Encontramos ${agregacao.total_resultados} resultados</h2>
                <div class="estatisticas">
                    <div class="stat">
                        <span class="label">Preço Médio</span>
                        <span class="value">R$ ${agregacao.preco_medio.toFixed(2)}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Menor Preço</span>
                        <span class="value success">R$ ${agregacao.preco_minimo.toFixed(2)}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Maior Preço</span>
                        <span class="value">R$ ${agregacao.preco_maximo.toFixed(2)}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Fornecedores</span>
                        <span class="value">${agregacao.total_fornecedores}</span>
                    </div>
                </div>
            </div>
        `;

        // Cards de produtos
        html += '<div class="produtos-grid">';

        itens.forEach(item => {
            const economiaClass = item.estatisticas.economia_potencial > 0 ? 'positiva' : '';

            html += `
                <div class="produto-card">
                    <div class="card-header">
                        <h3>${item.descricao}</h3>
                        <span class="badge-uf">${item.ata.uf}</span>
                    </div>

                    <div class="preco-destaque">
                        <span class="label">Preço Unitário</span>
                        <span class="valor">R$ ${item.preco_unitario.toFixed(2)}</span>
                        <span class="unidade">por ${item.unidade.toLowerCase()}</span>
                    </div>

                    <div class="economia ${economiaClass}">
                        <i class="icon-trending-down"></i>
                        ${item.estatisticas.economia_potencial}% abaixo da média
                    </div>

                    <div class="fornecedor">
                        <strong>Fornecedor:</strong>
                        ${item.fornecedor.nome}
                        <span class="cnpj">(CNPJ: ${this.formatCNPJ(item.fornecedor.cnpj)})</span>
                    </div>

                    <div class="ata-info">
                        <strong>ARP:</strong> ${item.ata.numero}<br>
                        <strong>Órgão:</strong> ${item.ata.orgao}<br>
                        <strong>Vigência:</strong> até ${this.formatDate(item.ata.vigencia_fim)}
                        ${item.ata.permite_adesao ? '<span class="badge-carona">✓ Permite adesão</span>' : ''}
                    </div>

                    <div class="disponibilidade">
                        <i class="icon-box"></i>
                        ${item.quantidade_disponivel.toLocaleString('pt-BR')} unidades disponíveis
                    </div>

                    <div class="card-actions">
                        <button onclick="verDetalhes('${item.item_id}')" class="btn-primary">
                            Ver Detalhes
                        </button>
                        <button onclick="salvarFavorito('${item.item_id}')" class="btn-secondary">
                            <i class="icon-star"></i> Favoritar
                        </button>
                        <a href="${item.ata.url_pncp}" target="_blank" class="btn-link">
                            <i class="icon-external-link"></i> Ver no PNCP
                        </a>
                    </div>
                </div>
            `;
        });

        html += '</div>';

        container.innerHTML = html;
    }

    formatCNPJ(cnpj) {
        return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
    }

    formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('pt-BR');
    }
}

// Inicializar
const pesquisa = new PesquisaPrecos();
```

---

## ⚡ PERFORMANCE E OTIMIZAÇÃO

### 1. Índices FULLTEXT

```sql
-- Criar índices FULLTEXT para busca rápida
ALTER TABLE itens_ata
ADD FULLTEXT INDEX idx_descricao_fulltext (descricao);

ALTER TABLE produtos_agregados
ADD FULLTEXT INDEX idx_descricao_norm_fulltext (descricao_normalizada);
```

### 2. Cache de Consultas Frequentes

```php
<?php

class CacheService
{
    private $redis;

    public function getCached(string $key, callable $callback, int $ttl = 3600)
    {
        // Tentar buscar do cache
        $cached = $this->redis->get($key);

        if ($cached !== false) {
            return json_decode($cached, true);
        }

        // Executar callback e cachear
        $result = $callback();
        $this->redis->setex($key, $ttl, json_encode($result));

        return $result;
    }
}

// Uso:
$cache = new CacheService();

$resultados = $cache->getCached(
    "pesquisa:mouse_optico:SP",
    function() {
        return $this->pesquisarPrecosDB('mouse optico', ['uf' => 'SP']);
    },
    1800 // 30 minutos
);
```

### 3. Paginação Eficiente

```sql
-- Usar cursor-based pagination para grandes datasets
SELECT * FROM itens_ata
WHERE id > :last_id
  AND MATCH(descricao) AGAINST(:query IN BOOLEAN MODE)
ORDER BY id ASC
LIMIT 20;
```

---

## 📱 PRÓXIMOS PASSOS

### Esta Semana
- [ ] Criar migration 005 (tabelas de alertas e histórico)
- [ ] Implementar AnalisePrecosService
- [ ] Implementar ItemAtaController
- [ ] Testar API de pesquisa

### Próximas 2 Semanas
- [ ] Frontend de pesquisa de preços
- [ ] Sistema de alertas
- [ ] Dashboard de oportunidades
- [ ] Integração completa

### Próximo Mês
- [ ] Sincronização automática PNCP
- [ ] Algoritmos de ML para previsão
- [ ] API pública
- [ ] Lançamento MVP

---

**Desenvolvido para Licita.pub**
**Versão:** 1.0.0
**Data:** 03/11/2025
