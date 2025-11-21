<?php

namespace App\Services;

use App\Config\Env;
use App\Config\Database;

/**
 * MercadoLivreOAuthService
 *
 * Gerencia autenticação OAuth 2.0 com PKCE do Mercado Livre
 */
class MercadoLivreOAuthService
{
    private const AUTH_URL = 'https://auth.mercadolivre.com.br/authorization';
    private const TOKEN_URL = 'https://api.mercadolibre.com/oauth/token';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        Env::load();

        $this->clientId = Env::get('ML_CLIENT_ID');
        $this->clientSecret = Env::get('ML_CLIENT_SECRET');
        $this->redirectUri = Env::get('ML_REDIRECT_URI');

        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('Credenciais do Mercado Livre não configuradas');
        }
    }

    /**
     * Gerar Code Verifier para PKCE
     */
    private function generateCodeVerifier(): string
    {
        $random = bin2hex(random_bytes(32));
        return rtrim(strtr(base64_encode($random), '+/', '-_'), '=');
    }

    /**
     * Gerar Code Challenge a partir do Verifier
     */
    private function generateCodeChallenge(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);
        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    /**
     * Gerar URL de autorização (Passo 1 do OAuth)
     */
    public function getAuthorizationUrl(string $userId): string
    {
        // Gerar PKCE
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);
        $state = bin2hex(random_bytes(16));

        // Salvar code_verifier e state na sessão/banco
        $this->saveOAuthState($userId, $state, $codeVerifier);

        // Montar URL
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => $state
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    /**
     * Trocar código por access token (Passo 2 do OAuth)
     */
    public function exchangeCodeForToken(string $code, string $state, string $userId): array
    {
        // Recuperar code_verifier
        $oauthData = $this->getOAuthState($userId, $state);

        if (!$oauthData) {
            return [
                'success' => false,
                'error' => 'State inválido ou expirado'
            ];
        }

        $codeVerifier = $oauthData['code_verifier'];

        // Fazer requisição para trocar código por token
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::TOKEN_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'code_verifier' => $codeVerifier
            ]),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("[ML OAuth] Erro ao trocar código: HTTP {$httpCode} - {$response}");
            return [
                'success' => false,
                'error' => 'Erro ao obter token',
                'details' => json_decode($response, true)
            ];
        }

        $data = json_decode($response, true);

        // Salvar tokens no banco
        $this->saveTokens($userId, $data);

        // Limpar state temporário
        $this->clearOAuthState($userId, $state);

        return [
            'success' => true,
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'],
            'refresh_token' => $data['refresh_token'] ?? null
        ];
    }

    /**
     * Renovar access token usando refresh token
     */
    public function refreshAccessToken(string $userId): array
    {
        $tokens = $this->getStoredTokens($userId);

        if (!$tokens || !$tokens['refresh_token']) {
            return [
                'success' => false,
                'error' => 'Refresh token não encontrado'
            ];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::TOKEN_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $tokens['refresh_token']
            ]),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'Erro ao renovar token'
            ];
        }

        $data = json_decode($response, true);

        // Atualizar tokens
        $this->saveTokens($userId, $data);

        return [
            'success' => true,
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in']
        ];
    }

    /**
     * Obter access token válido (renova se expirado)
     */
    public function getValidAccessToken(string $userId): ?string
    {
        $tokens = $this->getStoredTokens($userId);

        if (!$tokens) {
            return null;
        }

        // Verificar se token expirou
        if (time() >= $tokens['expires_at']) {
            // Renovar token
            $result = $this->refreshAccessToken($userId);
            return $result['success'] ? $result['access_token'] : null;
        }

        return $tokens['access_token'];
    }

    /**
     * Salvar state temporário (para PKCE)
     */
    private function saveOAuthState(string $userId, string $state, string $codeVerifier): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO ml_oauth_states (user_id, state, code_verifier, created_at, expires_at)
            VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ON DUPLICATE KEY UPDATE
                state = VALUES(state),
                code_verifier = VALUES(code_verifier),
                created_at = VALUES(created_at),
                expires_at = VALUES(expires_at)
        ");

        $stmt->execute([$userId, $state, $codeVerifier]);
    }

    /**
     * Recuperar state temporário
     */
    private function getOAuthState(string $userId, string $state): ?array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM ml_oauth_states
            WHERE user_id = ? AND state = ? AND expires_at > NOW()
        ");

        $stmt->execute([$userId, $state]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Limpar state temporário
     */
    private function clearOAuthState(string $userId, string $state): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM ml_oauth_states WHERE user_id = ? AND state = ?");
        $stmt->execute([$userId, $state]);
    }

    /**
     * Salvar tokens no banco
     */
    private function saveTokens(string $userId, array $tokenData): void
    {
        $db = Database::getConnection();

        $expiresAt = time() + ($tokenData['expires_in'] ?? 21600); // 6 horas padrão

        $stmt = $db->prepare("
            INSERT INTO ml_oauth_tokens (user_id, access_token, refresh_token, expires_at, updated_at)
            VALUES (?, ?, ?, FROM_UNIXTIME(?), NOW())
            ON DUPLICATE KEY UPDATE
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                expires_at = VALUES(expires_at),
                updated_at = VALUES(updated_at)
        ");

        $stmt->execute([
            $userId,
            $tokenData['access_token'],
            $tokenData['refresh_token'] ?? null,
            $expiresAt
        ]);
    }

    /**
     * Recuperar tokens armazenados
     */
    private function getStoredTokens(string $userId): ?array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT access_token, refresh_token, UNIX_TIMESTAMP(expires_at) as expires_at
            FROM ml_oauth_tokens
            WHERE user_id = ?
        ");

        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Verificar se usuário tem autorização ativa
     */
    public function hasValidAuthorization(string $userId): bool
    {
        return $this->getValidAccessToken($userId) !== null;
    }
}
