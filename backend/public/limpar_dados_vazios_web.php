<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Repositories\UsuarioRepository;

try {
    $usuarioRepo = new UsuarioRepository();
    $resultado = $usuarioRepo->limparCamposVazios();

    echo json_encode([
        'success' => true,
        'message' => 'Limpeza concluída com sucesso!',
        'cpf_cnpj_atualizados' => $resultado['cpf_cnpj_atualizados'],
        'telefones_atualizados' => $resultado['telefones_atualizados'],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
