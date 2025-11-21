/**
 * MÓDULO: PESQUISA DE PREÇOS (ARPs)
 * Gerencia a pesquisa de preços praticados em Atas de Registro de Preço
 */

const PrecosModule = {
    // Estado atual
    state: {
        atas: [],
        filtros: {
            produto: '',
            uf: '',
            vigente: true
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
            <div class="precos-page">
                <!-- Header com destaque -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Pesquisa de Preços</strong>
                        <p style="margin: 5px 0 0 0; font-size: 0.85rem;">
                            Consulte preços praticados pelo governo em Atas de Registro de Preço (ARPs) para precificar suas propostas.
                        </p>
                    </div>
                </div>

                <!-- Filtros de Busca -->
                <div class="filtros-container">
                    <div class="filtros-header">
                        <h2 class="filtros-title">
                            <i class="fas fa-search"></i>
                            Buscar Produtos
                        </h2>
                    </div>

                    <form id="formFiltrosPrecos" onsubmit="PrecosModule.handleBuscar(event)">
                        <div class="form-grid">
                            <!-- Produto -->
                            <div class="form-group">
                                <label class="form-label">Produto ou Serviço</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    id="filtroProduto"
                                    placeholder="Ex: notebook, papel A4, caneta..."
                                    value="${this.state.filtros.produto}"
                                >
                                <small style="color: #666; font-size: 0.8rem;">
                                    Digite palavras-chave do produto que deseja pesquisar
                                </small>
                            </div>

                            <!-- UF -->
                            <div class="form-group">
                                <label class="form-label">Estado (UF)</label>
                                <select class="form-select" id="filtroUFPrecos">
                                    <option value="">Todos os Estados</option>
                                    ${this.getUFOptions()}
                                </select>
                            </div>

                            <!-- Apenas Vigentes -->
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="filtroVigente">
                                    <option value="true" ${this.state.filtros.vigente ? 'selected' : ''}>
                                        Apenas Vigentes
                                    </option>
                                    <option value="false" ${!this.state.filtros.vigente ? 'selected' : ''}>
                                        Todas (inclusive expiradas)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="filtros-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Pesquisar Preços
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="PrecosModule.limparFiltros()">
                                <i class="fas fa-eraser"></i>
                                Limpar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Resultados -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Resultados da Pesquisa</h2>
                        <span class="total-resultados" id="totalResultadosPrecos">-- produtos encontrados</span>
                    </div>
                    <div class="card-body">
                        <div id="listaPrecos">
                            ${this.renderEmptyState()}
                        </div>
                    </div>
                </div>

                <!-- Card Informativo -->
                <div class="card" style="background-color: #f8f9fa; border-color: #dee2e6;">
                    <h3 style="font-size: 1rem; color: #1351b4; margin-bottom: 15px;">
                        <i class="fas fa-lightbulb"></i>
                        Como usar a Pesquisa de Preços
                    </h3>
                    <ul style="margin-left: 20px; line-height: 1.8;">
                        <li><strong>Digite o produto:</strong> Use palavras-chave como "notebook", "papel A4", "mouse"</li>
                        <li><strong>Filtre por estado:</strong> Veja preços praticados na sua região</li>
                        <li><strong>Compare preços:</strong> Analise valores mínimo, médio e máximo</li>
                        <li><strong>Verifique vigência:</strong> Confirme se a ARP ainda está válida</li>
                        <li><strong>Use para precificar:</strong> Base sua proposta em preços reais do governo</li>
                    </ul>
                </div>
            </div>
        `;

        router.render(html);
    },

    /**
     * Estado vazio inicial
     */
    renderEmptyState() {
        return `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Pesquise por produtos ou serviços</h3>
                <p>Digite palavras-chave no campo acima e clique em "Pesquisar Preços" para ver os resultados</p>
            </div>
        `;
    },

    /**
     * Buscar preços
     */
    async handleBuscar(event) {
        if (event) event.preventDefault();

        // Atualizar filtros do estado
        this.state.filtros.produto = document.getElementById('filtroProduto')?.value || '';
        this.state.filtros.uf = document.getElementById('filtroUFPrecos')?.value || '';
        this.state.filtros.vigente = document.getElementById('filtroVigente')?.value === 'true';

        // Validar
        if (!this.state.filtros.produto || this.state.filtros.produto.length < 3) {
            alert('Digite pelo menos 3 caracteres para pesquisar');
            return;
        }

        // Resetar paginação
        this.state.paginacao.paginaAtual = 1;

        // Carregar preços
        await this.carregarPrecos();
    },

    /**
     * Limpar filtros
     */
    limparFiltros() {
        this.state.filtros = {
            produto: '',
            uf: '',
            vigente: true
        };

        document.getElementById('filtroProduto').value = '';
        document.getElementById('filtroUFPrecos').value = '';
        document.getElementById('filtroVigente').value = 'true';

        document.getElementById('listaPrecos').innerHTML = this.renderEmptyState();
        document.getElementById('totalResultadosPrecos').textContent = '-- produtos encontrados';
    },

    /**
     * Carregar preços da API
     */
    async carregarPrecos() {
        const listaEl = document.getElementById('listaPrecos');
        if (!listaEl) return;

        // Mostrar loading
        listaEl.innerHTML = `
            <div class="loading-container">
                <div class="spinner"></div>
                <p>Pesquisando preços...</p>
            </div>
        `;

        try {
            // TODO: Implementar chamada real à API quando o backend estiver pronto
            // Por enquanto, simular delay e mostrar mensagem
            await new Promise(resolve => setTimeout(resolve, 1500));

            // Mensagem temporária
            listaEl.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-tools"></i>
                    <div>
                        <strong>Funcionalidade em Desenvolvimento</strong>
                        <p style="margin: 5px 0 0 0;">
                            A pesquisa de preços será implementada na próxima etapa.
                            O backend está sendo desenvolvido para processar ${this.state.atas.length || 5} ARPs
                            e ${24} itens já sincronizados no banco de dados.
                        </p>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <h3 style="font-size: 1rem; margin-bottom: 15px;">
                        <i class="fas fa-clipboard-check"></i>
                        Próximas Etapas
                    </h3>
                    <ol style="margin-left: 20px; line-height: 1.8;">
                        <li>Criar Models e Repositories para ARPs</li>
                        <li>Desenvolver API de pesquisa de preços</li>
                        <li>Implementar algoritmo de normalização de produtos</li>
                        <li>Criar interface de resultados com comparação</li>
                        <li>Adicionar gráficos e estatísticas</li>
                    </ol>
                </div>
            `;

            /*
            // Código para quando o backend estiver pronto:
            const params = new URLSearchParams({
                produto: this.state.filtros.produto,
                uf: this.state.filtros.uf,
                vigente: this.state.filtros.vigente,
                pagina: this.state.paginacao.paginaAtual,
                limite: this.state.paginacao.itensPorPagina
            });

            const response = await api.get(`/atas/pesquisar-produto?${params.toString()}`);

            if (response.success && response.data) {
                this.state.atas = response.data.itens || [];
                this.state.paginacao.totalItens = response.data.total || 0;
                this.renderResultados();
            }
            */

        } catch (error) {
            console.error('Erro ao carregar preços:', error);
            listaEl.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Erro ao carregar preços: ${error.message}</span>
                </div>
            `;
        }
    },

    /**
     * Renderizar resultados (será implementado)
     */
    renderResultados() {
        // TODO: Implementar visualização dos resultados
        // Incluirá:
        // - Lista de itens encontrados
        // - Comparação de preços (min, avg, max)
        // - Gráficos
        // - Link para a ARP original
        // - Informações do órgão gerenciador
    },

    /**
     * Opções de UF
     */
    getUFOptions() {
        const ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
        return ufs.map(uf => `<option value="${uf}" ${this.state.filtros.uf === uf ? 'selected' : ''}>${uf}</option>`).join('');
    }
};
