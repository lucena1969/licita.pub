<?php

namespace App\Middleware;

use App\Services\LimiteService;
use App\Services\AuthService;
use App\Config\Database;

/**
 * Middleware: LimiteConsultaMiddleware
 *
 * Verifica se usuário/IP pode fazer consulta detalhada
 * - Retorna 429 (Too Many Requests) se excedeu limite
 * - Adiciona headers de rate limiting
 * - Passa informações de limite para o request
 */
class LimiteConsultaMiddleware
{
    private LimiteService $limiteService;
    private AuthService $authService;

    public function __construct()
    {
        $db = Database::getInstance()->getConnection();
        $this->limiteService = new LimiteService($db);
        $this->authService = new AuthService($db);
    }

    /**
     * Handle do middleware
     *
     * @param object $request Objeto de request
     * @param callable $next Próximo handler na pipeline
     * @return mixed
     */
    public function handle(object $request, callable $next)
    {
        // 1. Obter IP do cliente
        $ip = LimiteService::getClientIP();

        // 2. Verificar se usuário está autenticado
        $usuario = null;
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $sessao = $this->authService->verificarSessao($token);
            if ($sessao) {
                $usuario = $sessao['usuario'];
            }
        }

        // 3. Verificar limite
        $resultado = $this->limiteService->verificarLimite($usuario, $ip);

        // 4. Adicionar headers de rate limiting
        $this->adicionarRateLimitHeaders($resultado);

        // 5. Se não permitido, retornar 429
        if (!$resultado['permitido']) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'LIMITE_EXCEDIDO',
                'message' => $resultado['mensagem'],
                'limite' => $resultado['info']
            ]);
            exit;
        }

        // 6. Adicionar informações ao request para uso posterior
        $request->limite = $resultado;
        $request->usuario = $usuario;
        $request->ip = $ip;

        // 7. Continuar pipeline
        return $next($request);
    }

    /**
     * Adicionar headers de rate limiting (padrão RFC 6585)
     */
    private function adicionarRateLimitHeaders(array $resultado): void
    {
        // Total de consultas permitidas por dia
        $limite = $resultado['info']['limite_diario'] ?? 0;

        // Consultas restantes
        $restantes = $resultado['restantes'];

        // Tempo para reset (em segundos)
        $reset = $resultado['info']['tempo_restante_segundos'] ?? 0;

        // Headers padrão
        header("X-RateLimit-Limit: $limite");
        header("X-RateLimit-Remaining: $restantes");

        if ($reset > 0) {
            $resetTimestamp = time() + $reset;
            header("X-RateLimit-Reset: $resetTimestamp");
        }

        // Headers adicionais (informacionais)
        header("X-RateLimit-Type: {$resultado['tipo']}");

        if (!empty($resultado['info']['tempo_restante_formatado'])) {
            header("X-RateLimit-Reset-Formatted: {$resultado['info']['tempo_restante_formatado']}");
        }
    }

    /**
     * Middleware estático para uso rápido
     */
    public static function check(): void
    {
        $middleware = new self();
        $request = new \stdClass();

        $middleware->handle($request, function($req) {
            // Continuar execução
            return $req;
        });
    }

    /**
     * Verificar limite sem bloquear (apenas retornar info)
     * Útil para endpoints que querem exibir info de limite sem consumir
     */
    public static function getInfo(): array
    {
        $db = Database::getInstance()->getConnection();
        $limiteService = new LimiteService($db);
        $authService = new AuthService($db);

        $ip = LimiteService::getClientIP();
        $usuario = null;

        // Verificar autenticação
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $sessao = $authService->verificarSessao($token);
            if ($sessao) {
                $usuario = $sessao['usuario'];
            }
        }

        return $limiteService->verificarLimite($usuario, $ip);
    }
}
