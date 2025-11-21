<?php

namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

/**
 * CacheGovernoService
 *
 * Serviço para gerenciar cache de consultas à API do governo
 * Melhora performance e reduz carga na API externa
 *
 * @package App\Services
 * @version 1.0.0
 * @author Licita.pub
 */
class CacheGovernoService
{
    private PDO $db;

    /**
     * Validade padrão do cache (em dias)
     */
    private const CACHE_VALIDADE_DIAS = 7;

    /**
     * Limite máximo de registros no cache
     */
    private const MAX_CACHE_SIZE = 10000;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obter dados do cache
     *
     * @param int $codigoCatmat Código CATMAT
     * @param array $filtros Filtros aplicados (uf, limite)
     * @return array|null Dados do cache ou null se não encontrado/expirado
     */
    public function getCache(int $codigoCatmat, array $filtros = []): ?array
    {
        try {
            $hash = $this->gerarHash($codigoCatmat, $filtros);

            $stmt = $this->db->prepare("
                SELECT
                    dados_json,
                    expires_at,
                    created_at,
                    acessos,
                    total_registros
                FROM cache_precos_governo
                WHERE codigo_catmat = ?
                  AND hash_consulta = ?
                  AND expires_at > NOW()
                LIMIT 1
            ");

            $stmt->execute([$codigoCatmat, $hash]);
            $cache = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cache) {
                // Incrementar contador de acessos
                $this->incrementarAcesso($codigoCatmat, $hash);

                error_log("[CacheGoverno] Cache HIT para CATMAT {$codigoCatmat} (hash: {$hash})");

                return [
                    'dados' => json_decode($cache['dados_json'], true),
                    'expirado' => false,
                    'expires_at' => $cache['expires_at'],
                    'created_at' => $cache['created_at'],
                    'acessos' => (int)$cache['acessos'],
                    'fonte' => 'cache'
                ];
            }

            error_log("[CacheGoverno] Cache MISS para CATMAT {$codigoCatmat}");
            return null;

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao buscar cache: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Salvar dados no cache
     *
     * @param int $codigoCatmat Código CATMAT
     * @param array $dados Dados a serem cacheados
     * @param array $filtros Filtros aplicados
     * @param int $tempoConsultaMs Tempo que levou a consulta à API
     * @return bool Sucesso
     */
    public function setCache(int $codigoCatmat, array $dados, array $filtros = [], int $tempoConsultaMs = 0): bool
    {
        try {
            // Verificar limite de cache
            $this->verificarLimiteCache();

            $hash = $this->gerarHash($codigoCatmat, $filtros);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::CACHE_VALIDADE_DIAS . ' days'));

            $stmt = $this->db->prepare("
                INSERT INTO cache_precos_governo (
                    id, codigo_catmat, dados_json, total_registros,
                    hash_consulta, expires_at, acessos, ultimo_acesso,
                    uf, limite, tempo_consulta_api_ms
                ) VALUES (
                    :id, :catmat, :dados, :total,
                    :hash, :expires, 1, NOW(),
                    :uf, :limite, :tempo
                )
                ON DUPLICATE KEY UPDATE
                    dados_json = VALUES(dados_json),
                    total_registros = VALUES(total_registros),
                    expires_at = VALUES(expires_at),
                    tempo_consulta_api_ms = VALUES(tempo_consulta_api_ms),
                    created_at = NOW(),
                    acessos = 1,
                    ultimo_acesso = NOW()
            ");

            $stmt->execute([
                ':id' => $this->generateUUID(),
                ':catmat' => $codigoCatmat,
                ':dados' => json_encode($dados, JSON_UNESCAPED_UNICODE),
                ':total' => $dados['total_registros'] ?? 0,
                ':hash' => $hash,
                ':expires' => $expiresAt,
                ':uf' => $filtros['uf'] ?? null,
                ':limite' => $filtros['limite'] ?? null,
                ':tempo' => $tempoConsultaMs
            ]);

            error_log("[CacheGoverno] Cache salvo para CATMAT {$codigoCatmat} (expira em {$expiresAt})");

            return true;

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao salvar cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Incrementar contador de acessos
     *
     * @param int $codigoCatmat Código CATMAT
     * @param string $hash Hash da consulta
     */
    private function incrementarAcesso(int $codigoCatmat, string $hash): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE cache_precos_governo
                SET acessos = acessos + 1,
                    ultimo_acesso = NOW()
                WHERE codigo_catmat = ?
                  AND hash_consulta = ?
            ");

            $stmt->execute([$codigoCatmat, $hash]);

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao incrementar acesso: " . $e->getMessage());
        }
    }

    /**
     * Limpar cache expirado
     *
     * @return int Número de registros deletados
     */
    public function limparCacheExpirado(): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM cache_precos_governo
                WHERE expires_at < NOW()
            ");

            $stmt->execute();
            $deletados = $stmt->rowCount();

            error_log("[CacheGoverno] Cache expirado limpo: {$deletados} registros");

            return $deletados;

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao limpar cache expirado: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpar cache antigo (sem uso há 30+ dias)
     *
     * @return int Número de registros deletados
     */
    public function limparCacheAntigo(): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM cache_precos_governo
                WHERE ultimo_acesso < DATE_SUB(NOW(), INTERVAL 30 DAY)
                   OR (ultimo_acesso IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
            ");

            $stmt->execute();
            $deletados = $stmt->rowCount();

            error_log("[CacheGoverno] Cache antigo limpo: {$deletados} registros");

            return $deletados;

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao limpar cache antigo: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Invalidar cache de um CATMAT específico
     *
     * @param int $codigoCatmat Código CATMAT
     * @return int Número de registros invalidados
     */
    public function invalidarCache(int $codigoCatmat): int
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE cache_precos_governo
                SET expires_at = NOW()
                WHERE codigo_catmat = ?
            ");

            $stmt->execute([$codigoCatmat]);
            $invalidados = $stmt->rowCount();

            error_log("[CacheGoverno] Cache invalidado para CATMAT {$codigoCatmat}: {$invalidados} registros");

            return $invalidados;

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao invalidar cache: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpar todo o cache (usar com cautela)
     *
     * @return bool Sucesso
     */
    public function limparTodoCache(): bool
    {
        try {
            $stmt = $this->db->query("TRUNCATE TABLE cache_precos_governo");

            error_log("[CacheGoverno] Todo cache foi limpo");

            return true;

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao limpar todo cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gerar hash único da consulta
     *
     * Hash garante que consultas com filtros diferentes
     * tenham cache separado
     *
     * @param int $codigoCatmat Código CATMAT
     * @param array $filtros Filtros aplicados
     * @return string Hash SHA-256
     */
    private function gerarHash(int $codigoCatmat, array $filtros): string
    {
        // Ordenar filtros para garantir hash consistente
        ksort($filtros);

        // Remover valores vazios
        $filtros = array_filter($filtros, function($value) {
            return $value !== null && $value !== '';
        });

        $string = $codigoCatmat . '|' . json_encode($filtros);

        return hash('sha256', $string);
    }

    /**
     * Verificar e aplicar limite de cache
     *
     * Se cache ultrapassar limite, remove os menos acessados
     */
    private function verificarLimiteCache(): void
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM cache_precos_governo");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $result['total'] ?? 0;

            if ($total >= self::MAX_CACHE_SIZE) {
                // Deletar 10% dos registros menos acessados
                $deletar = (int)($total * 0.1);

                $stmt = $this->db->prepare("
                    DELETE FROM cache_precos_governo
                    ORDER BY acessos ASC, ultimo_acesso ASC
                    LIMIT ?
                ");

                $stmt->execute([$deletar]);

                error_log("[CacheGoverno] Limite atingido. Deletados {$deletar} registros menos usados");
            }

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao verificar limite: " . $e->getMessage());
        }
    }

    /**
     * Obter estatísticas do cache
     *
     * @return array Estatísticas
     */
    public function getEstatisticas(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(*) as total_registros,
                    SUM(acessos) as total_hits,
                    AVG(acessos) as media_hits,
                    SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as cache_valido,
                    SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as cache_expirado,
                    MIN(tempo_consulta_api_ms) as tempo_minimo_api,
                    AVG(tempo_consulta_api_ms) as tempo_medio_api,
                    MAX(tempo_consulta_api_ms) as tempo_maximo_api,
                    MAX(ultimo_acesso) as ultimo_uso
                FROM cache_precos_governo
            ");

            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($stats) {
                // Calcular economia de tempo
                $economiaSegundos = 0;
                if ($stats['total_hits'] && $stats['tempo_medio_api']) {
                    $economiaSegundos = ($stats['total_hits'] * $stats['tempo_medio_api']) / 1000;
                }

                $stats['economia_segundos'] = round($economiaSegundos, 2);
                $stats['economia_minutos'] = round($economiaSegundos / 60, 2);

                // Taxa de validade
                $stats['percentual_valido'] = $stats['total_registros'] > 0
                    ? round(($stats['cache_valido'] / $stats['total_registros']) * 100, 2)
                    : 0;

                return $stats;
            }

            return [];

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter top produtos cacheados
     *
     * @param int $limite Limite de resultados
     * @return array Lista de produtos mais cacheados
     */
    public function getTopProdutos(int $limite = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    c.codigo_catmat,
                    m.descricao_oficial,
                    m.categoria,
                    c.total_registros,
                    c.acessos,
                    c.ultimo_acesso,
                    DATEDIFF(c.expires_at, NOW()) as dias_ate_expirar,
                    c.tempo_consulta_api_ms
                FROM cache_precos_governo c
                LEFT JOIN mapeamento_catmat m ON c.codigo_catmat = m.codigo_catmat
                WHERE c.expires_at > NOW()
                ORDER BY c.acessos DESC
                LIMIT ?
            ");

            $stmt->execute([$limite]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("[CacheGoverno] Erro ao obter top produtos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar saúde do cache
     *
     * @return array Status da saúde
     */
    public function healthCheck(): array
    {
        $stats = $this->getEstatisticas();

        $health = [
            'status' => 'ok',
            'issues' => []
        ];

        // Verificar se há muitos expirados
        if (isset($stats['cache_expirado']) && $stats['cache_expirado'] > 100) {
            $health['issues'][] = "Muitos caches expirados ({$stats['cache_expirado']}). Executar limpeza.";
            $health['status'] = 'warning';
        }

        // Verificar taxa de validade
        if (isset($stats['percentual_valido']) && $stats['percentual_valido'] < 50) {
            $health['issues'][] = "Taxa de cache válido baixa ({$stats['percentual_valido']}%).";
            $health['status'] = 'warning';
        }

        // Verificar se está próximo do limite
        if (isset($stats['total_registros']) && $stats['total_registros'] > self::MAX_CACHE_SIZE * 0.9) {
            $health['issues'][] = "Cache próximo do limite ({$stats['total_registros']}/" . self::MAX_CACHE_SIZE . ").";
            $health['status'] = 'warning';
        }

        $health['estatisticas'] = $stats;

        return $health;
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
}
