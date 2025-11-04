/**
 * APP.JS - Inicialização Principal
 * Controla o fluxo da aplicação
 */

// Variáveis globais
let currentUser = null;
let limiteInfo = null;

/**
 * Inicializar aplicação
 */
document.addEventListener('DOMContentLoaded', async function() {
    console.log('Inicializando Licita.pub...');

    // Verificar autenticação
    await checkAuth();

    // Inicializar componentes
    initSidebar();
    initLogout();
    initLimiteCounter();

    // Registrar rotas
    registerRoutes();

    console.log('Licita.pub iniciado com sucesso!');
});

/**
 * Verificar autenticação
 */
async function checkAuth() {
    try {
        const response = await api.me();

        if (response.success && response.data && response.data.success) {
            currentUser = response.data.usuario || response.data.data;
            updateUserInfo(currentUser);
        } else {
            // Não autenticado - redirecionar para login
            redirectToLogin();
        }
    } catch (error) {
        console.error('Erro ao verificar autenticação:', error);
        redirectToLogin();
    }
}

/**
 * Atualizar informações do usuário no header
 */
function updateUserInfo(user) {
    const userNameEl = document.getElementById('userName');
    if (userNameEl && user) {
        userNameEl.textContent = user.nome || user.email;
    }
}

/**
 * Redirecionar para login
 */
function redirectToLogin() {
    window.location.href = '/frontend/login.html';
}

/**
 * Inicializar sidebar (menu lateral)
 */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = createOverlay();

    if (toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
    }

    // Fechar ao clicar no overlay
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });

    // Fechar sidebar ao clicar em um link (mobile)
    document.querySelectorAll('.nav-item:not(.disabled)').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
    });
}

/**
 * Criar overlay para sidebar mobile
 */
function createOverlay() {
    let overlay = document.querySelector('.sidebar-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    return overlay;
}

/**
 * Inicializar botão de logout
 */
function initLogout() {
    const btnLogout = document.getElementById('btnLogout');

    if (btnLogout) {
        btnLogout.addEventListener('click', async () => {
            if (confirm('Deseja realmente sair?')) {
                try {
                    await api.post('/auth/logout');
                    window.location.href = '/frontend/login.html';
                } catch (error) {
                    console.error('Erro ao fazer logout:', error);
                    // Limpar localStorage e redirecionar mesmo assim
                    localStorage.removeItem('token');
                    window.location.href = '/frontend/login.html';
                }
            }
        });
    }
}

/**
 * Inicializar contador de limite de consultas
 */
async function initLimiteCounter() {
    await atualizarLimite();

    // Atualizar a cada 60 segundos
    setInterval(atualizarLimite, 60000);
}

/**
 * Atualizar informações de limite
 */
async function atualizarLimite() {
    try {
        const response = await api.request('/licitacoes/limite.php');

        if (response.success && response.data && response.data.success) {
            limiteInfo = response.data.data;
            updateLimiteDisplay(limiteInfo);
        }
    } catch (error) {
        console.error('Erro ao carregar limite:', error);
    }
}

/**
 * Atualizar display do limite
 */
function updateLimiteDisplay(limite) {
    const limiteCountEl = document.getElementById('limiteCount');
    const limiteRenovaEl = document.getElementById('limiteRenova');

    if (limiteCountEl && limite) {
        const restantes = limite.limite - limite.consumido;
        limiteCountEl.textContent = restantes;

        // Alterar cor baseado no limite
        if (restantes < 10) {
            limiteCountEl.style.color = '#dc3545'; // Vermelho
        } else if (restantes < 50) {
            limiteCountEl.style.color = '#ffc107'; // Amarelo
        } else {
            limiteCountEl.style.color = '#1351b4'; // Azul
        }
    }

    if (limiteRenovaEl && limite && limite.renova_em) {
        const renovaEm = new Date(limite.renova_em);
        const now = new Date();
        const diff = renovaEm - now;

        if (diff > 0) {
            const horas = Math.floor(diff / (1000 * 60 * 60));
            const minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            limiteRenovaEl.textContent = `${horas}h ${minutos}min`;
        } else {
            limiteRenovaEl.textContent = 'Em breve';
        }
    }
}

/**
 * Registrar todas as rotas da aplicação
 */
function registerRoutes() {
    // Rota: Licitações
    router.register('/licitacoes', async (params) => {
        if (typeof LicitacoesModule !== 'undefined') {
            await LicitacoesModule.render();
        } else {
            router.showError('Módulo de licitações não carregado');
        }
    });

    // Rota: Pesquisa de Preços (ARPs)
    router.register('/precos', async (params) => {
        if (typeof PrecosModule !== 'undefined') {
            await PrecosModule.render();
        } else {
            router.showError('Módulo de preços não carregado');
        }
    });

    // Rotas futuras (não implementadas)
    router.register('/contratos', () => {
        router.showNotImplemented('Contratos');
    });

    router.register('/orgaos', () => {
        router.showNotImplemented('Órgãos');
    });

    router.register('/favoritos', () => {
        router.showNotImplemented('Favoritos');
    });

    router.register('/alertas', () => {
        router.showNotImplemented('Alertas');
    });

    router.register('/historico', () => {
        router.showNotImplemented('Histórico');
    });

    router.register('/perfil', () => {
        router.showNotImplemented('Meu Perfil');
    });

    router.register('/planos', () => {
        router.showNotImplemented('Planos');
    });
}

/**
 * Utilidades globais
 */
const utils = {
    /**
     * Formatar data brasileira
     */
    formatarData(dataStr) {
        if (!dataStr) return '--';
        const data = new Date(dataStr);
        return data.toLocaleDateString('pt-BR');
    },

    /**
     * Formatar valor monetário
     */
    formatarValor(valor) {
        if (!valor) return '--';
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    },

    /**
     * Truncar texto
     */
    truncate(text, length = 150) {
        if (!text) return '';
        if (text.length <= length) return text;
        return text.substring(0, length) + '...';
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};
