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

    // Registrar rotas
    registerRoutes();

    // Iniciar o router (carregar rota inicial)
    router.start();

    // Inicializar componentes
    initSidebar();
    initLogout();
    initLimiteCounter();

    console.log('Licita.pub iniciado com sucesso!');
});

/**
 * Verificar autenticação
 */
async function checkAuth() {
    try {
        // Verificar se há session_id
        const sessionId = localStorage.getItem('session_id');
        if (!sessionId) {
            console.log('Sem session_id - redirecionando para login');
            redirectToLogin();
            return;
        }

        // Chamar API para verificar sessão
        const response = await api.me();

        if (response.success && response.data && response.data.usuario) {
            currentUser = response.data.usuario;
            updateUserInfo(currentUser);
        } else {
            // Não autenticado - redirecionar para login
            console.log('Sessão inválida - redirecionando para login');
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
                    await api.logout();
                    localStorage.removeItem('session_id');
                    window.location.href = '/frontend/login.html';
                } catch (error) {
                    console.error('Erro ao fazer logout:', error);
                    // Limpar localStorage e redirecionar mesmo assim
                    localStorage.removeItem('session_id');
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
        // Por enquanto, usar dados fictícios
        // TODO: Implementar endpoint de limite na API
        limiteInfo = {
            limite: 10,
            consumido: 0,
            renova_em: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString()
        };
        updateLimiteDisplay(limiteInfo);
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
    // Rota padrão: Home/Dashboard
    router.register('/', () => {
        router.render(`
            <div class="dashboard-home">
                <div class="welcome-card">
                    <h1>Bem-vindo ao Licita.pub</h1>
                    <p>Sua plataforma completa para consulta de licitações públicas do Brasil.</p>

                    <div class="dashboard-cards">
                        <a href="/frontend/consultas.html" class="dashboard-card">
                            <i class="fas fa-file-contract"></i>
                            <h3>Licitações</h3>
                            <p>Consulte mais de 3.000 licitações públicas</p>
                        </a>

                        <a href="/frontend/inteligencia-precos.html" class="dashboard-card" style="cursor: pointer;">
                            <i class="fas fa-chart-line"></i>
                            <h3>Inteligência de Preços</h3>
                            <p>Compare preços do governo com o mercado e descubra oportunidades</p>
                        </a>

                        <div class="dashboard-card disabled">
                            <i class="fas fa-file-signature"></i>
                            <h3>Contratos</h3>
                            <p>Em breve</p>
                        </div>

                        <div class="dashboard-card disabled">
                            <i class="fas fa-building"></i>
                            <h3>Órgãos</h3>
                            <p>Em breve</p>
                        </div>
                    </div>
                </div>
            </div>
        `);
    });

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
 * Utilidades globais - JÁ CARREGADAS VIA utils.js
 * O objeto utils está disponível globalmente
 */
