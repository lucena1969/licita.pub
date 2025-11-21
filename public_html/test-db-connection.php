<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== TESTE DE CONEXÃO COM BANCO ===\n\n";

$host = 'localhost';
$dbname = 'u590097272_licitapub';
$username = 'u590097272_neto';
$password = 'Numse!2020';

echo "Host: $host\n";
echo "Database: $dbname\n";
echo "Username: $username\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    echo "DSN: $dsn\n\n";

    echo "Tentando conectar...\n";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ CONEXÃO ESTABELECIDA COM SUCESSO!\n\n";

    // Testar query
    echo "Testando query...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM comparacoes_precos");
    $result = $stmt->fetch();

    echo "✓ Query executada com sucesso!\n";
    echo "Total de comparações: " . $result['total'] . "\n";

} catch (PDOException $e) {
    echo "✗ ERRO DE CONEXÃO!\n\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
