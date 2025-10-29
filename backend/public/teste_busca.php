<?php
/**
 * Script de teste para verificar consultas no banco
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carregar .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Conectar ao banco
$host = $_ENV['DB_HOST'];
$name = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Teste de Busca - Licita.pub</h1>";

    // 1. Total de licitações
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM licitacoes');
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Total de licitações: {$total['total']}</h2>";

    // 2. Total por UF
    $stmt = $pdo->query('SELECT uf, COUNT(*) as total FROM licitacoes GROUP BY uf ORDER BY uf');
    $ufs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Licitações por UF:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>UF</th><th>Total</th></tr>";
    foreach ($ufs as $uf) {
        echo "<tr><td>{$uf['uf']}</td><td>{$uf['total']}</td></tr>";
    }
    echo "</table>";

    // 3. Teste específico: Santa Catarina (SC)
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM licitacoes WHERE uf = ?');
    $stmt->execute(['SC']);
    $sc = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>Licitações em Santa Catarina (SC): {$sc['total']}</h2>";

    if ($sc['total'] > 0) {
        // Mostrar 5 exemplos de SC
        $stmt = $pdo->prepare('SELECT pncp_id, numero_controle, objeto_simplificado, municipio FROM licitacoes WHERE uf = ? LIMIT 5');
        $stmt->execute(['SC']);
        $exemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Exemplos:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>PNCP ID</th><th>Número</th><th>Município</th><th>Objeto</th></tr>";
        foreach ($exemplos as $ex) {
            echo "<tr>";
            echo "<td>{$ex['pncp_id']}</td>";
            echo "<td>{$ex['numero_controle']}</td>";
            echo "<td>{$ex['municipio']}</td>";
            echo "<td>" . substr($ex['objeto_simplificado'], 0, 80) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 4. Verificar estrutura da tabela
    $stmt = $pdo->query('DESCRIBE licitacoes');
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Estrutura da tabela 'licitacoes':</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    foreach ($colunas as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<h1 style='color: red;'>Erro: " . $e->getMessage() . "</h1>";
}
