/**
 * UTILS - Funções Utilitárias
 * Helpers para formatação e manipulação de dados
 */

const utils = {
    /**
     * Formatar valor monetário
     * @param {number} valor - Valor a ser formatado
     * @returns {string} - Valor formatado (ex: R$ 1.234,56)
     */
    formatarValor(valor) {
        if (valor === null || valor === undefined || isNaN(valor)) {
            return 'R$ 0,00';
        }

        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    },

    /**
     * Formatar data
     * @param {string} data - Data no formato YYYY-MM-DD ou ISO
     * @returns {string} - Data formatada (ex: 15/01/2025)
     */
    formatarData(data) {
        if (!data) return '--';

        try {
            const date = new Date(data + 'T00:00:00');
            return date.toLocaleDateString('pt-BR');
        } catch (e) {
            return data;
        }
    },

    /**
     * Formatar data e hora
     * @param {string} dataHora - Data/hora ISO
     * @returns {string} - Data/hora formatada (ex: 15/01/2025 14:30)
     */
    formatarDataHora(dataHora) {
        if (!dataHora) return '--';

        try {
            const date = new Date(dataHora);
            return date.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dataHora;
        }
    },

    /**
     * Truncar texto
     * @param {string} texto - Texto a ser truncado
     * @param {number} maxLength - Tamanho máximo
     * @returns {string} - Texto truncado com "..."
     */
    truncate(texto, maxLength = 100) {
        if (!texto) return '';
        if (texto.length <= maxLength) return texto;
        return texto.substring(0, maxLength) + '...';
    },

    /**
     * Formatar CPF/CNPJ
     * @param {string} documento - CPF ou CNPJ
     * @returns {string} - Documento formatado
     */
    formatarDocumento(documento) {
        if (!documento) return '';

        // Remove tudo que não é número
        documento = documento.replace(/\D/g, '');

        // CPF
        if (documento.length === 11) {
            return documento.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }

        // CNPJ
        if (documento.length === 14) {
            return documento.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }

        return documento;
    },

    /**
     * Formatar CNPJ (alias para formatarDocumento)
     * @param {string} cnpj - CNPJ
     * @returns {string} - CNPJ formatado
     */
    formatarCNPJ(cnpj) {
        return this.formatarDocumento(cnpj);
    },

    /**
     * Formatar CPF (alias para formatarDocumento)
     * @param {string} cpf - CPF
     * @returns {string} - CPF formatado
     */
    formatarCPF(cpf) {
        return this.formatarDocumento(cpf);
    },

    /**
     * Formatar número com separador de milhares
     * @param {number} numero - Número a ser formatado
     * @param {number} decimais - Quantidade de casas decimais
     * @returns {string} - Número formatado
     */
    formatarNumero(numero, decimais = 0) {
        if (numero === null || numero === undefined || isNaN(numero)) {
            return '0';
        }

        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: decimais,
            maximumFractionDigits: decimais
        }).format(numero);
    },

    /**
     * Calcular dias entre duas datas
     * @param {string} dataInicio - Data inicial
     * @param {string} dataFim - Data final
     * @returns {number} - Número de dias
     */
    diasEntre(dataInicio, dataFim) {
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);
        const diff = fim - inicio;
        return Math.floor(diff / (1000 * 60 * 60 * 24));
    },

    /**
     * Verificar se data está no futuro
     * @param {string} data - Data a verificar
     * @returns {boolean} - True se está no futuro
     */
    dataNoFuturo(data) {
        if (!data) return false;
        const dataCheck = new Date(data + 'T00:00:00');
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        return dataCheck > hoje;
    },

    /**
     * Verificar se data está no passado
     * @param {string} data - Data a verificar
     * @returns {boolean} - True se está no passado
     */
    dataNoPassado(data) {
        if (!data) return false;
        const dataCheck = new Date(data + 'T00:00:00');
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        return dataCheck < hoje;
    },

    /**
     * Debounce - Atrasa execução de função
     * @param {Function} func - Função a ser executada
     * @param {number} wait - Tempo de espera em ms
     * @returns {Function} - Função com debounce
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Copiar texto para área de transferência
     * @param {string} texto - Texto a copiar
     * @returns {Promise<boolean>} - True se copiou com sucesso
     */
    async copiarParaClipboard(texto) {
        try {
            await navigator.clipboard.writeText(texto);
            return true;
        } catch (err) {
            console.error('Erro ao copiar:', err);
            return false;
        }
    },

    /**
     * Gerar ID único
     * @returns {string} - ID único
     */
    gerarId() {
        return Date.now().toString(36) + Math.random().toString(36).substring(2);
    },

    /**
     * Sanitizar HTML
     * @param {string} html - HTML a ser sanitizado
     * @returns {string} - HTML sanitizado
     */
    sanitizeHtml(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    }
};
