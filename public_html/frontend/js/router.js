/**
 * ROUTER - Sistema de Roteamento SPA
 * Gerencia navegação entre páginas sem reload
 */

class Router {
    constructor() {
        this.routes = {};
        this.currentRoute = null;
        this.contentElement = document.getElementById('mainContent');

        // Bind event listeners
        this.init();
    }

    /**
     * Inicializar router
     */
    init() {
        // Listener para mudanças de hash
        window.addEventListener('hashchange', () => this.handleRoute());

        // Listener para cliques em links de navegação
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                e.preventDefault();
                const route = link.getAttribute('href').substring(1);
                this.navigate(route);
            }
        });

        // NÃO carregar rota inicial aqui
        // Será chamado pelo app.js após registrar as rotas
    }

    /**
     * Iniciar roteamento (chamar após registrar todas as rotas)
     */
    start() {
        // Se não há hash na URL, garantir que vai para '/'
        if (!window.location.hash || window.location.hash === '#') {
            window.location.hash = '#/';
        }
        this.handleRoute();
    }

    /**
     * Registrar uma rota
     * @param {string} path - Caminho da rota (ex: '/licitacoes')
     * @param {Function} handler - Função que renderiza a página
     */
    register(path, handler) {
        this.routes[path] = handler;
    }

    /**
     * Navegar para uma rota
     * @param {string} path - Caminho da rota
     */
    navigate(path) {
        // Remover # inicial se existir
        if (path.startsWith('#')) {
            path = path.substring(1);
        }

        // Atualizar hash
        window.location.hash = path;
    }

    /**
     * Processar rota atual
     */
    async handleRoute() {
        // Obter caminho do hash
        let path = window.location.hash.substring(1) || '/';

        // Remover trailing slash
        if (path.endsWith('/') && path.length > 1) {
            path = path.slice(0, -1);
        }

        // Se path já começa com /, usar direto; senão, adicionar /
        let fullRoutePath = path.startsWith('/') ? path : '/' + path;

        // Extrair apenas a rota base (sem parâmetros adicionais)
        const pathParts = fullRoutePath.split('/').filter(p => p);
        if (pathParts.length > 0) {
            fullRoutePath = '/' + pathParts[0];
        } else {
            fullRoutePath = '/';
        }

        // Parâmetros são as partes restantes
        const params = pathParts.slice(1);

        // Atualizar navegação ativa
        this.updateActiveNav(fullRoutePath);

        // Verificar se rota existe
        if (this.routes[fullRoutePath]) {
            this.currentRoute = fullRoutePath;

            // Mostrar loading
            this.showLoading();

            try {
                // Executar handler da rota
                await this.routes[fullRoutePath](params);
            } catch (error) {
                console.error('Erro ao carregar rota:', error);
                this.showError('Erro ao carregar página');
            }
        } else {
            // Rota não encontrada - redirecionar para home
            console.warn('Rota não encontrada:', fullRoutePath);
            this.navigate('/');
        }
    }

    /**
     * Atualizar item ativo na navegação
     * @param {string} path - Caminho da rota
     */
    updateActiveNav(path) {
        // Remover classe active de todos
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        // Adicionar classe active no item correto
        const routeName = path.substring(1); // Remove '/'
        const activeItem = document.querySelector(`[data-route="${routeName}"]`);
        if (activeItem) {
            activeItem.classList.add('active');
        }
    }

    /**
     * Renderizar conteúdo na área principal
     * @param {string} html - HTML para renderizar
     */
    render(html) {
        if (this.contentElement) {
            this.contentElement.innerHTML = html;
        }
    }

    /**
     * Mostrar loading
     */
    showLoading() {
        this.render(`
            <div class="loading-container">
                <div class="spinner"></div>
                <p>Carregando...</p>
            </div>
        `);
    }

    /**
     * Mostrar erro
     * @param {string} message - Mensagem de erro
     */
    showError(message) {
        this.render(`
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Erro</h3>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="router.navigate('/')">
                    Voltar ao Início
                </button>
            </div>
        `);
    }

    /**
     * Mostrar página não implementada
     * @param {string} pageName - Nome da página
     */
    showNotImplemented(pageName) {
        this.render(`
            <div class="empty-state">
                <i class="fas fa-tools"></i>
                <h3>${pageName} - Em Desenvolvimento</h3>
                <p>Esta funcionalidade está sendo desenvolvida e estará disponível em breve.</p>
                <button class="btn btn-primary" onclick="router.navigate('/')">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </button>
            </div>
        `);
    }
}

// Criar instância global do router
const router = new Router();
