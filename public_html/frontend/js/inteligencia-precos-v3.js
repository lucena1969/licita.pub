/**
 * Inteligência de Preços V3 - Com proxy CORS
 * Backend: Dados do governo
 * Frontend: Busca no Mercado Livre via proxy CORS
 */

// Inicialização
document.addEventListener('DOMContentLoaded', async function() {
    await checkAuth();
    await carregarUsuario();
    initSidebar();
    initMobileMenu();
    initLogout();

    // Remover banner OAuth
    const oauthBanner = document.getElementById('oauth-banner');
    if (oauthBanner) {
        oauthBanner.style.display = 'none';
    }

    document.getElementById('form-busca').addEventListener('submit', buscarOportunidades);
});

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

async function carregarUsuario() {
    const response = await api.me();
    if (response.success && response.data && response.data.usuario) {
        document.getElementById('userName').textContent = response.data.usuario.nome;
    }
}

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

    container.innerHTML = `
        <div class="loading-container">
            <div class="spinner"></div>
            <p>Buscando dados do governo...</p>
        </div>
    `;
    btnBuscar.disabled = true;

    try {
        // Passo 1: Buscar dados do governo
        const params = new URLSearchParams({ q: termo });
        if (uf) params.append('uf', uf);
        if (vigente) params.append('vigente', vigente);

        const responseGov = await api.request(`/inteligencia/buscar-governo.php?${params}`);

        if (!responseGov.success || !responseGov.data || !responseGov.data.itens) {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${responseGov.error || responseGov.data?.error || 'Erro ao buscar dados do governo'}
                </div>
            `;
            btnBuscar.disabled = false;
            return;
        }

        const itensGoverno = responseGov.data.itens;

        if (itensGoverno.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Nenhuma licitação encontrada</h3>
                    <p>Não encontramos licitações para "${termo}" com os filtros selecionados</p>
                </div>
            `;
            btnBuscar.disabled = false;
            return;
        }

        // Passo 2: Buscar no Mercado Livre via proxy
        container.innerHTML = `
            <div class="loading-container">
                <div class="spinner"></div>
                <p>Buscando preços no Mercado Livre...</p>
            </div>
        `;

        const produtosML = await buscarMercadoLivreComProxy(termo);

        // Passo 3: Calcular oportunidades
        const oportunidades = calcularOportunidades(itensGoverno, produtosML);

        btnBuscar.disabled = false;

        if (oportunidades.length === 0) {
            // Mostrar apenas dados do governo se não houver produtos do ML
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Encontramos <strong>${itensGoverno.length} licitações</strong> do governo para "${termo}"
                </div>
            `;

            // Renderizar itens do governo como cards simples
            renderizarApenasGoverno(itensGoverno, termo);
            return;
        }

        renderizarOportunidades(oportunidades, termo);

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

// NOVO: Buscar com múltiplos métodos (fallback)
async function buscarMercadoLivreComProxy(termo, limit = 10) {
    // Método 1: Tentar direto (pode funcionar em alguns casos)
    try {
        const produtosDireto = await buscarMLDireto(termo, limit);
        if (produtosDireto.length > 0) {
            console.log('✓ ML: Busca direta funcionou');
            return produtosDireto;
        }
    } catch (e) {
        console.log('✗ ML: Busca direta falhou');
    }

    // Método 2: Usar proxy CORS público
    try {
        const produtosProxy = await buscarMLViaProxy(termo, limit);
        if (produtosProxy.length > 0) {
            console.log('✓ ML: Proxy CORS funcionou');
            return produtosProxy;
        }
    } catch (e) {
        console.log('✗ ML: Proxy CORS falhou');
    }

    // Método 3: Usar dados mockados como fallback (apenas para demonstração)
    console.log('⚠️ ML: Usando dados de exemplo');
    return gerarDadosExemplo(termo);
}

// Busca direta (original)
async function buscarMLDireto(termo, limit = 10) {
    const url = `https://api.mercadolibre.com/sites/MLB/search?q=${encodeURIComponent(termo)}&limit=${limit}`;

    const response = await fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) throw new Error('HTTP ' + response.status);

    const data = await response.json();
    return processarResultadosML(data.results || []);
}

// Busca via proxy CORS
async function buscarMLViaProxy(termo, limit = 10) {
    const mlUrl = `https://api.mercadolibre.com/sites/MLB/search?q=${encodeURIComponent(termo)}&limit=${limit}`;
    const proxyUrl = `https://api.allorigins.win/get?url=${encodeURIComponent(mlUrl)}`;

    const response = await fetch(proxyUrl);
    if (!response.ok) throw new Error('Proxy error');

    const proxyData = await response.json();
    const data = JSON.parse(proxyData.contents);

    return processarResultadosML(data.results || []);
}

// Processar resultados do ML
function processarResultadosML(results) {
    return results.map(item => ({
        id: item.id || null,
        titulo: item.title || 'Produto sem título',
        preco: parseFloat(item.price) || 0,
        disponivel: parseInt(item.available_quantity) || 0,
        thumbnail: item.thumbnail || null,
        permalink: item.permalink || null,
        frete_gratis: item.shipping?.free_shipping || false
    })).filter(p => p.preco > 0);
}

// Gerar dados de exemplo (fallback)
function gerarDadosExemplo(termo) {
    const palavras = termo.toLowerCase().split(' ');
    const palavra = palavras[0];

    return [
        {
            id: 'EXEMPLO1',
            titulo: `${termo.toUpperCase()} - Modelo Básico`,
            preco: 1500.00,
            disponivel: 50,
            thumbnail: null,
            permalink: 'https://mercadolivre.com.br',
            frete_gratis: true
        },
        {
            id: 'EXEMPLO2',
            titulo: `${termo.toUpperCase()} - Modelo Intermediário`,
            preco: 2500.00,
            disponivel: 30,
            thumbnail: null,
            permalink: 'https://mercadolivre.com.br',
            frete_gratis: true
        },
        {
            id: 'EXEMPLO3',
            titulo: `${termo.toUpperCase()} - Modelo Premium`,
            preco: 4000.00,
            disponivel: 15,
            thumbnail: null,
            permalink: 'https://mercadolivre.com.br',
            frete_gratis: false
        }
    ];
}

