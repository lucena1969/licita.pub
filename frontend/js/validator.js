/**
 * Validações Client-Side - Licita.pub
 * Validações de formulários e dados
 */

const Validator = {
    /**
     * Validar email
     */
    validateEmail(email) {
        const errors = [];

        if (!email || email.trim() === '') {
            errors.push('Email é obrigatório');
            return { valid: false, errors };
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errors.push('Email inválido');
        }

        if (email.length > 255) {
            errors.push('Email muito longo (máximo 255 caracteres)');
        }

        return {
            valid: errors.length === 0,
            errors,
        };
    },

    /**
     * Validar senha
     */
    validatePassword(password) {
        const errors = [];

        if (!password || password.trim() === '') {
            errors.push('Senha é obrigatória');
            return { valid: false, errors };
        }

        if (password.length < 6) {
            errors.push('Senha deve ter no mínimo 6 caracteres');
        }

        if (password.length > 100) {
            errors.push('Senha muito longa (máximo 100 caracteres)');
        }

        const hasLetter = /[a-zA-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);

        if (!hasLetter || !hasNumber) {
            errors.push('Senha deve conter letras e números');
        }

        return {
            valid: errors.length === 0,
            errors,
            strength: this.getPasswordStrength(password),
        };
    },

    /**
     * Calcular força da senha
     */
    getPasswordStrength(password) {
        let score = 0;

        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;

        if (score <= 2) return 'fraca';
        if (score <= 4) return 'media';
        return 'forte';
    },

    /**
     * Validar CPF
     */
    validateCPF(cpf) {
        // Remover caracteres não numéricos
        cpf = cpf.replace(/[^\d]/g, '');

        if (cpf.length !== 11) {
            return false;
        }

        // Verificar se todos os dígitos são iguais
        if (/^(\d)\1{10}$/.test(cpf)) {
            return false;
        }

        // Validar dígitos verificadores
        let sum = 0;
        let remainder;

        for (let i = 1; i <= 9; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }

        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(9, 10))) return false;

        sum = 0;
        for (let i = 1; i <= 10; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }

        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    },

    /**
     * Validar CNPJ
     */
    validateCNPJ(cnpj) {
        // Remover caracteres não numéricos
        cnpj = cnpj.replace(/[^\d]/g, '');

        if (cnpj.length !== 14) {
            return false;
        }

        // Verificar se todos os dígitos são iguais
        if (/^(\d)\1{13}$/.test(cnpj)) {
            return false;
        }

        // Validar primeiro dígito verificador
        let size = cnpj.length - 2;
        let numbers = cnpj.substring(0, size);
        let digits = cnpj.substring(size);
        let sum = 0;
        let pos = size - 7;

        for (let i = size; i >= 1; i--) {
            sum += numbers.charAt(size - i) * pos--;
            if (pos < 2) pos = 9;
        }

        let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        if (result != digits.charAt(0)) return false;

        // Validar segundo dígito verificador
        size = size + 1;
        numbers = cnpj.substring(0, size);
        sum = 0;
        pos = size - 7;

        for (let i = size; i >= 1; i--) {
            sum += numbers.charAt(size - i) * pos--;
            if (pos < 2) pos = 9;
        }

        result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        if (result != digits.charAt(1)) return false;

        return true;
    },

    /**
     * Validar CPF ou CNPJ
     */
    validateCpfCnpj(value) {
        const errors = [];

        if (!value || value.trim() === '') {
            // CPF/CNPJ é opcional
            return { valid: true, errors: [] };
        }

        const clean = value.replace(/[^\d]/g, '');

        if (clean.length === 11) {
            if (!this.validateCPF(clean)) {
                errors.push('CPF inválido');
            }
        } else if (clean.length === 14) {
            if (!this.validateCNPJ(clean)) {
                errors.push('CNPJ inválido');
            }
        } else {
            errors.push('CPF/CNPJ deve ter 11 ou 14 dígitos');
        }

        return {
            valid: errors.length === 0,
            errors,
        };
    },

    /**
     * Validar telefone
     */
    validateTelefone(telefone) {
        const errors = [];

        if (!telefone || telefone.trim() === '') {
            // Telefone é opcional
            return { valid: true, errors: [] };
        }

        const clean = telefone.replace(/[^\d]/g, '');

        if (clean.length < 10 || clean.length > 11) {
            errors.push('Telefone deve ter 10 ou 11 dígitos (com DDD)');
        }

        return {
            valid: errors.length === 0,
            errors,
        };
    },

    /**
     * Validar nome
     */
    validateNome(nome) {
        const errors = [];

        if (!nome || nome.trim() === '') {
            errors.push('Nome é obrigatório');
            return { valid: false, errors };
        }

        if (nome.length < 3) {
            errors.push('Nome deve ter no mínimo 3 caracteres');
        }

        if (nome.length > 255) {
            errors.push('Nome muito longo (máximo 255 caracteres)');
        }

        return {
            valid: errors.length === 0,
            errors,
        };
    },

    /**
     * Validar formulário completo de registro
     */
    validateRegistro(data) {
        const allErrors = [];

        // Validar email
        const emailValidation = this.validateEmail(data.email);
        if (!emailValidation.valid) {
            allErrors.push(...emailValidation.errors);
        }

        // Validar senha
        const passwordValidation = this.validatePassword(data.senha);
        if (!passwordValidation.valid) {
            allErrors.push(...passwordValidation.errors);
        }

        // Validar nome
        const nomeValidation = this.validateNome(data.nome);
        if (!nomeValidation.valid) {
            allErrors.push(...nomeValidation.errors);
        }

        // Validar CPF/CNPJ (opcional)
        const cpfCnpjValidation = this.validateCpfCnpj(data.cpf_cnpj);
        if (!cpfCnpjValidation.valid) {
            allErrors.push(...cpfCnpjValidation.errors);
        }

        // Validar telefone (opcional)
        const telefoneValidation = this.validateTelefone(data.telefone);
        if (!telefoneValidation.valid) {
            allErrors.push(...telefoneValidation.errors);
        }

        return {
            valid: allErrors.length === 0,
            errors: allErrors,
        };
    },
};

// Exportar para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Validator;
}
