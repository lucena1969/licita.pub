-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 25/10/2025 às 20:14
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `licitapub`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alertas`
--

CREATE TABLE `alertas` (
  `id` char(36) NOT NULL,
  `usuario_id` char(36) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `filtros` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filtros`)),
  `frequencia` enum('IMEDIATA','DIARIA','SEMANAL') NOT NULL DEFAULT 'DIARIA',
  `ultimo_envio` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos`
--

CREATE TABLE `favoritos` (
  `id` char(36) NOT NULL,
  `usuario_id` char(36) NOT NULL,
  `licitacao_id` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_buscas`
--

CREATE TABLE `historico_buscas` (
  `id` char(36) NOT NULL,
  `usuario_id` char(36) NOT NULL,
  `termo_busca` varchar(500) NOT NULL,
  `filtros` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filtros`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_licitacao`
--

CREATE TABLE `itens_licitacao` (
  `id` char(36) NOT NULL,
  `licitacao_id` char(36) NOT NULL,
  `numero_item` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `quantidade` decimal(15,3) NOT NULL,
  `unidade` varchar(20) NOT NULL,
  `valor_unitario` decimal(15,2) DEFAULT NULL,
  `valor_total` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_licitacao`
--

INSERT INTO `itens_licitacao` (`id`, `licitacao_id`, `numero_item`, `descricao`, `quantidade`, `unidade`, `valor_unitario`, `valor_total`) VALUES
('af20a649-b03d-11f0-816d-2025649166b9', 'af1b572e-b03d-11f0-816d-2025649166b9', 1, 'Papel A4 - Resma com 500 folhas', 200.000, 'RESMA', 25.00, 5000.00),
('af20b879-b03d-11f0-816d-2025649166b9', 'af1b572e-b03d-11f0-816d-2025649166b9', 2, 'Caneta esferográfica azul', 500.000, 'UNIDADE', 1.50, 750.00),
('af20b972-b03d-11f0-816d-2025649166b9', 'af1b572e-b03d-11f0-816d-2025649166b9', 3, 'Grampeador de mesa', 50.000, 'UNIDADE', 35.00, 1750.00),
('af20ba13-b03d-11f0-816d-2025649166b9', 'af1b572e-b03d-11f0-816d-2025649166b9', 4, 'Pasta suspensa para arquivo', 300.000, 'UNIDADE', 8.00, 2400.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `licitacoes`
--

CREATE TABLE `licitacoes` (
  `id` char(36) NOT NULL,
  `pncp_id` varchar(100) NOT NULL,
  `orgao_id` varchar(50) NOT NULL,
  `numero` varchar(50) NOT NULL,
  `objeto` text NOT NULL,
  `modalidade` varchar(50) NOT NULL,
  `situacao` varchar(30) NOT NULL,
  `valor_estimado` decimal(15,2) DEFAULT NULL,
  `data_publicacao` datetime NOT NULL,
  `data_abertura` datetime DEFAULT NULL,
  `data_encerramento` datetime DEFAULT NULL,
  `uf` char(2) NOT NULL,
  `municipio` varchar(100) NOT NULL,
  `url_edital` text DEFAULT NULL,
  `url_pncp` text NOT NULL,
  `nome_orgao` varchar(255) NOT NULL,
  `cnpj_orgao` varchar(18) NOT NULL,
  `sincronizado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `licitacoes`
--

INSERT INTO `licitacoes` (`id`, `pncp_id`, `orgao_id`, `numero`, `objeto`, `modalidade`, `situacao`, `valor_estimado`, `data_publicacao`, `data_abertura`, `data_encerramento`, `uf`, `municipio`, `url_edital`, `url_pncp`, `nome_orgao`, `cnpj_orgao`, `sincronizado_em`, `atualizado_em`) VALUES
('af1b572e-b03d-11f0-816d-2025649166b9', 'EXEMPLO-001-2025', '00000000000001', 'CT-001/2025', 'Contratação de empresa especializada para fornecimento de material de escritório para as unidades administrativas do município, conforme especificações do termo de referência.', 'PREGAO_ELETRONICO', 'ATIVO', 50000.00, '2025-10-23 15:25:42', '2025-11-07 15:25:42', '2026-10-23 15:25:42', 'SP', 'São Paulo', NULL, 'https://pncp.gov.br/app/contratos/EXEMPLO-001-2025', 'PREFEITURA MUNICIPAL DE EXEMPLO', '00000000000001', '2025-10-23 15:25:42', '2025-10-23 15:25:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sincronizacao`
--

CREATE TABLE `logs_sincronizacao` (
  `id` char(36) NOT NULL,
  `fonte` varchar(50) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `registros_novos` int(11) NOT NULL DEFAULT 0,
  `registros_atualizados` int(11) NOT NULL DEFAULT 0,
  `registros_erro` int(11) NOT NULL DEFAULT 0,
  `mensagem` text DEFAULT NULL,
  `detalhes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalhes`)),
  `iniciado` datetime NOT NULL,
  `finalizado` datetime NOT NULL,
  `duracao` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cpf_cnpj` varchar(18) DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `token_verificacao` varchar(255) DEFAULT NULL,
  `token_verificacao_expira` datetime DEFAULT NULL,
  `token_reset_senha` varchar(255) DEFAULT NULL,
  `token_reset_senha_expira` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `plano` enum('GRATUITO','BASICO','INTERMEDIARIO','PREMIUM') NOT NULL DEFAULT 'GRATUITO',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `senha`, `nome`, `telefone`, `cpf_cnpj`, `email_verificado`, `token_verificacao`, `token_verificacao_expira`, `token_reset_senha`, `token_reset_senha_expira`, `ativo`, `plano`, `created_at`, `updated_at`) VALUES
('af15f65e-b03d-11f0-816d-2025649166b9', 'teste@licita.pub', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztOvWLhUFz.i', 'Usuário de Teste', '(11) 98765-4321', '12345678901', 1, NULL, NULL, NULL, NULL, 1, 'GRATUITO', '2025-10-23 15:25:42', '2025-10-23 15:25:42');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_ativo` (`ativo`),
  ADD KEY `idx_frequencia` (`frequencia`);

--
-- Índices de tabela `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_licitacao` (`usuario_id`,`licitacao_id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_licitacao_id` (`licitacao_id`);

--
-- Índices de tabela `historico_buscas`
--
ALTER TABLE `historico_buscas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `itens_licitacao`
--
ALTER TABLE `itens_licitacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_licitacao_id` (`licitacao_id`),
  ADD KEY `idx_numero_item` (`numero_item`);

--
-- Índices de tabela `licitacoes`
--
ALTER TABLE `licitacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pncp_id` (`pncp_id`),
  ADD KEY `idx_pncp_id` (`pncp_id`),
  ADD KEY `idx_uf` (`uf`),
  ADD KEY `idx_municipio` (`municipio`),
  ADD KEY `idx_uf_municipio` (`uf`,`municipio`),
  ADD KEY `idx_modalidade` (`modalidade`),
  ADD KEY `idx_situacao` (`situacao`),
  ADD KEY `idx_modalidade_situacao` (`modalidade`,`situacao`),
  ADD KEY `idx_data_abertura` (`data_abertura`),
  ADD KEY `idx_data_publicacao` (`data_publicacao`),
  ADD KEY `idx_valor` (`valor_estimado`),
  ADD KEY `idx_cnpj_orgao` (`cnpj_orgao`);
ALTER TABLE `licitacoes` ADD FULLTEXT KEY `idx_objeto` (`objeto`);
ALTER TABLE `licitacoes` ADD FULLTEXT KEY `idx_nome_orgao` (`nome_orgao`);

--
-- Índices de tabela `logs_sincronizacao`
--
ALTER TABLE `logs_sincronizacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fonte` (`fonte`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_iniciado` (`iniciado`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alertas`
--
ALTER TABLE `alertas`
  ADD CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`licitacao_id`) REFERENCES `licitacoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `historico_buscas`
--
ALTER TABLE `historico_buscas`
  ADD CONSTRAINT `historico_buscas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `itens_licitacao`
--
ALTER TABLE `itens_licitacao`
  ADD CONSTRAINT `itens_licitacao_ibfk_1` FOREIGN KEY (`licitacao_id`) REFERENCES `licitacoes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
