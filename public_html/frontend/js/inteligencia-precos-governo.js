/**
 * Inteligência de Preços - INTEGRAÇÃO COM API GOVERNO
 * Usa API dadosabertos.compras.gov.br para comparar preços oficiais
 *
 * @version 1.0.0
 * @author Licita.pub
 */

// ============================================
// INICIALIZAÇÃO
// ============================================

document.addEventListener('DOMContentLoaded', async function() {
    console.log('[INIT] Iniciando Inteligência de Preços Governo');

    await checkAuth();
    await carregarUsuario();

    initSidebar();
    initMobileMenu();
    initLogout();
    initEventListeners();

    console.log('[INIT] ✓ Aplicação iniciada com sucesso');
});

function initEventListeners() {
    const formBusca = document.getElementById('form-busca');
    if (formBusca) {
        formBusca.addEventListener('submit', buscarPrecosGoverno);
    }
}


// ============================================
// AUTENTICAÇÃO
// ============================================

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
        console.error('[AUTH] Erro ao verificar autenticação:', error);
        window.location.href = '/frontend/login.html';
    }
}

async function carregarUsuario() {
    try {
        const response = await api.me();
        if (response.success && response.data && response.data.usuario) {
            document.getElementById('userName').textContent = response.data.usuario.nome;
        }
    } catch (error) {
        console.error('[USER] Erro ao carregar usuário:', error);
    }
}


// ============================================
// BUSCA DE PREÇOS DO GOVERNO
// ============================================

