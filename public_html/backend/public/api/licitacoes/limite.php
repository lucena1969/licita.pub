<?php
/**
 * GET /api/licitacoes/limite.php
 *
 * Verifica limite disponível SEM consumir
 * - Útil para exibir contador no frontend
 * - Não requer autenticação (mas se autenticado, mostra limite do usuário)
 *
 * Retorna:
 * - tipo: ANONIMO|FREE|PREMIUM
 * - limite_diario: número total de consultas permitidas
 * - consultas_hoje: quantas consultas já fez hoje
 * - restantes: quantas consultas ainda pode fazer
 * - atingiu_limite: boolean
 * - tempo_restante_formatado: "Renova em Xh Ymin"
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Middleware\LimiteConsultaMiddleware;

// Validar método
validateMethod('GET');

// Obter informações de limite (sem consumir)
$limiteInfo = LimiteConsultaMiddleware::getInfo();

// Retornar resposta
jsonResponse([
    'success' => true,
    'data' => $limiteInfo
]);
