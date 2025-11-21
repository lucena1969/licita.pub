/**
 * Inteligência de Preços - JavaScript
 * Comparação de preços governo vs mercado
 */

// Inicialização
document.addEventListener('DOMContentLoaded', async function() {
    // Verificar autenticação
    await checkAuth();

    // Carregar dados do usuário
    await carregarUsuario();

    // Inicializar componentes
    initSidebar();
    initMobileMenu();
    initLogout();

    // Verificar callback OAuth
    verificarCallbackOAuth();

    // Verificar se precisa mostrar banner OAuth
    verificarAutorizacaoML();

    // Event listener do formulário
    document.getElementById('form-busca').addEventListener('submit', buscarOportunidades);
});

// Verificar callback OAuth (sucesso/erro)
function verificarCallbackOAuth() {
    const params = new URLSearchParams(window.location.search);
    const oauthStatus = params.get('oauth');

    const container = document.getElementById('resultados-container');

    if (oauthStatus === 'success') {
        container.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>Autorização concluída!</strong> Agora você pode buscar preços do Mercado Livre.
            </div>
        `;
        // Limpar URL
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (oauthStatus === 'denied') {
        container.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                Autorização negada. Você precisa autorizar para comparar preços.
            </div>
        `;
    } else if (oauthStatus) {
        container.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                Erro ao autorizar Mercado Livre. Tente novamente.
            </div>
        `;
    }
}

// Verificar se usuário já autorizou ML (mostrar/esconder banner)
async function verificarAutorizacaoML() {
    // Por enquanto, sempre mostrar banner
    // TODO: Implementar endpoint para verificar se tem token válido
    document.getElementById('oauth-banner').style.display = 'flex';
}

// Verificar autenticação
async function checkAuth() {
    try {
        const sessionId = localStorage.getItem('session_id');
        if (!sessionId) {
            window.location.href = '/frontend/login.html';
            return;
        }

        const response = await api.me();
        if (!response.success || !response.data || !response.data.usuario) {
            window.location.href = '/frontend/login.html';
        }
    } catch (error) {
        console.error('Erro ao verificar autenticação:', error);
        window.location.href = '/frontend/login.html';
    }
}

// Carregar dados do usuário
async function carregarUsuario() {
    const response = await api.me();
    if (response.success && response.data && response.data.usuario) {
        document.getElementById('userName').textContent = response.data.usuario.nome;
    }
}

// Buscar oportunidades
async function buscarOportunidades(e) {
    e.preventDefault();

    const termo = document.getElementById('busca').value.trim();
    const uf = document.getElementById('uf').value;
    const vigente = document.getElementById('vigente').value;

    if (termo.length < 3) {
        alert('Digite pelo menos 3 caracteres');
        return;
    }

    const container = document.getElementById('resultados-container');
    const btnBuscar = document.getElementById('btn-buscar');

    // Loading
    container.innerHTML = '<div class="loading-container"><div class="spinner"></div><p>Buscando oportunidades...</p></div>';
    btnBuscar.disabled = true;

    try {
        // Montar query string
        const params = new URLSearchParams({ q: termo });
        if (uf) params.append('uf', uf);
        if (vigente) params.append('vigente', vigente);

        // Chamar API
        const response = await api.request(`/inteligencia/comparar.php?${params}`);

        btnBuscar.disabled = false;

        if (!response.success || !response.data) {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${response.error || 'Erro ao buscar oportunidades'}
                </div>
            `;
            return;
        }

        const data = response.data;

        // Verificar se há oportunidades
        if (!data.oportunidades || data.oportunidades.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Nenhuma oportunidade encontrada</h3>
                    <p>Tente usar outras palavras-chave ou ajustar os filtros</p>
                </div>
            `;
            return;
        }

        // Renderizar oportunidades
        renderizarOportunidades(data.oportunidades, termo);

    } catch (error) {
        console.error('Erro ao buscar oportunidades:', error);
        btnBuscar.disabled = false;
        container.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                Erro ao buscar oportunidades. Tente novamente.
            </div>
        `;
    }
}