async function buscarPrecosGoverno(e) {
    e.preventDefault();

    const termo = document.getElementById('busca').value.trim();
    const uf = document.getElementById('uf').value;
    const limite = document.getElementById('limite')?.value || 10;

    // Validação
    if (!termo || termo.length < 3) {
        mostrarAlerta('Digite pelo menos 3 caracteres para buscar', 'warning');
        return;
    }

    console.log('[BUSCA] Iniciando busca:', { termo, uf, limite });

    const container = document.getElementById('resultados-container');
    const btnBuscar = document.getElementById('btn-buscar');

    // Loading state
    btnBuscar.disabled = true;
    btnBuscar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';

    container.innerHTML = `
        <div class="loading-container">
            <div class="spinner"></div>
            <p>🔍 Buscando código CATMAT...</p>
            <p class="loading-step">Consultando catálogo oficial do governo</p>
        </div>
    `;

    try {
        // Construir URL da API
        const params = new URLSearchParams({
            termo: termo
        });

        if (uf) params.append('uf', uf);
        if (limite) params.append('limite', limite);

        const endpoint = `/inteligencia/buscar-precos-governo.php?${params}`;
        console.log('[API] Chamando endpoint:', endpoint);

        // Atualizar estado do loading
        atualizarLoadingStep('Consultando preços praticados...');

        // Fazer requisição
        const response = await api.request(endpoint);
        console.log('[API] Resposta recebida:', response);

        // Verificar resposta
        if (!response.success) {
            throw new Error(response.message || 'Erro ao buscar preços do governo');
        }

        const dados = response.data;

        // Verificar se encontrou CATMAT
        if (!dados.catmat || !dados.catmat.codigo) {
            mostrarResultadoVazio(termo, 'Não encontramos o código CATMAT correspondente a este produto');
            return;
        }

        // Verificar se encontrou preços
        if (!dados.precos_governo || dados.precos_governo.length === 0) {
            mostrarResultadoVazio(termo, 'Não encontramos preços praticados pelo governo para este produto');
            return;
        }

        // Renderizar resultados
        console.log('[RENDER] Renderizando resultados');
        renderizarResultadosGoverno(dados);

    } catch (error) {
        console.error('[BUSCA] Erro:', error);

        container.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong>Erro ao buscar dados</strong>
                    <p>${error.message}</p>
                </div>
            </div>
        `;
    } finally {
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = '<i class="fas fa-search"></i> Buscar Oportunidades';
    }
}


// ============================================
// RENDERIZAÇÃO DE RESULTADOS
// ============================================

function renderizarResultadosGoverno(dados) {
    const container = document.getElementById('resultados-container');

    const {
        catmat,
        precos_governo,
        produtos_mercado,
        oportunidades,
        analise,
        estatisticas,
        metadata
    } = dados;

    let html = '';

    // Header com informações do CATMAT
    html += renderizarHeaderCatmat(catmat, estatisticas);

    // Estatísticas gerais
    html += renderizarEstatisticas(estatisticas, metadata);

    // Seção de oportunidades (se houver produtos do mercado)
    if (oportunidades && oportunidades.length > 0) {
        html += renderizarOportunidades(oportunidades);
    }

    // Tabela de preços do governo
    html += renderizarTabelaPrecosGoverno(precos_governo);

    // Produtos do mercado local
    if (produtos_mercado && produtos_mercado.length > 0) {
        html += renderizarProdutosMercado(produtos_mercado);
    }

    // Análise inteligente (se disponível)
    if (analise && analise.recomendacao) {
        html += renderizarAnaliseInteligente(analise);
    }

    // Botões de ação
    html += renderizarAcoesFooter(catmat);

    container.innerHTML = html;

    // Inicializar interações
    initToggles();
}


function renderizarHeaderCatmat(catmat, estatisticas) {
    const scoreClass = obterClasseScore(estatisticas.score_medio || 0);

    return `
        <div class="resultado-header">
            <div class="catmat-info">
                <div class="catmat-badge">
                    <i class="fas fa-barcode"></i>
                    <span>CATMAT ${catmat.codigo}</span>
                </div>
                <h2>${catmat.descricao}</h2>
                <div class="catmat-meta">
                    ${catmat.categoria ? `<span><i class="fas fa-tag"></i> ${catmat.categoria}</span>` : ''}
                    ${catmat.score ? `<span><i class="fas fa-percentage"></i> ${Math.round(catmat.score * 100)}% similaridade</span>` : ''}
                </div>
            </div>
            <div class="score-badge ${scoreClass}">
                <div class="score-valor">${estatisticas.score_medio || 0}</div>
                <div class="score-label">Score Médio</div>
            </div>
        </div>
    `;
}


function renderizarEstatisticas(estatisticas, metadata) {
    const economia = calcularEconomia(estatisticas);

    return `
        <div class="estatisticas-grid">
            <div class="estatistica-card">
                <i class="fas fa-receipt"></i>
                <div class="estatistica-valor">${estatisticas.total_precos_governo || 0}</div>
                <div class="estatistica-label">Preços Governo</div>
            </div>

            <div class="estatistica-card">
                <i class="fas fa-dollar-sign"></i>
                <div class="estatistica-valor">R$ ${formatarMoeda(estatisticas.preco_medio_governo || 0)}</div>
                <div class="estatistica-label">Preço Médio Gov</div>
            </div>

            <div class="estatistica-card">
                <i class="fas fa-shopping-cart"></i>
                <div class="estatistica-valor">${estatisticas.total_produtos_mercado || 0}</div>
                <div class="estatistica-label">Produtos Mercado</div>
            </div>

            <div class="estatistica-card">
                <i class="fas fa-chart-line"></i>
                <div class="estatistica-valor">${estatisticas.total_oportunidades || 0}</div>
                <div class="estatistica-label">Oportunidades</div>
            </div>
        </div>

        ${economia > 0 ? `
            <div class="alert alert-success economia-alert">
                <i class="fas fa-piggy-bank"></i>
                <div>
                    <strong>💰 Potencial de Economia Identificado!</strong>
                    <p>Você pode economizar até <strong>R$ ${formatarMoeda(economia)}</strong>
                    comprando no mercado ao invés dos preços praticados pelo governo.</p>
                </div>
            </div>
        ` : ''}

        <div class="metadata-info">
            <span><i class="fas fa-clock"></i> Consultado em ${formatarDataHora(metadata.timestamp)}</span>
            <span><i class="fas fa-${metadata.fonte_cache ? 'database' : 'cloud'}"></i>
                ${metadata.fonte_cache ? 'Cache' : 'API'}
                ${metadata.tempo_resposta ? `(${metadata.tempo_resposta}ms)` : ''}
            </span>
        </div>
    `;
}


function renderizarOportunidades(oportunidades) {
    if (!oportunidades || oportunidades.length === 0) return '';

    let html = `
        <div class="secao-oportunidades">
            <h3><i class="fas fa-star"></i> Melhores Oportunidades (${oportunidades.length})</h3>
            <div class="oportunidades-grid">
    `;

    oportunidades.slice(0, 6).forEach((oport, index) => {
        const classificacao = oport.classificacao || 'BAIXA';
        const classeOport = `oportunidade-${classificacao.toLowerCase()}`;

        html += `
            <div class="oportunidade-card ${classeOport}">
                <div class="oportunidade-rank">#${index + 1}</div>

                <div class="oportunidade-badge">
                    ${obterIconeClassificacao(classificacao)}
                    <span>${formatarClassificacao(classificacao)}</span>
                </div>

                <h4 class="oportunidade-produto">${truncarTexto(oport.produto_mercado.titulo, 60)}</h4>

                <div class="comparacao-precos">
                    <div class="preco-item preco-governo-item">
                        <span class="preco-label">Gov</span>
                        <span class="preco-valor">R$ ${formatarMoeda(oport.preco_governo)}</span>
                    </div>
                    <div class="preco-seta"><i class="fas fa-arrow-right"></i></div>
                    <div class="preco-item preco-mercado-item">
                        <span class="preco-label">Mercado</span>
                        <span class="preco-valor">R$ ${formatarMoeda(oport.preco_mercado)}</span>
                    </div>
                </div>

                <div class="oportunidade-margem">
                    <div class="margem-valor ${oport.margem_percentual > 0 ? 'positiva' : 'negativa'}">
                        ${oport.margem_percentual > 0 ? '+' : ''}${oport.margem_percentual.toFixed(1)}%
                    </div>
                    <div class="margem-absoluta">
                        R$ ${formatarMoeda(Math.abs(oport.margem_absoluta))}
                    </div>
                </div>

                <div class="oportunidade-score">
                    <div class="score-bar">
                        <div class="score-fill" style="width: ${oport.score * 10}%"></div>
                    </div>
                    <span class="score-text">Score: ${oport.score.toFixed(1)}</span>
                </div>

                <a href="${oport.produto_mercado.link}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-ver-produto">
                    <i class="fas fa-external-link-alt"></i> Ver Produto
                </a>
            </div>
        `;
    });

    html += `
            </div>
        </div>
    `;

    return html;
}


function renderizarTabelaPrecosGoverno(precos) {
    if (!precos || precos.length === 0) return '';

    return `
        <div class="secao-tabela">
            <div class="secao-header" onclick="toggleSecao('precos-governo')">
                <h3><i class="fas fa-landmark"></i> Preços Praticados pelo Governo (${precos.length})</h3>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>

            <div class="secao-conteudo" id="secao-precos-governo">
                <div class="tabela-wrapper">
                    <table class="tabela-precos">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Órgão</th>
                                <th>UF</th>
                                <th>Valor Unitário</th>
                                <th>Quantidade</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${precos.map(preco => `
                                <tr>
                                    <td>${formatarData(preco.dataCompra || preco.data_compra)}</td>
                                    <td>${truncarTexto(preco.nomeOrgao || preco.orgao, 40)}</td>
                                    <td><span class="badge-uf">${preco.ufOrgao || preco.uf || '-'}</span></td>
                                    <td class="preco-destaque">R$ ${formatarMoeda(preco.valorUnitario || preco.valor)}</td>
                                    <td>${preco.quantidade || 1}</td>
                                    <td>R$ ${formatarMoeda((preco.valorUnitario || preco.valor) * (preco.quantidade || 1))}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}


function renderizarProdutosMercado(produtos) {
    if (!produtos || produtos.length === 0) return '';

    return `
        <div class="secao-tabela">
            <div class="secao-header" onclick="toggleSecao('produtos-mercado')">
                <h3><i class="fas fa-shopping-bag"></i> Produtos do Mercado (${produtos.length})</h3>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>

            <div class="secao-conteudo collapsed" id="secao-produtos-mercado">
                <div class="produtos-grid">
                    ${produtos.map(produto => `
                        <div class="produto-card">
                            <div class="produto-header">
                                <h4>${truncarTexto(produto.titulo, 80)}</h4>
                            </div>
                            <div class="produto-preco">
                                <span class="preco-valor">R$ ${formatarMoeda(produto.preco)}</span>
                            </div>
                            <div class="produto-footer">
                                <a href="${produto.link}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="btn-produto">
                                    <i class="fas fa-external-link-alt"></i> Ver Oferta
                                </a>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}


function renderizarAnaliseInteligente(analise) {
    if (!analise) return '';

    const iconClass = analise.fornecimento_recomendado ? 'fa-thumbs-up' : 'fa-info-circle';
    const alertClass = analise.fornecimento_recomendado ? 'alert-success' : 'alert-info';

    return `
        <div class="secao-analise">
            <div class="alert ${alertClass}">
                <i class="fas ${iconClass}"></i>
                <div>
                    <strong>📊 Análise Inteligente</strong>
                    <p>${analise.recomendacao}</p>
                    ${analise.observacao ? `<p class="observacao"><em>${analise.observacao}</em></p>` : ''}
                </div>
            </div>
        </div>
    `;
}


function renderizarAcoesFooter(catmat) {
    return `
        <div class="acoes-footer">
            <button class="btn-acao btn-secundario" onclick="buscarMaisLojas('${catmat.descricao}')">
                <i class="fas fa-search"></i> Buscar em Mais Lojas
            </button>

            <button class="btn-acao btn-primario" onclick="exportarResultados()">
                <i class="fas fa-download"></i> Exportar Análise
            </button>
        </div>
    `;
}


// ============================================
// FUNÇÕES AUXILIARES
// ============================================

function mostrarResultadoVazio(termo, mensagem) {
    const container = document.getElementById('resultados-container');

    container.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Nenhum resultado encontrado</h3>
            <p>${mensagem || `Não encontramos resultados para "${termo}"`}</p>
            <button class="btn-acao btn-primario" onclick="document.getElementById('busca').focus()">
                <i class="fas fa-search"></i> Tentar Outra Busca
            </button>
        </div>
    `;
}


function mostrarAlerta(mensagem, tipo = 'info') {
    const container = document.getElementById('resultados-container');

    const icons = {
        info: 'fa-info-circle',
        success: 'fa-check-circle',
        warning: 'fa-exclamation-triangle',
        error: 'fa-times-circle'
    };

    container.innerHTML = `
        <div class="alert alert-${tipo}">
            <i class="fas ${icons[tipo]}"></i>
            <span>${mensagem}</span>
        </div>
    `;
}


function atualizarLoadingStep(mensagem) {
    const stepElement = document.querySelector('.loading-step');
    if (stepElement) {
        stepElement.textContent = mensagem;
    }
}


function toggleSecao(secaoId) {
    const secao = document.getElementById(`secao-${secaoId}`);
    const header = secao?.previousElementSibling;

    if (secao) {
        secao.classList.toggle('collapsed');

        if (header) {
            const icon = header.querySelector('.toggle-icon');
            if (icon) {
                icon.classList.toggle('rotated');
            }
        }
    }
}


function initToggles() {
    // Todas as seções começam abertas, exceto produtos do mercado
    const secoesProdutosMercado = document.querySelectorAll('#secao-produtos-mercado');
    secoesProdutosMercado.forEach(secao => {
        secao.classList.add('collapsed');
    });
}


function calcularEconomia(estatisticas) {
    if (!estatisticas.preco_medio_governo || !estatisticas.preco_medio_mercado) {
        return 0;
    }

    const economia = estatisticas.preco_medio_governo - estatisticas.preco_medio_mercado;
    return economia > 0 ? economia : 0;
}


function obterClasseScore(score) {
    if (score >= 8) return 'score-excelente';
    if (score >= 6) return 'score-bom';
    if (score >= 4) return 'score-medio';
    return 'score-baixo';
}


function obterIconeClassificacao(classificacao) {
    const icones = {
        'EXCELENTE': '<i class="fas fa-star"></i>',
        'MUITO_BOA': '<i class="fas fa-trophy"></i>',
        'BOA': '<i class="fas fa-thumbs-up"></i>',
        'RAZOAVEL': '<i class="fas fa-check"></i>',
        'BAIXA': '<i class="fas fa-minus"></i>'
    };

    return icones[classificacao] || icones['BAIXA'];
}


function formatarClassificacao(classificacao) {
    const nomes = {
        'EXCELENTE': 'Excelente',
        'MUITO_BOA': 'Muito Boa',
        'BOA': 'Boa',
        'RAZOAVEL': 'Razoável',
        'BAIXA': 'Baixa'
    };

    return nomes[classificacao] || classificacao;
}


function truncarTexto(texto, maxLength) {
    if (!texto) return '';
    if (texto.length <= maxLength) return texto;
    return texto.substring(0, maxLength) + '...';
}


function formatarMoeda(valor) {
    const numero = parseFloat(valor) || 0;
    return numero.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}


function formatarData(data) {
    if (!data) return '-';

    try {
        const date = new Date(data);
        return date.toLocaleDateString('pt-BR');
    } catch {
        return data;
    }
}


function formatarDataHora(timestamp) {
    if (!timestamp) return 'agora';

    try {
        const date = new Date(timestamp);
        return date.toLocaleString('pt-BR');
    } catch {
        return timestamp;
    }
}


// ============================================
// AÇÕES DO USUÁRIO
// ============================================

function buscarMaisLojas(descricao) {
    const termo = encodeURIComponent(descricao);

    const lojas = [
        { nome: 'Mercado Livre', url: `https://lista.mercadolivre.com.br/${termo}` },
        { nome: 'Google Shopping', url: `https://www.google.com/search?tbm=shop&q=${termo}` },
        { nome: 'Amazon', url: `https://www.amazon.com.br/s?k=${termo}` },
        { nome: 'Magazine Luiza', url: `https://www.magazineluiza.com.br/busca/${termo}` }
    ];

    lojas.forEach(loja => {
        window.open(loja.url, '_blank', 'noopener,noreferrer');
    });
}


function exportarResultados() {
    // TODO: Implementar exportação para CSV/Excel
    alert('Funcionalidade de exportação será implementada em breve!');
}


// ============================================
// FUNÇÕES DE UI (Sidebar, Menu, Logout)
// ============================================

function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar?.classList.remove('active');
        });
    }
}


function initMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            sidebar?.classList.add('active');
        });
    }
}


function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            try {
                await api.logout();
                localStorage.removeItem('session_id');
                window.location.href = '/frontend/login.html';
            } catch (error) {
                console.error('[LOGOUT] Erro:', error);
                localStorage.removeItem('session_id');
                window.location.href = '/frontend/login.html';
            }
        });
    }
}


// ============================================
// EXPORTS (para debug)
// ============================================

if (typeof window !== 'undefined') {
    window.InteligenciaGoverno = {
        buscarPrecosGoverno,
        toggleSecao,
        buscarMaisLojas,
        exportarResultados
    };
}
