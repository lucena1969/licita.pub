/**
 * Serviço de Autenticação - Licita.pub
 * Gerenciamento de autenticação e estado do usuário
 */

class AuthService {
    constructor() {
        this.usuario = null;
        this.carregarUsuario();
    }

    /**
     * Verificar se usuário está autenticado
     */
    isAuthenticated() {
        // Verificar apenas se tem token (mais confiável)
        return api.getSessionId() !== null;
    }

    /**
     * Obter token de autenticação
     */
    getToken() {
        return api.getSessionId();
    }

    /**
     * Obter usuário atual
     */
    getUsuario() {
        return this.usuario;
    }

    /**
     * Carregar dados do usuário
     */
    async carregarUsuario() {
        if (!api.getSessionId()) {
            this.usuario = null;
            return false;
        }

        try {
            const response = await api.me();

            if (response.success && response.data.success) {
                this.usuario = response.data.usuario;
                return true;
            } else {
                this.usuario = null;
                api.removeSessionId();
                return false;
            }
        } catch (error) {
            console.error('Erro ao carregar usuário:', error);
            this.usuario = null;
            return false;
        }
    }

    /**
     * Registrar novo usuário
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

        if (response.success && response.data.success) {
            this.usuario = response.data.usuario;
            return {
                success: true,
                usuario: this.usuario,
            };
        }

        return response;
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
     * Redirecionar se não autenticado
     */
    requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '/login.html';
        }
    }

    /**
     * Redirecionar se já autenticado
     */
    redirectIfAuthenticated(redirectTo = '/consultas.html') {
        if (this.isAuthenticated()) {
            window.location.href = redirectTo;
        }
    }

    /**
     * Atualizar informações do usuário na UI
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

        // Mostrar elementos apenas para usuários autenticados
        const authOnlyElements = document.querySelectorAll('.auth-only');
        authOnlyElements.forEach(el => {
            el.style.display = 'block';
        });

        // Esconder elementos apenas para usuários não autenticados
        const guestOnlyElements = document.querySelectorAll('.guest-only');
        guestOnlyElements.forEach(el => {
            el.style.display = 'none';
        });
    }
}

// Instância global do serviço de autenticação
const auth = new AuthService();

// Exportar para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthService;
}
