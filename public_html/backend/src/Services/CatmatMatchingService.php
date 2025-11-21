<?php

namespace App\Services;

use App\Config\Database;
use App\Services\ComprasGovAPIService;
use App\Services\KeywordsExtractionService;
use PDO;
use Exception;

/**
 * CatmatMatchingService
 *
 * Serviço para encontrar código CATMAT correspondente a uma descrição de produto
 * Utiliza cache local e API do governo para matching inteligente
 *
 * @package App\Services
 * @version 1.0.0
 * @author Licita.pub
 */
class CatmatMatchingService
{
    private PDO $db;
    private KeywordsExtractionService $keywordsService;
    private ComprasGovAPIService $apiService;

    /**
     * Threshold mínimo de similaridade para aceitar match
     */
    private const MIN_SIMILARITY_SCORE = 0.7;

    /**
     * Limite de resultados na busca
     */
    private const MAX_RESULTS = 10;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->keywordsService = new KeywordsExtractionService();
        $this->apiService = new ComprasGovAPIService();
    }

    /**
     * Encontrar código CATMAT para uma descrição de produto
     *
     * Fluxo:
     * 1. Buscar em cache local (mapeamento_catmat)
     * 2. Se não encontrado, buscar na API do catálogo
     * 3. Salvar resultado em cache
     * 4. Retornar código CATMAT e metadados
     *
     * @param string $descricao Descrição do produto
     * @param array $opcoes Opções de busca (force_api, min_score)
     * @return array|null Código CATMAT e metadados ou null se não encontrado
     */
    public function encontrarCodigoCatmat(string $descricao, array $opcoes = []): ?array
    {
        $descricao = trim($descricao);

        if (strlen($descricao) < 3) {
            error_log("[CatmatMatching] Descrição muito curta: {$descricao}");
            return null;
        }

        error_log("[CatmatMatching] Buscando CATMAT para: {$descricao}");

        // Opções padrão
        $forceAPI = $opcoes['force_api'] ?? false;
        $minScore = $opcoes['min_score'] ?? self::MIN_SIMILARITY_SCORE;

        // ETAPA 1: Tentar cache local (se não forçar API)
        if (!$forceAPI) {
            $cacheResult = $this->buscarEmCache($descricao, $minScore);

            if ($cacheResult) {
                error_log("[CatmatMatching] Encontrado em cache: CATMAT {$cacheResult['codigo_catmat']}");
                return $cacheResult;
            }
        }

        // ETAPA 2: Buscar na API do catálogo
        try {
            $apiResult = $this->buscarNoCatalogo($descricao);

            if ($apiResult) {
                // Salvar em cache para próximas consultas
                $this->salvarMapeamento($apiResult['codigo_catmat'], $descricao, $apiResult);

                error_log("[CatmatMatching] Encontrado via API: CATMAT {$apiResult['codigo_catmat']}");
                return $apiResult;
            }

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao buscar via API: " . $e->getMessage());
        }

        // ETAPA 3: Busca fuzzy em cache (caso API falhe)
        $fuzzyResult = $this->buscaFuzzyEmCache($descricao, $minScore * 0.8); // Score mais permissivo

        if ($fuzzyResult) {
            error_log("[CatmatMatching] Encontrado via busca fuzzy: CATMAT {$fuzzyResult['codigo_catmat']}");
            return $fuzzyResult;
        }

        error_log("[CatmatMatching] Nenhum código CATMAT encontrado para: {$descricao}");
        return null;
    }

    /**
     * Buscar em cache local usando FULLTEXT search
     *
     * @param string $descricao Descrição do produto
     * @param float $minScore Score mínimo
     * @return array|null Resultado do cache
     */
    private function buscarEmCache(string $descricao, float $minScore = 0.7): ?array
    {
        try {
            // Extrair keywords para melhor busca
            $keywordsData = $this->keywordsService->extrairKeywords($descricao, 5);
            $keywords = $keywordsData['keywords'];

            error_log("[CatmatMatching] Keywords extraídas: {$keywords}");

            // Busca FULLTEXT com score
            $stmt = $this->db->prepare("
                SELECT
                    codigo_catmat,
                    descricao_oficial,
                    categoria,
                    subcategoria,
                    palavras_chave,
                    sinonimos,
                    total_consultas,
                    preco_medio_governo,
                    confiabilidade_mapping,
                    MATCH(descricao_oficial, palavras_chave, sinonimos)
                        AGAINST(:keywords IN BOOLEAN MODE) as score
                FROM mapeamento_catmat
                WHERE MATCH(descricao_oficial, palavras_chave, sinonimos)
                    AGAINST(:keywords IN BOOLEAN MODE)
                HAVING score >= :min_score
                ORDER BY score DESC, total_consultas DESC, confiabilidade_mapping DESC
                LIMIT 1
            ");

            $stmt->execute([
                ':keywords' => $keywords,
                ':min_score' => $minScore
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Incrementar contador de consultas
                $this->incrementarContador($result['codigo_catmat']);

                return [
                    'codigo_catmat' => (int)$result['codigo_catmat'],
                    'descricao_oficial' => $result['descricao_oficial'],
                    'categoria' => $result['categoria'],
                    'subcategoria' => $result['subcategoria'],
                    'score_similaridade' => (float)$result['score'],
                    'fonte' => 'cache',
                    'total_consultas' => (int)$result['total_consultas'],
                    'preco_medio_governo' => $result['preco_medio_governo'] ? (float)$result['preco_medio_governo'] : null,
                    'confiabilidade' => (float)$result['confiabilidade_mapping']
                ];
            }

            return null;

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao buscar em cache: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca fuzzy quando busca exata falha
     *
     * @param string $descricao Descrição
     * @param float $minScore Score mínimo
     * @return array|null Resultado
     */
    private function buscaFuzzyEmCache(string $descricao, float $minScore = 0.5): ?array
    {
        try {
            // Extrair palavras individuais
            $palavras = preg_split('/\s+/', strtolower($descricao));
            $palavras = array_filter($palavras, fn($p) => strlen($p) >= 3);

            if (empty($palavras)) {
                return null;
            }

            // Buscar produtos que contenham ao menos uma palavra
            $placeholders = implode(',', array_fill(0, count($palavras), '?'));

            $stmt = $this->db->prepare("
                SELECT
                    codigo_catmat,
                    descricao_oficial,
                    categoria,
                    subcategoria,
                    total_consultas,
                    confiabilidade_mapping
                FROM mapeamento_catmat
                WHERE descricao_oficial LIKE CONCAT('%', ?, '%')
                   OR palavras_chave LIKE CONCAT('%', ?, '%')
                ORDER BY total_consultas DESC, confiabilidade_mapping DESC
                LIMIT 5
            ");

            $stmt->execute([$palavras[0], $palavras[0]]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) {
                return null;
            }

            // Calcular similaridade manual
            $melhorMatch = null;
            $melhorScore = 0;

            foreach ($results as $result) {
                $score = $this->calcularSimilaridade($descricao, $result['descricao_oficial']);

                if ($score > $melhorScore && $score >= $minScore) {
                    $melhorScore = $score;
                    $melhorMatch = $result;
                }
            }

            if ($melhorMatch) {
                $this->incrementarContador($melhorMatch['codigo_catmat']);

                return [
                    'codigo_catmat' => (int)$melhorMatch['codigo_catmat'],
                    'descricao_oficial' => $melhorMatch['descricao_oficial'],
                    'categoria' => $melhorMatch['categoria'],
                    'subcategoria' => $melhorMatch['subcategoria'],
                    'score_similaridade' => $melhorScore,
                    'fonte' => 'cache_fuzzy',
                    'total_consultas' => (int)$melhorMatch['total_consultas']
                ];
            }

            return null;

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro na busca fuzzy: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar no catálogo oficial via API
     *
     * @param string $descricao Descrição do produto
     * @return array|null Resultado da API
     */
    private function buscarNoCatalogo(string $descricao): ?array
    {
        try {
            error_log("[CatmatMatching] Buscando no catálogo oficial via API: {$descricao}");

            // Extrair keywords para busca mais precisa
            $keywordsData = $this->keywordsService->extrairKeywords($descricao, 3);
            $termoBusca = $keywordsData['keywords'];

            // Buscar via API
            $resultado = $this->apiService->buscarCatalogo($termoBusca, 10);

            if (!$resultado['success'] || empty($resultado['itens'])) {
                error_log("[CatmatMatching] API não retornou resultados");
                return null;
            }

            $itens = $resultado['itens'];

            // Calcular similaridade para cada item
            $melhorMatch = null;
            $melhorScore = 0;

            foreach ($itens as $item) {
                if (empty($item['codigo_catmat']) || empty($item['descricao'])) {
                    continue;
                }

                $score = $this->calcularSimilaridade($descricao, $item['descricao']);

                if ($score > $melhorScore) {
                    $melhorScore = $score;
                    $melhorMatch = $item;
                }
            }

            if ($melhorMatch && $melhorScore >= self::MIN_SIMILARITY_SCORE) {
                return [
                    'codigo_catmat' => (int)$melhorMatch['codigo_catmat'],
                    'descricao_oficial' => $melhorMatch['descricao'],
                    'categoria' => $melhorMatch['categoria'] ?? 'Geral',
                    'subcategoria' => null,
                    'score_similaridade' => $melhorScore,
                    'fonte' => 'api',
                    'total_consultas' => 0
                ];
            }

            return null;

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao buscar no catálogo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Salvar mapeamento em cache para futuras consultas
     *
     * @param int $codigoCatmat Código CATMAT
     * @param string $descricaoOriginal Descrição original buscada
     * @param array $metadados Dados adicionais
     */
    private function salvarMapeamento(int $codigoCatmat, string $descricaoOriginal, array $metadados): void
    {
        try {
            // Extrair keywords da descrição original
            $keywordsData = $this->keywordsService->extrairKeywords($descricaoOriginal, 10);

            // Verificar se já existe
            $stmt = $this->db->prepare("
                SELECT id FROM mapeamento_catmat
                WHERE codigo_catmat = ?
                LIMIT 1
            ");
            $stmt->execute([$codigoCatmat]);
            $existe = $stmt->fetch();

            if ($existe) {
                // Atualizar keywords adicionando novas
                $this->atualizarKeywords($codigoCatmat, $keywordsData['keywords']);
                return;
            }

            // Inserir novo mapeamento
            $stmt = $this->db->prepare("
                INSERT INTO mapeamento_catmat (
                    id,
                    codigo_catmat,
                    descricao_oficial,
                    categoria,
                    subcategoria,
                    palavras_chave,
                    confiabilidade_mapping,
                    created_at
                ) VALUES (
                    :id,
                    :codigo,
                    :descricao,
                    :categoria,
                    :subcategoria,
                    :keywords,
                    :confiabilidade,
                    NOW()
                )
            ");

            $uuid = $this->generateUUID();

            $stmt->execute([
                ':id' => $uuid,
                ':codigo' => $codigoCatmat,
                ':descricao' => $metadados['descricao_oficial'] ?? $descricaoOriginal,
                ':categoria' => $metadados['categoria'] ?? 'Geral',
                ':subcategoria' => $metadados['subcategoria'] ?? null,
                ':keywords' => $keywordsData['keywords'],
                ':confiabilidade' => $metadados['score_similaridade'] ?? 1.0
            ]);

            error_log("[CatmatMatching] Mapeamento salvo: CATMAT {$codigoCatmat}");

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao salvar mapeamento: " . $e->getMessage());
        }
    }

    /**
     * Atualizar keywords de um CATMAT existente
     *
     * @param int $codigoCatmat Código CATMAT
     * @param string $novasKeywords Novas keywords
     */
    private function atualizarKeywords(int $codigoCatmat, string $novasKeywords): void
    {
        try {
            // Buscar keywords atuais
            $stmt = $this->db->prepare("
                SELECT palavras_chave FROM mapeamento_catmat
                WHERE codigo_catmat = ?
            ");
            $stmt->execute([$codigoCatmat]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return;
            }

            $keywordsAtuais = $result['palavras_chave'] ?? '';

            // Mesclar keywords (evitar duplicatas)
            $arrayAtuais = array_filter(explode(' ', strtolower($keywordsAtuais)));
            $arrayNovas = array_filter(explode(' ', strtolower($novasKeywords)));

            $keywordsMerged = array_unique(array_merge($arrayAtuais, $arrayNovas));
            $keywordsFinal = implode(' ', $keywordsMerged);

            // Atualizar
            $stmt = $this->db->prepare("
                UPDATE mapeamento_catmat
                SET palavras_chave = ?,
                    updated_at = NOW()
                WHERE codigo_catmat = ?
            ");

            $stmt->execute([$keywordsFinal, $codigoCatmat]);

            error_log("[CatmatMatching] Keywords atualizadas para CATMAT {$codigoCatmat}");

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao atualizar keywords: " . $e->getMessage());
        }
    }

    /**
     * Incrementar contador de consultas
     *
     * @param int $codigoCatmat Código CATMAT
     */
    private function incrementarContador(int $codigoCatmat): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE mapeamento_catmat
                SET total_consultas = total_consultas + 1,
                    ultima_consulta = NOW()
                WHERE codigo_catmat = ?
            ");

            $stmt->execute([$codigoCatmat]);

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao incrementar contador: " . $e->getMessage());
        }
    }

    /**
     * Calcular similaridade entre duas strings
     *
     * Usa algoritmo combinado:
     * - Similar_text (30%)
     * - Levenshtein (30%)
     * - Palavras em comum (40%)
     *
     * @param string $str1 Primeira string
     * @param string $str2 Segunda string
     * @return float Score de 0.0 a 1.0
     */
    public function calcularSimilaridade(string $str1, string $str2): float
    {
        $str1 = mb_strtolower(trim($str1));
        $str2 = mb_strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        // 1. Similar_text (30%)
        similar_text($str1, $str2, $percentSimilar);
        $scoreSimilar = $percentSimilar / 100;

        // 2. Levenshtein (30%) - normalizado
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen > 0) {
            $levenshtein = levenshtein(substr($str1, 0, 255), substr($str2, 0, 255));
            $scoreLevenshtein = 1 - ($levenshtein / $maxLen);
        } else {
            $scoreLevenshtein = 0;
        }

        // 3. Palavras em comum (40%)
        $palavras1 = array_filter(explode(' ', preg_replace('/[^a-z0-9\s]/u', '', $str1)));
        $palavras2 = array_filter(explode(' ', preg_replace('/[^a-z0-9\s]/u', '', $str2)));

        if (empty($palavras1) || empty($palavras2)) {
            $scorePalavras = 0;
        } else {
            $comuns = count(array_intersect($palavras1, $palavras2));
            $total = max(count($palavras1), count($palavras2));
            $scorePalavras = $comuns / $total;
        }

        // Score final ponderado
        $scoreFinal = ($scoreSimilar * 0.3) + ($scoreLevenshtein * 0.3) + ($scorePalavras * 0.4);

        return round($scoreFinal, 4);
    }

    /**
     * Buscar múltiplos códigos CATMAT (sugestões)
     *
     * @param string $descricao Descrição
     * @param int $limite Limite de resultados
     * @return array Lista de sugestões
     */
    public function buscarSugestoes(string $descricao, int $limite = 5): array
    {
        try {
            $keywordsData = $this->keywordsService->extrairKeywords($descricao, 5);
            $keywords = $keywordsData['keywords'];

            $stmt = $this->db->prepare("
                SELECT
                    codigo_catmat,
                    descricao_oficial,
                    categoria,
                    total_consultas,
                    MATCH(descricao_oficial, palavras_chave, sinonimos)
                        AGAINST(:keywords IN BOOLEAN MODE) as score
                FROM mapeamento_catmat
                WHERE MATCH(descricao_oficial, palavras_chave, sinonimos)
                    AGAINST(:keywords IN BOOLEAN MODE)
                ORDER BY score DESC, total_consultas DESC
                LIMIT :limite
            ");

            $stmt->bindValue(':keywords', $keywords, PDO::PARAM_STR);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao buscar sugestões: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gerar UUID v4
     *
     * @return string UUID
     */
    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Obter estatísticas do serviço
     *
     * @return array Estatísticas
     */
    public function getEstatisticas(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(*) as total_produtos,
                    SUM(total_consultas) as total_consultas,
                    AVG(preco_medio_governo) as preco_medio_geral,
                    COUNT(DISTINCT categoria) as total_categorias,
                    MAX(ultima_consulta) as ultima_consulta
                FROM mapeamento_catmat
            ");

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            error_log("[CatmatMatching] Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }
}
