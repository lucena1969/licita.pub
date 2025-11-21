<?php
/**
 * API: Salvar Comparação de Preços
 * Endpoint para salvar produtos capturados e fazer análise de oportunidades
 */

// Habilitar exibição de erros para debug
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Carregar .env - tentar múltiplos caminhos
    $envPaths = [
        __DIR__ . '/../../../.env',   // Produção (Hostinger) e Localhost (XAMPP)
        __DIR__ . '/../../.env',      // Fallback
    ];

    foreach ($envPaths as $envFile) {
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value, '"\'');
                    putenv(trim($key) . "=" . trim($value, '"\''));
                }
            }
            break;
        }
    }

    // Autoloader - tentar múltiplos caminhos (localhost vs produção)
    $autoloaderPaths = [
        __DIR__ . '/../../../vendor/autoload.php',   // Produção (Hostinger) e Localhost (XAMPP)
        __DIR__ . '/../../vendor/autoload.php',      // Fallback
    ];

    $autoloaderPath = null;
    foreach ($autoloaderPaths as $path) {
        if (file_exists($path)) {
            $autoloaderPath = $path;
            break;
        }
    }

    if (!$autoloaderPath) {
        throw new Exception('Autoloader não encontrado. Tentei: ' . implode(', ', $autoloaderPaths));
    }

    require_once $autoloaderPath;

    // Verificar autenticação
    $usuario = \App\Middleware\AuthMiddleware::verificar();
} catch (Exception $e) {
    error_log("[API Comparações] Erro fatal na inicialização: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro na inicialização: ' . $e->getMessage()
    ]);
    exit;
}

if (!$usuario) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Não autenticado'
    ]);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
    exit;
}

