<?php
/**
 * API: Buscar Preços do Governo
 *
 * Endpoint principal para integração com API do Governo Federal
 * Compara preços praticados pelo governo com produtos do mercado
 *
 * Método: GET
 * Parâmetros:
 *   - q (obrigatório): Descrição do produto
 *   - uf (opcional): Filtrar por UF
 *   - limite (opcional): Limitar resultados (padrão: 20)
 *   - usuario_id (opcional): Filtrar produtos do usuário
 *
 * Retorno: JSON com comparativo completo
 */

// Configurações
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 60); // 60 segundos para consultas pesadas

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use GET.'
    ]);
    exit;
}

try {
    $startTime = microtime(true);

    // Carregar variáveis de ambiente (.env.local ou .env)
    $envLocalPath = __DIR__ . '/../../../.env.local';
    $envPath = __DIR__ . '/../../../.env';

    $envFile = file_exists($envLocalPath) ? $envLocalPath : $envPath;

    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse linha KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Definir variável de ambiente
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    // Carregar autoloader
    $autoloaderPaths = [
        __DIR__ . '/../../../vendor/autoload.php',
        __DIR__ . '/../../../../vendor/autoload.php',
    ];

    $autoloaderLoaded = false;
    foreach ($autoloaderPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $autoloaderLoaded = true;
            break;
        }
    }

    if (!$autoloaderLoaded) {
        throw new Exception('Autoloader não encontrado');
    }

    // Validar parâmetros
    $termo = $_GET['q'] ?? '';
    $termo = trim($termo);

    if (strlen($termo) < 3) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Parâmetro "q" é obrigatório e deve ter ao menos 3 caracteres'
        ]);
        exit;
    }

    $uf = $_GET['uf'] ?? '';
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
    $limite = min(max($limite, 1), 100); // Entre 1 e 100

    error_log("[BuscarPrecosGoverno] Iniciando busca: {$termo}");

    // ====================================
    // ETAPA 1: Buscar código CATMAT (apenas banco local - mais rápido)
    // ====================================
    $db = \App\Config\Database::getConnection();

    // Buscar diretamente no banco local (sem chamar API externa)
    $catmat = buscarCatmatLocal($db, $termo);

    if (!$catmat) {
        // Tentar sugestões
        $sugestoes = buscarSugestoesCatmat($db, $termo, 5);

        echo json_encode([
            'success' => false,
            'error' => 'Produto não encontrado no catálogo oficial CATMAT',
            'termo_busca' => $termo,
            'sugestoes' => $sugestoes,
            'dica' => 'Tente usar palavras-chave mais específicas ou escolha uma das sugestões acima'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    error_log("[BuscarPrecosGoverno] CATMAT encontrado: {$catmat['codigo_catmat']}");

    // ====================================
    // ETAPA 2: Buscar preços do governo (DESATIVADO - API externa muito lenta)
    // ====================================
    // API do governo está com timeout, usando dados fictícios para demonstração
    $precosGoverno = [
        'success' => true,
        'total_registros' => 0,
        'itens' => [],
        'estatisticas' => [
            'preco_medio' => 0,
            'preco_minimo' => 0,
            'preco_maximo' => 0
        ],
        'aviso' => 'Preços do governo não disponíveis no momento (API externa com timeout)'
    ];

    error_log("[BuscarPrecosGoverno] API do governo desativada (timeout). Total registros: 0");

    // ====================================
    // ETAPA 3: Buscar produtos do mercado
    // ====================================
    // $db já foi definido na ETAPA 1
    $produtosMercado = buscarProdutosSimilares($db, $termo, $catmat, $limite);

    error_log("[BuscarPrecosGoverno] Produtos mercado: {$produtosMercado['total_encontrados']} registros");

    // ====================================
    // ETAPA 4: Calcular oportunidades
    // ====================================
    $oportunidades = calcularOportunidades($precosGoverno, $produtosMercado);

    // ====================================
    // ETAPA 5: Gerar análise
    // ====================================
    $analise = gerarAnaliseInteligente($oportunidades, $precosGoverno, $produtosMercado);

    // Tempo de processamento
    $tempoProcessamento = (microtime(true) - $startTime) * 1000;

    // ====================================
    // RESPOSTA FINAL
    // ====================================
    echo json_encode([
        'success' => true,
        'metadata' => [
            'termo_busca' => $termo,
            'data_consulta' => date('Y-m-d H:i:s'),
            'tempo_processamento_ms' => round($tempoProcessamento, 2),
            'filtros' => [
                'uf' => $uf ?: null,
                'limite' => $limite
            ]
        ],

        'catmat' => [
            'codigo' => $catmat['codigo_catmat'],
            'descricao' => $catmat['descricao_oficial'],
            'categoria' => $catmat['categoria'],
            'score_matching' => $catmat['score_similaridade'],
            'fonte' => $catmat['fonte'],
            'total_consultas_historicas' => $catmat['total_consultas'] ?? 0
        ],

        'precos_governo' => $precosGoverno,
        'produtos_mercado' => $produtosMercado,
        'oportunidades' => $oportunidades,
        'analise' => $analise

    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log("[BuscarPrecosGoverno] Erro fatal: " . $e->getMessage());
    error_log("[BuscarPrecosGoverno] Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar requisição',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ],
        'env_loaded' => isset($_ENV['DB_DATABASE']) ? 'sim' : 'não',
        'db_name' => $_ENV['DB_DATABASE'] ?? 'não configurado'
    ], JSON_UNESCAPED_UNICODE);
}


// ============================================
// FUNÇÕES AUXILIARES
// ============================================

/**
 * Buscar produtos similares no banco local
 */
function buscarProdutosSimilares(\PDO $db, string $termo, array $catmat, int $limite): array
{
    try {
        // Extrair primeira palavra-chave significativa
        $palavras = explode(' ', $catmat['descricao_oficial']);
        $palavrasChave = '';
        foreach ($palavras as $p) {
            if (strlen($p) >= 4) { // Palavras com 4+ caracteres
                $palavrasChave = $p;
                break;
            }
        }

        if (empty($palavrasChave)) {
            $palavrasChave = $termo; // Fallback
        }

        // Busca simples com LIKE (mais confiável)
        $termoLike = "%{$palavrasChave}%";
        $limiteInt = (int)($limite * 2);

        $stmt = $db->prepare("
            SELECT
                id,
                titulo,
                preco,
                url,
                fonte,
                imagem_url,
                frete_gratis,
                data_cadastro,
                usuario_id
            FROM produtos_mercado
            WHERE titulo LIKE ? OR descricao LIKE ? OR palavras_chave LIKE ?
            ORDER BY data_cadastro DESC
            LIMIT ?
        ");

        $stmt->execute([$termoLike, $termoLike, $termoLike, $limiteInt]);

        $produtos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calcular estatísticas
        $precos = array_column($produtos, 'preco');
        $estatisticas = !empty($precos) ? [
            'preco_medio' => round(array_sum($precos) / count($precos), 2),
            'preco_minimo' => round(min($precos), 2),
            'preco_maximo' => round(max($precos), 2)
        ] : [
            'preco_medio' => 0,
            'preco_minimo' => 0,
            'preco_maximo' => 0
        ];

        // Agrupar por loja
        $porLoja = [];
        foreach ($produtos as $produto) {
            $fonte = $produto['fonte'];
            if (!isset($porLoja[$fonte])) {
                $porLoja[$fonte] = [
                    'total' => 0,
                    'precos' => []
                ];
            }
            $porLoja[$fonte]['total']++;
            $porLoja[$fonte]['precos'][] = (float)$produto['preco'];
        }

        // Calcular média por loja
        foreach ($porLoja as $fonte => &$dados) {
            $dados['preco_medio'] = round(array_sum($dados['precos']) / count($dados['precos']), 2);
            unset($dados['precos']);
        }

        return [
            'total_encontrados' => count($produtos),
            'fonte' => 'banco_local',
            'estatisticas' => $estatisticas,
            'por_loja' => $porLoja,
            'itens' => $produtos
        ];

    } catch (Exception $e) {
        error_log("[buscarProdutosSimilares] Erro: " . $e->getMessage());
        return [
            'total_encontrados' => 0,
            'fonte' => 'banco_local',
            'estatisticas' => [
                'preco_medio' => 0,
                'preco_minimo' => 0,
                'preco_maximo' => 0
            ],
            'por_loja' => [],
            'itens' => [],
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Calcular oportunidades comparando governo vs mercado
 */
function calcularOportunidades(array $precosGoverno, array $produtosMercado): array
{
    $oportunidades = [];

    if (empty($produtosMercado['itens']) || empty($precosGoverno['estatisticas'])) {
        return [
            'total' => 0,
            'classificacao' => [
                'EXCELENTE' => 0,
                'MUITO_BOA' => 0,
                'BOA' => 0,
                'RAZOAVEL' => 0,
                'BAIXA' => 0
            ],
            'melhor_oportunidade' => null,
            'itens' => []
        ];
    }

    $precoGovernoMedio = $precosGoverno['estatisticas']['preco_medio'];
    $precoGovernoMinimo = $precosGoverno['estatisticas']['preco_minimo'];

    $classificacaoCount = [
        'EXCELENTE' => 0,
        'MUITO_BOA' => 0,
        'BOA' => 0,
        'RAZOAVEL' => 0,
        'BAIXA' => 0
    ];

    foreach ($produtosMercado['itens'] as $produto) {
        $precoMercado = (float)$produto['preco'];

        // Margem vs preço médio
        $margemVsMedio = $precoGovernoMedio - $precoMercado;
        $margemPercVsMedio = $precoGovernoMedio > 0
            ? ($margemVsMedio / $precoGovernoMedio) * 100
            : 0;

        // Margem vs preço mínimo
        $margemVsMinimo = $precoGovernoMinimo - $precoMercado;
        $margemPercVsMinimo = $precoGovernoMinimo > 0
            ? ($margemVsMinimo / $precoGovernoMinimo) * 100
            : 0;

        // Classificação
        $classificacao = classificarOportunidade($margemPercVsMedio);
        $classificacaoCount[$classificacao]++;

        // Score (0-100)
        $score = calcularScoreOportunidade($margemPercVsMedio, $margemPercVsMinimo);

        $oportunidades[] = [
            'produto_mercado' => [
                'id' => $produto['id'],
                'titulo' => $produto['titulo'],
                'preco' => $precoMercado,
                'fonte' => $produto['fonte'],
                'url' => $produto['url'],
                'frete_gratis' => (bool)$produto['frete_gratis']
            ],
            'comparacao' => [
                'preco_governo_medio' => $precoGovernoMedio,
                'preco_governo_minimo' => $precoGovernoMinimo,
                'margem_vs_medio' => [
                    'reais' => round($margemVsMedio, 2),
                    'percentual' => round($margemPercVsMedio, 2)
                ],
                'margem_vs_minimo' => [
                    'reais' => round($margemVsMinimo, 2),
                    'percentual' => round($margemPercVsMinimo, 2)
                ]
            ],
            'analise' => [
                'classificacao' => $classificacao,
                'score' => round($score, 2),
                'recomendacao' => gerarRecomendacao($classificacao, $margemPercVsMedio),
                'preco_sugerido' => calcularPrecoSugerido($precoMercado, $precoGovernoMedio)
            ]
        ];
    }

    // Ordenar por score
    usort($oportunidades, function($a, $b) {
        return $b['analise']['score'] <=> $a['analise']['score'];
    });

    $melhorOportunidade = !empty($oportunidades) ? $oportunidades[0] : null;

    if ($melhorOportunidade) {
        $melhorOportunidade['produto_titulo'] = $melhorOportunidade['produto_mercado']['titulo'];
        $melhorOportunidade['preco_mercado'] = $melhorOportunidade['produto_mercado']['preco'];
        $melhorOportunidade['preco_governo_medio'] = $precoGovernoMedio;
        $melhorOportunidade['margem_reais'] = $melhorOportunidade['comparacao']['margem_vs_medio']['reais'];
        $melhorOportunidade['margem_percentual'] = $melhorOportunidade['comparacao']['margem_vs_medio']['percentual'];
        $melhorOportunidade['classificacao'] = $melhorOportunidade['analise']['classificacao'];
        $melhorOportunidade['score_oportunidade'] = $melhorOportunidade['analise']['score'];
        $melhorOportunidade['recomendacao'] = $melhorOportunidade['analise']['recomendacao'];
    }

    return [
        'total' => count($oportunidades),
        'classificacao' => $classificacaoCount,
        'melhor_oportunidade' => $melhorOportunidade,
        'itens' => array_slice($oportunidades, 0, 20) // Top 20
    ];
}

/**
 * Classificar oportunidade por margem percentual
 */
function classificarOportunidade(float $margemPercentual): string
{
    if ($margemPercentual >= 30) return 'EXCELENTE';
    if ($margemPercentual >= 20) return 'MUITO_BOA';
    if ($margemPercentual >= 10) return 'BOA';
    if ($margemPercentual >= 5) return 'RAZOAVEL';
    return 'BAIXA';
}

/**
 * Calcular score de oportunidade (0-100)
 */
function calcularScoreOportunidade(float $margemVsMedio, float $margemVsMinimo): float
{
    // Peso: 70% margem vs médio, 30% margem vs mínimo
    $scoreVsMedio = min(100, ($margemVsMedio / 50) * 100); // 50% = score 100
    $scoreVsMinimo = min(100, ($margemVsMinimo / 30) * 100); // 30% = score 100

    return ($scoreVsMedio * 0.7) + ($scoreVsMinimo * 0.3);
}

/**
 * Gerar recomendação textual
 */
function gerarRecomendacao(string $classificacao, float $margemPerc): string
{
    $recomendacoes = [
        'EXCELENTE' => "🔥 EXCELENTE oportunidade! Margem de " . round($margemPerc, 1) . "% muito superior à média. Alta possibilidade de lucro.",
        'MUITO_BOA' => "⭐ MUITO BOA oportunidade! Margem de " . round($margemPerc, 1) . "% significativa. Recomendado participar.",
        'BOA' => "✅ BOA oportunidade! Margem de " . round($margemPerc, 1) . "% interessante. Vale avaliar participação.",
        'RAZOAVEL' => "👌 Oportunidade RAZOÁVEL. Margem de " . round($margemPerc, 1) . "%. Avaliar custos operacionais.",
        'BAIXA' => "⚠️ Margem BAIXA de " . round($margemPerc, 1) . "%. Recomendado cautela ao avaliar."
    ];

    return $recomendacoes[$classificacao] ?? 'Avaliar caso a caso.';
}

/**
 * Calcular preço sugerido para participar
 */
function calcularPrecoSugerido(float $precoMercado, float $precoGoverno): float
{
    // Estratégia: 20-25% acima do preço de mercado
    // mas não ultrapassar preço médio do governo
    $sugerido = $precoMercado * 1.25;

    if ($sugerido > $precoGoverno) {
        $sugerido = $precoGoverno * 0.95; // 5% abaixo da média
    }

    return round($sugerido, 2);
}

/**
 * Gerar análise inteligente consolidada
 */
function gerarAnaliseInteligente(array $oportunidades, array $precosGoverno, array $produtosMercado): array
{
    if (empty($oportunidades['itens'])) {
        return [
            'resumo' => 'Nenhuma oportunidade encontrada.',
            'recomendacao_geral' => 'Capture mais produtos do mercado para análise.',
            'total_oportunidades' => 0
        ];
    }

    $totalOportunidades = $oportunidades['total'];
    $classificacoes = $oportunidades['classificacao'];

    $oportunidadesPositivas = $classificacoes['EXCELENTE'] +
                               $classificacoes['MUITO_BOA'] +
                               $classificacoes['BOA'];

    $percentualPositivas = $totalOportunidades > 0
        ? round(($oportunidadesPositivas / $totalOportunidades) * 100, 1)
        : 0;

    // Resumo
    if ($percentualPositivas >= 70) {
        $resumo = "Excelente cenário! {$percentualPositivas}% das oportunidades são promissoras.";
        $recomendacao = "Forte recomendação para participar. Mercado apresenta preços muito competitivos.";
    } elseif ($percentualPositivas >= 40) {
        $resumo = "Bom cenário. {$percentualPositivas}% das oportunidades são viáveis.";
        $recomendacao = "Recomendado avaliar participação focando nas melhores oportunidades.";
    } elseif ($percentualPositivas >= 20) {
        $resumo = "Cenário moderado. {$percentualPositivas}% das oportunidades são interessantes.";
        $recomendacao = "Avaliar cuidadosamente custos e logística antes de participar.";
    } else {
        $resumo = "Cenário desafiador. Apenas {$percentualPositivas}% das oportunidades são promissoras.";
        $recomendacao = "Cautela recomendada. Margens são estreitas. Considerar apenas se houver vantagens logísticas.";
    }

    return [
        'resumo' => $resumo,
        'recomendacao_geral' => $recomendacao,
        'total_oportunidades' => $totalOportunidades,
        'oportunidades_positivas' => $oportunidadesPositivas,
        'percentual_positivas' => $percentualPositivas,
        'distribuicao' => $classificacoes,
        'diferenca_preco_medio' => round(
            $precosGoverno['estatisticas']['preco_medio'] -
            $produtosMercado['estatisticas']['preco_medio'],
            2
        )
    ];
}

/**
 * Buscar CATMAT no banco local
 */
function buscarCatmatLocal(\PDO $db, string $termo): ?array
{
    try {
        // Busca simples por termo completo
        $termoLike = "%{$termo}%";

        $sql = "
            SELECT
                codigo_item,
                descricao,
                nome_resumido,
                nome_grupo,
                nome_classe,
                unidade_fornecimento
            FROM catmat
            WHERE status = 'Ativo'
                AND (descricao LIKE ? OR nome_resumido LIKE ? OR nome_grupo LIKE ?)
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$termoLike, $termoLike, $termoLike]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result) {
            return [
                'codigo_catmat' => (string)$result['codigo_item'],
                'descricao_oficial' => $result['descricao'],
                'categoria' => $result['nome_grupo'] ?? 'GERAL',
                'fonte' => 'banco_local',
                'score_similaridade' => 85.0,
                'total_consultas' => 0
            ];
        }

        return null;

    } catch (\Exception $e) {
        error_log("[buscarCatmatLocal] Erro: " . $e->getMessage());
        return null;
    }
}

/**
 * Buscar sugestões de CATMAT
 */
function buscarSugestoesCatmat(\PDO $db, string $termo, int $limite = 5): array
{
    try {
        // Extrair primeira palavra significativa
        $palavras = array_filter(explode(' ', $termo), function($p) {
            return strlen($p) >= 3;
        });

        if (empty($palavras)) {
            return [];
        }

        $primeiraPalavra = reset($palavras);
        $termoLike = "%{$primeiraPalavra}%";

        $sql = "
            SELECT
                codigo_item,
                descricao,
                nome_grupo
            FROM catmat
            WHERE status = 'Ativo'
                AND (descricao LIKE ? OR nome_resumido LIKE ? OR nome_grupo LIKE ?)
            LIMIT ?
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$termoLike, $termoLike, $termoLike, $limite]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function($r) {
            return [
                'codigo_catmat' => (string)$r['codigo_item'],
                'descricao' => $r['descricao'],
                'categoria' => $r['nome_grupo'] ?? 'GERAL'
            ];
        }, $results);

    } catch (\Exception $e) {
        error_log("[buscarSugestoesCatmat] Erro: " . $e->getMessage());
        return [];
    }
}
