/**
 * MÓDULO: LICITAÇÕES
 * Gerencia a listagem e busca de licitações
 */

const LicitacoesModule = {
    // Estado atual
    state: {
        licitacoes: [],
        filtros: {
            q: '',
            uf: '',
            modalidade: '',
            dataInicio: '',
            dataFim: ''
        },
        paginacao: {
            paginaAtual: 1,
            itensPorPagina: 20,
            totalPaginas: 1,
            totalItens: 0
        },
        loading: false
    },

    /**
     * Renderizar página principal
     */
    async render() {
        const html = `
            <div class="licitacoes-page">
                <!-- Filtros -->
                <div class="filtros-container">
                    <div class="filtros-header">
                        <h2 class="filtros-title">
                            <i class="fas fa-filter"></i>
                            Filtros de Busca
                        </h2>
                    </div>

                    <form id="formFiltros" onsubmit="LicitacoesModule.handleBuscar(event)">
                        <div class="form-grid">
                            <!-- Palavra-chave -->
                            <div class="form-group">
                                <label class="form-label">Palavra-chave</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    id="filtroQ"
                                    placeholder="Digite para buscar..."
                                    value="${this.state.filtros.q}"
                                >
                            </div>

                            <!-- UF -->
                            <div class="form-group">
                                <label class="form-label">Estado (UF)</label>
                                <select class="form-select" id="filtroUF">
                                    <option value="">Todos</option>
                                    ${this.getUFOptions()}
                                </select>
                            </div>

                            <!-- Modalidade -->
                            <div class="form-group">
                                <label class="form-label">Modalidade</label>
                                <select class="form-select" id="filtroModalidade">
                                    <option value="">Todas</option>
                                    ${this.getModalidadeOptions()}
                                </select>
                            </div>

                            <!-- Data Início -->
                            <div class="form-group">
                                <label class="form-label">Data Início</label>
                                <input
                                    type="date"
                                    class="form-input"
                                    id="filtroDataInicio"
                                    value="${this.state.filtros.dataInicio}"
                                >
                            </div>

                            <!-- Data Fim -->
                            <div class="form-group">
                                <label class="form-label">Data Fim</label>
                                <input
                                    type="date"
                                    class="form-input"
                                    id="filtroDataFim"
                                    value="${this.state.filtros.dataFim}"
                                >
                            </div>
                        </div>

                        <div class="filtros-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="LicitacoesModule.limparFiltros()">
                                <i class="fas fa-eraser"></i>
                                Limpar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Resultados -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Resultados</h2>
                        <span class="total-resultados" id="totalResultados">-- licitações encontradas</span>
                    </div>
                    <div class="card-body">
                        <div id="listaLicitacoes">
                            <!-- Conteúdo será carregado aqui -->
                        </div>
                    </div>
                </div>
            </div>
        `;

        router.render(html);

        // Carregar licitações inicial
        await this.carregarLicitacoes();
    },

    /**
     * Buscar licitações
     */
    async handleBuscar(event) {
        if (event) event.preventDefault();

        // Atualizar filtros do estado
        this.state.filtros.q = document.getElementById('filtroQ')?.value || '';
        this.state.filtros.uf = document.getElementById('filtroUF')?.value || '';
        this.state.filtros.modalidade = document.getElementById('filtroModalidade')?.value || '';
        this.state.filtros.dataInicio = document.getElementById('filtroDataInicio')?.value || '';
        this.state.filtros.dataFim = document.getElementById('filtroDataFim')?.value || '';

        // Resetar paginação
        this.state.paginacao.paginaAtual = 1;

        // Carregar licitações
        await this.carregarLicitacoes();
    },

    /**
     * Limpar filtros
     */
    limparFiltros() {
        this.state.filtros = {
            q: '',
            uf: '',
            modalidade: '',
            dataInicio: '',
            dataFim: ''
        };

        document.getElementById('filtroQ').value = '';
        document.getElementById('filtroUF').value = '';
        document.getElementById('filtroModalidade').value = '';
        document.getElementById('filtroDataInicio').value = '';
        document.getElementById('filtroDataFim').value = '';

        this.handleBuscar();
    },

    /**
     * Carregar licitações da API
     */
    async carregarLicitacoes() {
        const listaEl = document.getElementById('listaLicitacoes');
        if (!listaEl) return;

        // Mostrar loading
        listaEl.innerHTML = `
            <div class="loading-container">
                <div class="spinner"></div>
                <p>Carregando licitações...</p>
            </div>
        `;

        try {
            // Montar query params
            const params = new URLSearchParams({
                pagina: this.state.paginacao.paginaAtual,
                limite: this.state.paginacao.itensPorPagina,
                ...this.state.filtros
            });

            // Fazer requisição
            const response = await api.get(`/licitacoes/buscar.php?${params.toString()}`);

            // A resposta vem como { success: true, data: { success: true, data: {...} } }
            if (response.success && response.data) {
                const apiData = response.data;

                if (apiData.success && apiData.data) {
                    this.state.licitacoes = apiData.data.licitacoes || [];
                    this.state.paginacao.totalItens = apiData.data.total || 0;
                    this.state.paginacao.totalPaginas = Math.ceil(this.state.paginacao.totalItens / this.state.paginacao.itensPorPagina);

                    this.renderLicitacoes();
                } else {
                    throw new Error(apiData.message || 'Erro ao carregar licitações');
                }
            } else {
                throw new Error('Erro ao conectar com o servidor');
            }
        } catch (error) {
            console.error('Erro ao carregar licitações:', error);
            listaEl.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Erro ao carregar licitações: ${error.message}</span>
                </div>
            `;
        }
    },

    /**
     * Renderizar lista de licitações
     */
    renderLicitacoes() {
        const listaEl = document.getElementById('listaLicitacoes');
        const totalEl = document.getElementById('totalResultados');

        if (!listaEl) return;

        // Atualizar total
        if (totalEl) {
            totalEl.textContent = `${this.state.paginacao.totalItens} licitações encontradas`;
        }

        // Se não houver resultados
        if (!this.state.licitacoes || this.state.licitacoes.length === 0) {
            listaEl.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Nenhuma licitação encontrada</h3>
                    <p>Tente ajustar os filtros de busca</p>
                </div>
            `;
            return;
        }

        // Renderizar itens
        const itemsHTML = this.state.licitacoes.map(lic => `
            <div class="item" onclick="LicitacoesModule.verDetalhes('${lic.id}')">
                <div class="item-header">
                    <span class="item-title">${lic.numero || lic.pncp_id}</span>
                    <span class="status-badge status-${lic.situacao?.toLowerCase() || 'active'}">
                        ${lic.situacao || 'ATIVO'}
                    </span>
                </div>
                <div class="item-description">
                    ${utils.truncate(lic.objeto, 200)}
                </div>
                <div class="item-meta">
                    <span class="item-meta-item">
                        <i class="fas fa-building"></i>
                        ${lic.nome_orgao || '--'}
                    </span>
                    <span class="item-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        ${lic.municipio}, ${lic.uf}
                    </span>
                    <span class="item-meta-item">
                        <i class="fas fa-calendar"></i>
                        ${utils.formatarData(lic.data_publicacao)}
                    </span>
                    ${lic.valor_estimado ? `
                        <span class="item-meta-item">
                            <i class="fas fa-dollar-sign"></i>
                            ${utils.formatarValor(lic.valor_estimado)}
                        </span>
                    ` : ''}
                    <span class="item-meta-item">
                        <i class="fas fa-gavel"></i>
                        ${lic.modalidade || '--'}
                    </span>
                </div>
            </div>
        `).join('');

        listaEl.innerHTML = `
            <div class="item-list">
                ${itemsHTML}
            </div>
            ${this.renderPaginacao()}
        `;
    },

    /**
     * Renderizar paginação
     */
    renderPaginacao() {
        if (this.state.paginacao.totalPaginas <= 1) return '';

        const { paginaAtual, totalPaginas } = this.state.paginacao;

        let pages = [];

        // Primeira página
        pages.push(1);

        // Páginas ao redor da atual
        for (let i = Math.max(2, paginaAtual - 1); i <= Math.min(totalPaginas - 1, paginaAtual + 1); i++) {
            if (!pages.includes(i)) pages.push(i);
        }

        // Última página
        if (!pages.includes(totalPaginas)) pages.push(totalPaginas);

        const buttonsHTML = pages.map((page, index) => {
            // Adicionar "..." se houver gap
            const gap = index > 0 && page - pages[index - 1] > 1 ? '<span class="pagination-dots">...</span>' : '';

            return `
                ${gap}
                <button
                    class="pagination-btn ${page === paginaAtual ? 'active' : ''}"
                    onclick="LicitacoesModule.irParaPagina(${page})"
                >
                    ${page}
                </button>
            `;
        }).join('');

        return `
            <div class="pagination">
                <button
                    class="pagination-btn"
                    onclick="LicitacoesModule.irParaPagina(${paginaAtual - 1})"
                    ${paginaAtual === 1 ? 'disabled' : ''}
                >
                    <i class="fas fa-chevron-left"></i>
                </button>

                ${buttonsHTML}

                <button
                    class="pagination-btn"
                    onclick="LicitacoesModule.irParaPagina(${paginaAtual + 1})"
                    ${paginaAtual === totalPaginas ? 'disabled' : ''}
                >
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        `;
    },

    /**
     * Ir para página específica
     */
    async irParaPagina(pagina) {
        if (pagina < 1 || pagina > this.state.paginacao.totalPaginas) return;

        this.state.paginacao.paginaAtual = pagina;
        await this.carregarLicitacoes();

        // Scroll para o topo
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    /**
     * Ver detalhes de uma licitação
     */
    verDetalhes(id) {
        // TODO: Implementar página de detalhes
        window.open(`/frontend/detalhes.html?id=${id}`, '_blank');
    },

    /**
     * Opções de UF
     */
    getUFOptions() {
        const ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
        return ufs.map(uf => `<option value="${uf}" ${this.state.filtros.uf === uf ? 'selected' : ''}>${uf}</option>`).join('');
    },

    /**
     * Opções de Modalidade
     */
    getModalidadeOptions() {
        const modalidades = [
            'PREGAO_ELETRONICO',
            'PREGAO_PRESENCIAL',
            'CONCORRENCIA',
            'TOMADA_PRECOS',
            'CONVITE',
            'DISPENSA',
            'INEXIGIBILIDADE'
        ];
        return modalidades.map(mod => {
            const label = mod.replace(/_/g, ' ');
            return `<option value="${mod}" ${this.state.filtros.modalidade === mod ? 'selected' : ''}>${label}</option>`;
        }).join('');
    }
};