try {
    // Obter payload
    error_log("[API Comparações] Lendo payload");
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        error_log("[API Comparações] Dados inválidos recebidos");
        throw new Exception('Dados inválidos');
    }

    error_log("[API Comparações] Payload recebido: " . json_encode(array_keys($data)));

    // Validar campos obrigatórios
    if (empty($data['licitacao_descricao']) || !isset($data['preco_governo']) || empty($data['produtos'])) {
        error_log("[API Comparações] Campos obrigatórios faltando");
        throw new Exception('Campos obrigatórios faltando: licitacao_descricao, preco_governo, produtos');
    }

    if (!is_array($data['produtos']) || count($data['produtos']) === 0) {
        error_log("[API Comparações] Produtos inválido");
        throw new Exception('Produtos deve ser um array não vazio');
    }

    error_log("[API Comparações] Validação OK. Total produtos: " . count($data['produtos']));

    // Conexão
    error_log("[API Comparações] Conectando ao banco");
    $db = \App\Config\Database::getConnection();
    error_log("[API Comparações] Conexão estabelecida");

    // Iniciar transação
    $db->beginTransaction();

    // 1. Criar registro de comparação
    $comparacaoId = \App\Utils\UUID::generate();

    $stmtComparacao = $db->prepare("
        INSERT INTO comparacoes_precos (
            id, usuario_id, licitacao_id, licitacao_descricao,
            preco_governo, status
        ) VALUES (
            :id, :usuario_id, :licitacao_id, :descricao,
            :preco_governo, 'finalizada'
        )
    ");

    $stmtComparacao->execute([
        ':id' => $comparacaoId,
        ':usuario_id' => $usuario->id,
        ':licitacao_id' => $data['licitacao_id'] ?? null,
        ':descricao' => $data['licitacao_descricao'],
        ':preco_governo' => $data['preco_governo']
    ]);

    // 2. Inserir produtos do mercado
    $stmtProduto = $db->prepare("
        INSERT INTO produtos_mercado (
            id, usuario_id, titulo, preco, url, fonte,
            imagem_url, frete_gratis, metodo_coleta, palavras_chave
        ) VALUES (
            :id, :usuario_id, :titulo, :preco, :url, :fonte,
            :imagem_url, :frete_gratis, 'extensao_navegador', :palavras_chave
        )
    ");

    $stmtItem = $db->prepare("
        INSERT INTO itens_comparacao (
            id, comparacao_id, produto_mercado_id,
            margem_reais, margem_percentual, classificacao, selecionado
        ) VALUES (
            :id, :comparacao_id, :produto_id,
            :margem_reais, :margem_percentual, :classificacao, true
        )
    ");

    $produtosSalvos = 0;
    $analisePrecos = [
        'precos' => [],
        'margens' => []
    ];

    foreach ($data['produtos'] as $produto) {
        // Validar produto
        if (empty($produto['titulo']) || !isset($produto['preco']) || empty($produto['url'])) {
            continue;
        }

        // Gerar ID do produto
        $produtoId = \App\Utils\UUID::generate();

        // Extrair palavras-chave
        $palavrasChave = extrairPalavrasChave($produto['titulo']);

        // Inserir produto
        $stmtProduto->execute([
            ':id' => $produtoId,
            ':usuario_id' => $usuario->id,
            ':titulo' => $produto['titulo'],
            ':preco' => $produto['preco'],
            ':url' => $produto['url'],
            ':fonte' => $produto['fonte'] ?? 'Desconhecido',
            ':imagem_url' => $produto['imagem_url'] ?? null,
            ':frete_gratis' => ($produto['frete_gratis'] ?? false) ? 1 : 0,
            ':palavras_chave' => $palavrasChave
        ]);

        // Calcular margem
        $margemReais = $produto['margem_reais'] ?? ($data['preco_governo'] - $produto['preco']);
        $margemPercentual = $produto['margem_percentual'] ?? (($margemReais / $data['preco_governo']) * 100);

        // Classificar oportunidade
        $classificacao = classificarOportunidade($margemPercentual);

        // Inserir item da comparação
        $stmtItem->execute([
            ':id' => \App\Utils\UUID::generate(),
            ':comparacao_id' => $comparacaoId,
            ':produto_id' => $produtoId,
            ':margem_reais' => $margemReais,
            ':margem_percentual' => $margemPercentual,
            ':classificacao' => $classificacao
        ]);

        $produtosSalvos++;

        // Coletar dados para análise
        $analisePrecos['precos'][] = $produto['preco'];
        $analisePrecos['margens'][] = $margemReais;
    }

    // 3. Registrar histórico de captura
    $fontes = array_count_values(array_column($data['produtos'], 'fonte'));

    foreach ($fontes as $fonte => $total) {
        $stmtHistorico = $db->prepare("
            INSERT INTO historico_capturas (
                id, usuario_id, fonte, total_produtos, produtos_salvos
            ) VALUES (
                :id, :usuario_id, :fonte, :total, :salvos
            )
        ");

        $stmtHistorico->execute([
            ':id' => \App\Utils\UUID::generate(),
            ':usuario_id' => $usuario->id,
            ':fonte' => $fonte,
            ':total' => $total,
            ':salvos' => $total
        ]);
    }

    // Commit
    $db->commit();

    // 4. Gerar análise de oportunidades
    $analise = gerarAnalise($analisePrecos, $data['preco_governo']);

    // Resposta
    echo json_encode([
        'success' => true,
        'comparacao_id' => $comparacaoId,
        'produtos_salvos' => $produtosSalvos,
        'analise' => $analise
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log("[API Comparações] Erro: " . $e->getMessage());
    error_log("[API Comparações] Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ========== FUNÇÕES AUXILIARES ==========

function extrairPalavrasChave($titulo) {
    $stopWords = ['de', 'da', 'do', 'para', 'com', 'em', 'a', 'o', 'e', 'ou'];

    $palavras = preg_split('/\s+/', strtolower($titulo));
    $palavras = array_filter($palavras, function($p) use ($stopWords) {
        return strlen($p) > 2 && !in_array($p, $stopWords);
    });

    return implode(' ', array_slice($palavras, 0, 10));
}

function classificarOportunidade($margemPercentual) {
    if ($margemPercentual >= 30) return 'EXCELENTE';
    if ($margemPercentual >= 20) return 'MUITO_BOA';
    if ($margemPercentual >= 10) return 'BOA';
    if ($margemPercentual >= 5) return 'RAZOAVEL';
    return 'BAIXA';
}

function gerarAnalise($dados, $precoGoverno) {
    if (empty($dados['precos'])) {
        return [
            'menor_preco' => 0,
            'preco_medio' => 0,
            'melhor_margem' => 0,
            'recomendacao' => 'Nenhum produto analisado'
        ];
    }

    $menorPreco = min($dados['precos']);
    $precoMedio = array_sum($dados['precos']) / count($dados['precos']);
    $melhorMargem = max($dados['margens']);
    $margemPercentual = ($melhorMargem / $precoGoverno) * 100;

    // Gerar recomendação
    $recomendacao = '';
    if ($margemPercentual >= 30) {
        $recomendacao = '🌟 EXCELENTE oportunidade! Margem muito superior à média do mercado. Alta possibilidade de lucro.';
    } elseif ($margemPercentual >= 20) {
        $recomendacao = '✨ MUITO BOA oportunidade! Margem significativa acima do mercado. Recomendado participar.';
    } elseif ($margemPercentual >= 10) {
        $recomendacao = '👍 BOA oportunidade! Margem positiva interessante. Vale a pena avaliar participação.';
    } elseif ($margemPercentual >= 5) {
        $recomendacao = '👌 Oportunidade RAZOÁVEL. Margem baixa, mas ainda positiva. Avaliar custos operacionais.';
    } else {
        $recomendacao = '⚠️ Oportunidade de BAIXA margem. Recomendado cautela ao avaliar participação.';
    }

    return [
        'menor_preco' => $menorPreco,
        'preco_medio' => $precoMedio,
        'melhor_margem' => $melhorMargem,
        'margem_percentual' => $margemPercentual,
        'total_produtos' => count($dados['precos']),
        'recomendacao' => $recomendacao
    ];
}
