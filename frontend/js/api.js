/**
 * Serviço de API - Licita.pub
 * Cliente para comunicação com a API REST
 */

class ApiService {
    constructor() {
        // Ajustar baseURL conforme ambiente
        this.baseURL = this.getBaseURL();
    }

    /**
     * Obter URL base da API
     */
    getBaseURL() {
        const hostname = window.location.hostname;

        // Produção
        if (hostname === 'licita.pub' || hostname === 'www.licita.pub') {
            return 'https://licita.pub/backend/api';
        }

        // Desenvolvimento local
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            return 'http://localhost/backend/api';
        }

        // Fallback
        return '/backend/api';
    }

    /**
     * Fazer requisição HTTP
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;

        const config = {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            },
            credentials: 'include', // Importante para enviar/receber cookies
        };

        // Adicionar body se for POST/PUT
        if (options.body) {
            config.body = JSON.stringify(options.body);
        }

        // Adicionar token de autenticação se existir
        const sessionId = this.getSessionId();
        if (sessionId) {
            config.headers['Authorization'] = `Bearer ${sessionId}`;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            // Salvar session_id se vier na resposta
            if (data.session_id) {
                this.saveSessionId(data.session_id);
            }

            return {
                success: response.ok,
                status: response.status,
                data: data,
            };
        } catch (error) {
            console.error('Erro na requisição:', error);
            return {
                success: false,
                status: 0,
                data: {
                    success: false,
                    errors: ['Erro ao conectar com o servidor. Tente novamente.'],
                },
            };
        }
    }

    /**
     * Obter session_id do localStorage
     */
    getSessionId() {
        return localStorage.getItem('session_id');
    }

    /**
     * Salvar session_id no localStorage
     */
    saveSessionId(sessionId) {
        localStorage.setItem('session_id', sessionId);
    }

    /**
     * Remover session_id do localStorage
     */
    removeSessionId() {
        localStorage.removeItem('session_id');
    }

    // ==================== ENDPOINTS DE AUTENTICAÇÃO ====================

    /**
     * Registrar novo usuário
     */
    async register(userData) {
        return await this.request('/auth/register.php', {
            method: 'POST',
            body: userData,
        });
    }

    /**
     * Login
     */
    async login(email, senha) {
        return await this.request('/auth/login.php', {
            method: 'POST',
            body: { email, senha },
        });
    }

    /**
     * Obter dados do usuário autenticado
     */
    async me() {
        return await this.request('/auth/me.php');
    }

    /**
     * Logout
     */
    async logout() {
        const response = await this.request('/auth/logout.php', {
            method: 'POST',
        });

        // Remover session_id do localStorage
        this.removeSessionId();

        return response;
    }

    // ==================== ENDPOINTS DE LICITAÇÕES ====================
    // TODO: Implementar quando endpoints estiverem prontos

    /**
     * Listar licitações
     */
    async listarLicitacoes(filtros = {}) {
        const params = new URLSearchParams(filtros);
        return await this.request(`/licitacoes/listar.php?${params}`);
    }

    /**
     * Buscar licitação por ID
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
