/**
 * Serviço de API - Licita.pub
 * Cliente responsável por centralizar a comunicação com a API REST
 */

class ApiService {
    constructor() {
        this.storageKeys = {
            session: 'session_id',
        };

        this.manualOverride = this.detectManualOverride();
        this.baseURL = this.manualOverride || this.deriveBaseURL();
    }

    /**
     * Detecta uma URL base definida manualmente no ambiente do navegador.
     */
    detectManualOverride() {
        if (typeof window === 'undefined') {
            return null;
        }

        const overrides = [
            window.API_BASE_URL,
            window.__API_BASE_URL__,
            window.APP_API_BASE_URL,
        ];

        for (const candidate of overrides) {
            const normalized = this.normalizeBase(candidate);
            if (normalized) {
                return normalized;
            }
        }

        return null;
    }

    /**
     * Remove espaços excedentes e barras finais para manter a URL consistente.
     */
    normalizeBase(value) {
        if (!value || typeof value !== 'string') {
            return null;
        }

        const trimmed = value.trim();
        if (!trimmed) {
            return null;
        }

        return trimmed.replace(/\/+$/, '');
    }

    /**
     * Deriva a URL base com base no ambiente atual.
     */
    deriveBaseURL() {
        if (typeof window === 'undefined' || !window.location) {
            return '/api';
        }

        const { origin, protocol, hostname, port, pathname } = window.location;

        // Detectar localhost e ajustar caminho
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            // Para localhost XAMPP
            // Se estamos em /licita.pub/public_html/frontend/*, usar caminho relativo ao backend
            if (pathname.includes('/licita.pub/public_html/frontend/')) {
                return this.normalizeBase(`${origin}/licita.pub/public_html/backend/public/api`) || '/api';
            }
            // Fallback genérico para localhost
            return this.normalizeBase(`${origin}/backend/public/api`) || '/api';
        }

        if (origin && origin !== 'null') {
            return this.normalizeBase(`${origin}/api`) || '/api';
        }

        if (hostname) {
            const scheme = protocol || 'https:';
            const portSuffix = port ? `:${port}` : '';
            return this.normalizeBase(`${scheme}//${hostname}${portSuffix}/api`) || '/api';
        }

