/*
====================================================================================================
LICITA.PUB - DADOS INICIAIS (SEEDS)
====================================================================================================
Descrição: Script opcional para inserir dados de exemplo/teste
Autor: Licita.pub
Data: 2025-01-17
Versão: 1.0

INSTRUÇÕES:
Este script é OPCIONAL e serve apenas para testes em ambiente de desenvolvimento.
NÃO execute em produção!

1. Execute DEPOIS dos scripts 01 e 02
2. Abra o phpMyAdmin
3. Selecione o banco "licitapub"
4. Clique em "SQL"
5. Cole este script
6. Clique em "Executar"
====================================================================================================
*/

USE licitapub;

-- ====================================================================================================
-- USUÁRIO DE TESTE
-- ====================================================================================================
-- Senha: Teste123 (hash bcrypt abaixo)
INSERT INTO usuarios (
    id,
    email,
    senha,
    nome,
    telefone,
    cpf_cnpj,
    email_verificado,
    ativo,
    plano
) VALUES (
    UUID(),
    'teste@licita.pub',
    '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztOvWLhUFz.i', -- Senha: Teste123
    'Usuário de Teste',
    '(11) 98765-4321',
    '12345678901',
    TRUE,
    TRUE,
    'GRATUITO'
) ON DUPLICATE KEY UPDATE email = email; -- Não insere se já existir

-- ====================================================================================================
-- LICITAÇÃO DE EXEMPLO
-- ====================================================================================================
-- Exemplo baseado em dados reais do PNCP
INSERT INTO licitacoes (
    id,
    pncp_id,
    orgao_id,
    numero,
    objeto,
    modalidade,
    situacao,
    valor_estimado,
    data_publicacao,
    data_abertura,
    data_encerramento,
    uf,
    municipio,
    url_edital,
    url_pncp,
    nome_orgao,
    cnpj_orgao
) VALUES (
    UUID(),
    'EXEMPLO-001-2025',
    '00000000000001',
    'CT-001/2025',
    'Contratação de empresa especializada para fornecimento de material de escritório para as unidades administrativas do município, conforme especificações do termo de referência.',
    'PREGAO_ELETRONICO',
    'ATIVO',
    50000.00,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 15 DAY),
    DATE_ADD(NOW(), INTERVAL 365 DAY),
    'SP',
    'São Paulo',
    NULL,
    'https://pncp.gov.br/app/contratos/EXEMPLO-001-2025',
    'PREFEITURA MUNICIPAL DE EXEMPLO',
    '00000000000001'
) ON DUPLICATE KEY UPDATE pncp_id = pncp_id;

-- ====================================================================================================
-- ITENS DA LICITAÇÃO DE EXEMPLO
-- ====================================================================================================
SET @licitacao_exemplo_id = (SELECT id FROM licitacoes WHERE pncp_id = 'EXEMPLO-001-2025' LIMIT 1);

INSERT INTO itens_licitacao (
    id,
    licitacao_id,
    numero_item,
    descricao,
    quantidade,
    unidade,
    valor_unitario,
    valor_total
) VALUES
(UUID(), @licitacao_exemplo_id, 1, 'Papel A4 - Resma com 500 folhas', 200.000, 'RESMA', 25.00, 5000.00),
(UUID(), @licitacao_exemplo_id, 2, 'Caneta esferográfica azul', 500.000, 'UNIDADE', 1.50, 750.00),
(UUID(), @licitacao_exemplo_id, 3, 'Grampeador de mesa', 50.000, 'UNIDADE', 35.00, 1750.00),
(UUID(), @licitacao_exemplo_id, 4, 'Pasta suspensa para arquivo', 300.000, 'UNIDADE', 8.00, 2400.00)
ON DUPLICATE KEY UPDATE numero_item = numero_item;

-- ====================================================================================================
-- VERIFICAÇÃO DOS DADOS
-- ====================================================================================================

SELECT '✓ Dados iniciais inseridos com sucesso!' AS Status;

-- Resumo dos dados inseridos
SELECT 'Usuários' AS Tabela, COUNT(*) AS Total FROM usuarios
UNION ALL
SELECT 'Licitações' AS Tabela, COUNT(*) AS Total FROM licitacoes
UNION ALL
SELECT 'Itens' AS Tabela, COUNT(*) AS Total FROM itens_licitacao
UNION ALL
SELECT 'Favoritos' AS Tabela, COUNT(*) AS Total FROM favoritos
UNION ALL
SELECT 'Alertas' AS Tabela, COUNT(*) AS Total FROM alertas;

-- Exibir usuário de teste criado
SELECT
    'Usuário de teste criado:' AS Info,
    email AS Email,
    nome AS Nome,
    'Senha: Teste123' AS Senha,
    plano AS Plano
FROM usuarios
WHERE email = 'teste@licita.pub';
