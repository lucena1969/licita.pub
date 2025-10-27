/**
 * Máscaras de Input - Licita.pub
 * Formatação automática de campos
 */

const Masks = {
    /**
     * Aplicar máscara de CPF: 000.000.000-00
     */
    cpf(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        return value;
    },

    /**
     * Aplicar máscara de CNPJ: 00.000.000/0000-00
     */
    cnpj(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{2})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1/$2');
        value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        return value;
    },

    /**
     * Aplicar máscara de CPF ou CNPJ automaticamente
     */
    cpfCnpj(value) {
        value = value.replace(/\D/g, '');

        if (value.length <= 11) {
            return this.cpf(value);
        } else {
            return this.cnpj(value);
        }
    },

    /**
     * Aplicar máscara de telefone: (00) 00000-0000 ou (00) 0000-0000
     */
    telefone(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
        return value;
    },

    /**
     * Aplicar máscara de CEP: 00000-000
     */
    cep(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        return value;
    },

    /**
     * Aplicar máscara de valor monetário: R$ 0.000,00
     */
    money(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d)(\d{2})$/, '$1,$2');
        value = value.replace(/(?=(\d{3})+(\D))\B/g, '.');
        return 'R$ ' + value;
    },

    /**
     * Remover máscara (apenas números)
     */
    removeAll(value) {
        return value.replace(/\D/g, '');
    },

    /**
     * Aplicar máscara em um input em tempo real
     */
    apply(input, maskType) {
        input.addEventListener('input', (e) => {
            const start = e.target.selectionStart;
            const end = e.target.selectionEnd;
            const originalLength = e.target.value.length;

            // Aplicar máscara
            switch (maskType) {
                case 'cpf':
                    e.target.value = this.cpf(e.target.value);
                    break;
                case 'cnpj':
                    e.target.value = this.cnpj(e.target.value);
                    break;
                case 'cpf_cnpj':
                    e.target.value = this.cpfCnpj(e.target.value);
                    break;
                case 'telefone':
                    e.target.value = this.telefone(e.target.value);
                    break;
                case 'cep':
                    e.target.value = this.cep(e.target.value);
                    break;
                case 'money':
                    e.target.value = this.money(e.target.value);
                    break;
            }

            // Ajustar posição do cursor
            const newLength = e.target.value.length;
            const diff = newLength - originalLength;
            e.target.setSelectionRange(start + diff, end + diff);
        });
    },
};

// Exportar para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Masks;
}
