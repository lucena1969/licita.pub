<?php
/**
 * Ver estrutura da tabela licitacoes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$db = Database::getConnection();

echo "<h1>Estrutura da Tabela 'licitacoes'</h1>";

$stmt = $db->query("DESCRIBE licitacoes");
$colunas = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

foreach ($colunas as $col) {
    echo "<tr>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Exemplo de registro:</h2>";
$stmt = $db->query("SELECT * FROM licitacoes LIMIT 1");
$exemplo = $stmt->fetch();

echo "<pre>";
print_r($exemplo);
echo "</pre>";
