<?php

namespace App\Services;

class ValidatorService
{
    /**
     * Validar email
     */
    public static function validateEmail(string $email): array
    {
        $errors = [];

        if (empty($email)) {
            $errors[] = 'Email é obrigatório';
            return ['valid' => false, 'errors' => $errors];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }

        if (strlen($email) > 255) {
            $errors[] = 'Email muito longo (máximo 255 caracteres)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validar senha
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (empty($password)) {
            $errors[] = 'Senha é obrigatória';
            return ['valid' => false, 'errors' => $errors];
        }

        if (strlen($password) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres';
        }

        if (strlen($password) > 100) {
            $errors[] = 'Senha muito longa (máximo 100 caracteres)';
        }

        // Verificar se tem pelo menos uma letra e um número (opcional, mas recomendado)
        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Senha deve conter letras e números';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => self::getPasswordStrength($password),
        ];
    }

    /**
     * Calcular força da senha
     */
    private static function getPasswordStrength(string $password): string
    {
        $score = 0;

        if (strlen($password) >= 8) $score++;
        if (strlen($password) >= 12) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;

        if ($score <= 2) return 'fraca';
        if ($score <= 4) return 'media';
        return 'forte';
    }

    /**
     * Validar CPF
     */
    public static function validateCPF(string $cpf): bool
    {
        // Remover caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verificar se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validar CNPJ
     */
    public static function validateCNPJ(string $cnpj): bool
    {
        // Remover caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verificar se tem 14 dígitos
        if (strlen($cnpj) != 14) {
            return false;
        }

        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Validar primeiro dígito verificador
        $soma = 0;
        $peso = 5;
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $peso;
            $peso = ($peso == 2) ? 9 : $peso - 1;
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;

        if ($cnpj[12] != $digito1) {
            return false;
        }

        // Validar segundo dígito verificador
        $soma = 0;
        $peso = 6;
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $peso;
            $peso = ($peso == 2) ? 9 : $peso - 1;
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;

        return $cnpj[13] == $digito2;
    }

    /**
     * Validar CPF ou CNPJ
     */
    public static function validateCpfCnpj(?string $cpfCnpj): array
    {
        $errors = [];

        if (empty($cpfCnpj)) {
            // CPF/CNPJ é opcional
            return ['valid' => true, 'errors' => []];
        }

        // Remover caracteres não numéricos
        $clean = preg_replace('/[^0-9]/', '', $cpfCnpj);

        if (strlen($clean) == 11) {
            if (!self::validateCPF($clean)) {
                $errors[] = 'CPF inválido';
            }
        } elseif (strlen($clean) == 14) {
            if (!self::validateCNPJ($clean)) {
                $errors[] = 'CNPJ inválido';
            }
        } else {
            $errors[] = 'CPF/CNPJ deve ter 11 ou 14 dígitos';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validar telefone
     */
    public static function validateTelefone(?string $telefone): array
    {
        $errors = [];

        if (empty($telefone)) {
            // Telefone é opcional
            return ['valid' => true, 'errors' => []];
        }

        // Remover caracteres não numéricos
        $clean = preg_replace('/[^0-9]/', '', $telefone);

        // Verificar se tem 10 ou 11 dígitos (com DDD)
        if (strlen($clean) < 10 || strlen($clean) > 11) {
            $errors[] = 'Telefone deve ter 10 ou 11 dígitos (com DDD)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validar nome
     */
    public static function validateNome(string $nome): array
    {
        $errors = [];

        if (empty($nome)) {
            $errors[] = 'Nome é obrigatório';
            return ['valid' => false, 'errors' => $errors];
        }

        if (strlen($nome) < 3) {
            $errors[] = 'Nome deve ter no mínimo 3 caracteres';
        }

        if (strlen($nome) > 255) {
            $errors[] = 'Nome muito longo (máximo 255 caracteres)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validar dados completos de cadastro
     */
    public static function validateRegistro(array $data): array
    {
        $allErrors = [];

        // Validar email
        $emailValidation = self::validateEmail($data['email'] ?? '');
        if (!$emailValidation['valid']) {
            $allErrors = array_merge($allErrors, $emailValidation['errors']);
        }

        // Validar senha
        $passwordValidation = self::validatePassword($data['senha'] ?? '');
        if (!$passwordValidation['valid']) {
            $allErrors = array_merge($allErrors, $passwordValidation['errors']);
        }

        // Validar nome
        $nomeValidation = self::validateNome($data['nome'] ?? '');
        if (!$nomeValidation['valid']) {
            $allErrors = array_merge($allErrors, $nomeValidation['errors']);
        }

        // Validar CPF/CNPJ (opcional)
        $cpfCnpjValidation = self::validateCpfCnpj($data['cpf_cnpj'] ?? null);
        if (!$cpfCnpjValidation['valid']) {
            $allErrors = array_merge($allErrors, $cpfCnpjValidation['errors']);
        }

        // Validar telefone (opcional)
        $telefoneValidation = self::validateTelefone($data['telefone'] ?? null);
        if (!$telefoneValidation['valid']) {
            $allErrors = array_merge($allErrors, $telefoneValidation['errors']);
        }

        return [
            'valid' => empty($allErrors),
            'errors' => $allErrors,
        ];
    }
}
