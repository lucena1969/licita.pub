<?php

require_once __DIR__ . '/vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Repositories\UsuarioRepository;

try {
    $usuarioRepo = new UsuarioRepository();
    $resultado = $usuarioRepo->limparCamposVazios();

    echo "CPF/CNPJ vazios atualizados: {$resultado['cpf_cnpj_atualizados']}\n";
    echo "Telefones vazios atualizados: {$resultado['telefones_atualizados']}\n";
    echo "\nLimpeza concluída com sucesso!\n";
} catch (Exception $e) {
    echo "Erro ao limpar dados: " . $e->getMessage() . "\n";
    exit(1);
}