// Renderizar oportunidades
function renderizarOportunidades(oportunidades, termo) {
    const container = document.getElementById('resultados-container');

    let html = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>${oportunidades.length} oportunidade(s)</strong> encontrada(s) para "<strong>${termo}</strong>"
        </div>
    `;

    oportunidades.forEach(op => {
        const gov = op.governo;
        const merc = op.mercado;
        const opo = op.oportunidade;

        html += `
            <div class="oportunidade-card">
                <div class="oportunidade-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-bottom: 2px solid #dee2e6;">
                    <span class="margem-badge margem-${opo.classificacao}">
                        ${getClassificacaoTexto(opo.classificacao)} - ${opo.margem_percentual}%
                    </span>
                    <div style="font-size: 1.1rem; font-weight: 600; color: #333; margin-top: 5px;">
                        ${gov.descricao}
                    </div>
                </div>

                <div class="comparacao-grid">
                    <div class="lado-governo">
                        <div class="lado-titulo">🏛️ GOVERNO PAGA</div>
                        <div class="produto-nome">${gov.descricao}</div>
                        <div class="preco-valor">${utils.formatarValor(gov.preco)}</div>
                        <div class="info-item"><i class="fas fa-box"></i> Unidade: ${gov.unidade}</div>
                        <div class="info-item"><i class="fas fa-hashtag"></i> Disponível: ${gov.quantidade_disponivel}</div>
                        <div class="info-item"><i class="fas fa-file-alt"></i> ATA: ${gov.ata_numero || 'N/A'}</div>
                        <div class="info-item"><i class="fas fa-building"></i> ${gov.orgao}</div>
                        ${gov.uf ? `<div class="info-item"><i class="fas fa-map-marker-alt"></i> ${gov.uf}</div>` : ''}
                    </div>

                    <div class="lado-mercado">
                        <div class="lado-titulo">🛒 MERCADO LIVRE</div>
                        <div class="produto-nome">${merc.titulo}</div>
                        <div class="preco-valor preco-mercado">${utils.formatarValor(merc.preco)}</div>
                        <div class="info-item"><i class="fas fa-box"></i> Disponível: ${merc.disponivel} unidades</div>
                        <div class="info-item">
                            <i class="fas fa-truck"></i>
                            ${merc.frete_gratis ? '<strong style="color: #28a745;">Frete Grátis</strong>' : 'Frete a calcular'}
                        </div>
                        ${merc.thumbnail ? `<img src="${merc.thumbnail}" alt="Produto" style="max-width: 100px; margin-top: 10px; border-radius: 4px;">` : ''}
                    </div>
                </div>

                <div class="acoes-footer">
                    <div>
                        ${merc.permalink ? `<a href="${merc.permalink}" target="_blank" class="btn-acao btn-comprar"><i class="fas fa-shopping-cart"></i> Comprar no ML</a>` : ''}
                    </div>
                    <div class="margem-info">
                        <div class="margem-valor">+${utils.formatarValor(opo.margem_reais)}</div>
                        <div class="margem-label">Margem potencial</div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Texto da classificação
function getClassificacaoTexto(classificacao) {
    const textos = {
        'EXCELENTE': '⭐ Excelente',
        'MUITO_BOA': '✨ Muito Boa',
        'BOA': '👍 Boa',
        'RAZOAVEL': '👌 Razoável',
        'BAIXA': '⚠️ Baixa'
    };
    return textos[classificacao] || classificacao;
}

// Inicializar sidebar
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar || !toggle) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
}

// Inicializar menu mobile
function initMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobileMenuToggle');

    if (!sidebar || !mobileToggle) return;

    mobileToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    // Fechar ao clicar fora (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
}

// Inicializar logout
function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');

    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        if (confirm('Deseja realmente sair?')) {
            try {
                await api.logout();
                localStorage.removeItem('session_id');
                window.location.href = '/frontend/login.html';
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
                localStorage.removeItem('session_id');
                window.location.href = '/frontend/login.html';
            }
        }
    });
}
