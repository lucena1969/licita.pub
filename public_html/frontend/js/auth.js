/**
 * Serviço de Autenticação - Licita.pub
 * Gerenciamento de autenticacao e estado do usuario
 */

class AuthService {
    constructor() {
        this.usuario = null;
        // Nao carregar usuario automaticamente - só quando houver session_id
        if (this.getToken()) {
            this.carregarUsuario();
        }
    }

    /**
     * Obter token de autenticacao do localStorage ou cookie
     */
    getToken() {
        // Verificar localStorage primeiro
        const sessionId = localStorage.getItem('session_id');
        if (sessionId) {
            return sessionId;
        }

        // Verificar cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'session_id') {
                return value;
            }
        }

        return null;
    }

    /**
     * Verificar se usuario está autenticado
     */
    isAuthenticated() {
        // Considerar token armazenado ou usuario ja carregado
        return this.getToken() !== null || !!this.usuario;
    }

    /**
     * Obter usuario atual
     */
    getUsuario() {
        return this.usuario;
    }

    /**
     * Carregar dados do usuario
     */
    async carregarUsuario() {
        try {
            const response = await api.me();

            if (response.success && response.data?.success && response.data?.usuario) {
                this.usuario = response.data.usuario;
                return true;
            }

            if (response.status === 401 || response.status === 403) {
                this.usuario = null;
                api.removeSessionId();
                return false;
            }

            this.usuario = null;
            return false;
        } catch (error) {
            // Erro ao carregar usuario e silencioso (nao logado ainda)
            this.usuario = null;
            return false;
        }
    }

    /**
     * Registrar novo usuario
     */
    async register(userData) {
        const response = await api.register(userData);

        if (response.success && response.data.success) {
            // Após registro bem-sucedido, fazer login automático
            const loginResponse = await this.login(userData.email, userData.senha);
            return loginResponse;
        }

        return response;
    }

    /**
     * Login
     */
    async login(email, senha) {
        const response = await api.login(email, senha);

        // response = {success: bool, status: number, data: {success: bool, usuario: {}, session_id: string}}
        if (response.success && response.data) {
            // Salvar session_id ja foi feito pelo api.js automaticamente
            if (response.data.usuario) {
                this.usuario = response.data.usuario;
            }

            return {
                success: true,
                usuario: this.usuario,
                session_id: response.data.session_id,
                data: response.data
            };
        }

        return {
            success: false,
            data: response.data,
            errors: response.data?.errors || ['Erro ao fazer login']
        };
    }

    /**
     * Logout
     */
    async logout() {
        await api.logout();
        this.usuario = null;

        // Redirecionar para home
        window.location.href = '/';
    }

    /**
     * Redirecionar se nao autenticado
     */
    async requireAuth() {
        if (this.isAuthenticated()) {
            return true;
        }

        const carregado = await this.carregarUsuario();

        if (!carregado) {
            window.location.href = '/frontend/login.html';
            return false;
        }

        return true;
    }

    /**
     * Redirecionar se ja autenticado
     */
    async redirectIfAuthenticated(redirectTo = '/frontend/consultas.html') {
        if (this.isAuthenticated()) {
            window.location.href = redirectTo;
            return true;
        }

        const carregado = await this.carregarUsuario();

        if (carregado) {
            window.location.href = redirectTo;
            return true;
        }

        return false;
    }

    /**
     * Atualizar informacoes do usuario na UI
     */
    updateUserUI() {
        if (!this.isAuthenticated()) {
            return;
        }

        // Atualizar elementos com classe .user-name
        const userNameElements = document.querySelectorAll('.user-name');
        userNameElements.forEach(el => {
            el.textContent = this.usuario.nome;
        });

        // Atualizar elementos com classe .user-email
        const userEmailElements = document.querySelectorAll('.user-email');
        userEmailElements.forEach(el => {
            el.textContent = this.usuario.email;
        });

        // Atualizar elementos com classe .user-plano
        const userPlanoElements = document.querySelectorAll('.user-plano');
        userPlanoElements.forEach(el => {
            el.textContent = this.usuario.plano;
        });

        // Mostrar elementos apenas para usuarios autenticados
        const authOnlyElements = document.querySelectorAll('.auth-only');
        authOnlyElements.forEach(el => {
            el.style.display = 'block';
        });

        // Esconder elementos apenas para usuarios nao autenticados
        const guestOnlyElements = document.querySelectorAll('.guest-only');
        guestOnlyElements.forEach(el => {
            el.style.display = 'none';
        });
    }
}

// Instância global do serviço de autenticacao
const auth = new AuthService();

// Exportar para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthService;
}