        return '/api';
    }

    /**
     * Permite ajustar manualmente a base utilizada nas próximas requisições.
     */
    setBaseURL(baseURL) {
        this.baseURL = this.normalizeBase(baseURL) || this.baseURL;
    }

    /**
     * Lista de bases candidatas que podem responder às requisições.
     */
    getCandidateBases() {
        const candidates = [];
        const pushUnique = (value) => {
            const normalized = this.normalizeBase(value);
            if (normalized && !candidates.includes(normalized)) {
                candidates.push(normalized);
            }
        };

        pushUnique(this.manualOverride);
        pushUnique(this.baseURL);
        pushUnique(this.deriveBaseURL());
        pushUnique('/api');
        pushUnique('/backend/public/api');

        return candidates;
    }

    /**
     * Monta a URL final considerando a base e o endpoint informado.
     */
    buildURL(base, endpoint) {
        const normalizedBase = this.normalizeBase(base) || '/api';
        const path = typeof endpoint === 'string' ? endpoint.trim() : '';
        const sanitizedPath = path.startsWith('/') ? path : `/${path}`;
        return `${normalizedBase}${sanitizedPath}`;
    }

    /**
     * Define a configuração padrão do fetch considerando método, headers e corpo.
     */
    createFetchConfig(options = {}) {
        const headers = {
            Accept: 'application/json, text/plain, */*',
            ...(options.headers || {}),
        };

        const config = {
            method: options.method ? options.method.toUpperCase() : 'GET',
            headers,
            credentials: options.credentials || 'include',
        };

        if (options.body !== undefined && options.body !== null) {
            if (options.body instanceof FormData) {
                config.body = options.body;
                delete headers['Content-Type'];
            } else if (typeof options.body === 'string') {
                config.body = options.body;
                if (!headers['Content-Type']) {
                    headers['Content-Type'] = 'application/json';
                }
            } else {
                config.body = JSON.stringify(options.body);
                if (!headers['Content-Type']) {
                    headers['Content-Type'] = 'application/json';
                }
            }
        } else if (config.method !== 'GET' && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        return config;
    }

    /**
     * Executa a requisição tentando múltiplas bases até encontrar uma válida.
     */
    async request(endpoint, options = {}) {
        const candidates = this.getCandidateBases();
        const errors = [];
        let sessionId = this.getSessionId();

        for (const base of candidates) {
            const url = this.buildURL(base, endpoint);
            const config = this.createFetchConfig(options);

            if (sessionId) {
                config.headers['Authorization'] = `Bearer ${sessionId}`;
            }

            try {
                const response = await fetch(url, config);
                const payload = await this.parseResponse(response);

                if (payload && payload.session_id) {
                    this.saveSessionId(payload.session_id);
                    sessionId = payload.session_id;
                }

                if (response.status === 401 || response.status === 403) {
                    this.removeSessionId();
                    sessionId = null;
                }

                if (response.status === 404 || response.status === 0) {
                    errors.push({ base, status: response.status });
                    continue;
                }

                this.baseURL = this.normalizeBase(base) || this.baseURL;
                return {
                    success: response.ok,
                    status: response.status,
                    data: payload,
                };
            } catch (error) {
                errors.push({ base, error: error?.message || error });
            }
        }

        console.error('Falha ao comunicar com a API:', errors);
        return this.buildNetworkErrorResult();
    }

    /**
     * Resposta padrão para falhas de comunicação.
     */
    buildNetworkErrorResult() {
        return {
            success: false,
            status: 0,
            data: {
                success: false,
                errors: ['Erro ao conectar com o servidor. Tente novamente.'],
            },
        };
    }

    /**
     * Faz o parse seguro da resposta HTTP.
     */
    async parseResponse(response) {
        const defaultError = {
            success: false,
            errors: ['Resposta inválida do servidor.'],
        };

        try {
            if (response.status === 204) {
                return { success: true };
            }

            const contentType = response.headers.get('Content-Type') || '';
            if (contentType.includes('application/json')) {
                return await response.json();
            }

            const text = await response.text();
            if (!text) {
                return response.ok ? { success: true } : defaultError;
            }

            try {
                return JSON.parse(text);
            } catch (parseError) {
                return {
                    ...defaultError,
                    raw: text,
                };
            }
        } catch (error) {
            console.warn('Não foi possível interpretar a resposta da API:', error);
            return defaultError;
        }
    }

    /**
     * Obtém o session_id armazenado.
     */
    getSessionId() {
        try {
            return localStorage.getItem(this.storageKeys.session);
        } catch (error) {
            console.warn('Não foi possível acessar o localStorage:', error);
            return null;
        }
    }

    /**
     * Persiste o session_id.
     */
    saveSessionId(sessionId) {
        if (!sessionId) {
            return;
        }

        try {
            localStorage.setItem(this.storageKeys.session, sessionId);
        } catch (error) {
            console.warn('Não foi possível salvar session_id:', error);
        }
    }

    /**
     * Remove o session_id armazenado.
     */
    removeSessionId() {
        try {
            localStorage.removeItem(this.storageKeys.session);
        } catch (error) {
            console.warn('Não foi possível remover session_id:', error);
        }
    }

    // ==================== ENDPOINTS DE AUTENTICAÇÃO ====================

    /**
     * Registrar novo usuário.
     */
    async register(userData) {
        return await this.request('/auth/register.php', {
            method: 'POST',
            body: userData,
        });
    }

    /**
     * Login.
     */
    async login(email, senha) {
        return await this.request('/auth/login.php', {
            method: 'POST',
            body: { email, senha },
        });
    }

    /**
     * Dados do usuário autenticado.
     */
    async me() {
        return await this.request('/auth/me.php');
    }

    /**
     * Logout.
     */
    async logout() {
        const response = await this.request('/auth/logout.php', {
            method: 'POST',
        });

        this.removeSessionId();
        return response;
    }

    // ==================== ENDPOINTS DE LICITAÇÕES ====================

    /**
     * Listar licitações.
     */
    async listarLicitacoes(filtros = {}) {
        const params = new URLSearchParams(filtros);
        const endpoint = params.toString()
            ? `/licitacoes/listar.php?${params.toString()}`
            : '/licitacoes/listar.php';

        return await this.request(endpoint);
    }

    /**
     * Buscar licitação por ID.
     */
    async buscarLicitacao(id) {
        return await this.request(`/licitacoes/detalhes.php?id=${id}`);
    }
}

// Instância global do serviço
const api = new ApiService();

// Exportar para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiService;
}