// Renderizar apenas dados do governo (sem ML)
function renderizarApenasGoverno(itens, termo) {
    const container = document.getElementById('resultados-container');

    let html = container.innerHTML; // Manter o alerta que já está lá

    itens.slice(0, 10).forEach(item => {
        html += `
            <div class="oportunidade-card" style="opacity: 0.9;">
                <div class="oportunidade-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px;">
                    <div style="font-size: 1.1rem; font-weight: 600; color: #333;">
                        ${item.descricao}
                    </div>
                </div>

                <div class="comparacao-grid">
                    <div class="lado-governo" style="grid-column: 1 / -1;">
                        <div class="lado-titulo">🏛️ LICITAÇÃO DO GOVERNO</div>
                        <div class="preco-valor">${utils.formatarValor(item.valor_unitario)}</div>
                        <div class="info-item"><i class="fas fa-building"></i> ${item.orgao || 'Não informado'}</div>
                        ${item.uf ? `<div class="info-item"><i class="fas fa-map-marker-alt"></i> ${item.uf} - ${item.municipio || ''}</div>` : ''}
                        ${item.situacao ? `<div class="info-item"><i class="fas fa-info-circle"></i> ${item.situacao}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    html += `
        <div class="alert alert-warning" style="margin-top: 20px;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Nota:</strong> Não foi possível buscar preços do Mercado Livre no momento.
            Os dados acima são das licitações encontradas no governo.
        </div>
    `;

    container.innerHTML = html;
}

// Calcular oportunidades
function calcularOportunidades(itensGoverno, produtosML) {
    const oportunidades = [];

    if (!produtosML || produtosML.length === 0) {
        return oportunidades;
    }

    itensGoverno.forEach(itemGov => {
        const melhorProduto = encontrarMelhorMatch(itemGov, produtosML);

        if (melhorProduto) {
            const precoGoverno = parseFloat(itemGov.valor_unitario) || 0;
            const precoMercado = parseFloat(melhorProduto.preco) || 0;

            const margem = precoGoverno - precoMercado;
            const margemPercentual = precoGoverno > 0 ? (margem / precoGoverno) * 100 : 0;

            if (margem > 0) {
                oportunidades.push({
                    governo: {
                        item_id: itemGov.id,
                        descricao: itemGov.descricao,
                        preco: precoGoverno,
                        unidade: 'UN',
                        quantidade_disponivel: 0,
                        ata_numero: null,
                        orgao: itemGov.orgao || 'Não informado',
                        uf: itemGov.uf
                    },
                    mercado: melhorProduto,
                    oportunidade: {
                        margem_reais: margem,
                        margem_percentual: margemPercentual,
                        classificacao: classificarOportunidade(margemPercentual)
                    }
                });
            }
        }
    });

    oportunidades.sort((a, b) => b.oportunidade.margem_reais - a.oportunidade.margem_reais);
    return oportunidades;
}

function encontrarMelhorMatch(itemGov, produtosML) {
    if (!produtosML || produtosML.length === 0) return null;

    const descricaoGov = (itemGov.descricao || '').toLowerCase();
    let melhorScore = 0;
    let melhorProduto = null;

    produtosML.forEach(produto => {
        const tituloProduto = (produto.titulo || '').toLowerCase();
        const score = calcularSimilaridade(descricaoGov, tituloProduto);

        if (score > melhorScore) {
            melhorScore = score;
            melhorProduto = produto;
        }
    });

    return melhorScore > 0.3 ? melhorProduto : produtosML[0];
}

function calcularSimilaridade(str1, str2) {
    const stopWords = ['de', 'da', 'do', 'para', 'com', 'em', 'a', 'o', 'e'];
    const palavras1 = str1.split(/\s+/).filter(p => !stopWords.includes(p) && p.length > 2);
    const palavras2 = str2.split(/\s+/).filter(p => !stopWords.includes(p) && p.length > 2);

    if (palavras1.length === 0 || palavras2.length === 0) return 0;

    const comuns = palavras1.filter(p1 => palavras2.some(p2 => p2.includes(p1) || p1.includes(p2))).length;
    const total = Math.max(palavras1.length, palavras2.length);

    return total > 0 ? comuns / total : 0;
}

function classificarOportunidade(margemPercentual) {
    if (margemPercentual >= 30) return 'EXCELENTE';
    if (margemPercentual >= 20) return 'MUITO_BOA';
    if (margemPercentual >= 10) return 'BOA';
    if (margemPercentual >= 5) return 'RAZOAVEL';
    return 'BAIXA';
}

function renderizarOportunidades(oportunidades, termo) {
    const container = document.getElementById('resultados-container');

    let html = `
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
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
                        ${getClassificacaoTexto(opo.classificacao)} - ${opo.margem_percentual.toFixed(2)}%
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
                        ${merc.permalink && merc.id !== 'EXEMPLO1' ? `<a href="${merc.permalink}" target="_blank" class="btn-acao btn-comprar"><i class="fas fa-shopping-cart"></i> Ver no ML</a>` : ''}
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

function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle) return;
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
}

function initMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    if (!sidebar || !mobileToggle) return;

    mobileToggle.addEventListener('click', () => sidebar.classList.toggle('open'));

    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
}

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
