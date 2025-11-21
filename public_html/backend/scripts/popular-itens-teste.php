<?php
/**
 * Script: Popular Itens de Teste
 *
 * Cria itens de teste variados para demonstração do módulo de pesquisa de preços
 */

require_once __DIR__ . '/../public/api/bootstrap.php';

use App\Models\ItemAta;
use App\Repositories\ItemAtaRepository;
use App\Repositories\AtaRegistroPrecoRepository;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           POPULAR ITENS DE TESTE - PESQUISA DE PREÇOS        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$itemRepository = new ItemAtaRepository();
$ataRepository = new AtaRegistroPrecoRepository();

// Buscar algumas ATAs para vincular
$atas = $ataRepository->findVigentes(10);

if (empty($atas)) {
    echo "❌ Nenhuma ATA encontrada no banco!\n";
    exit(1);
}

echo "✅ Encontradas " . count($atas) . " ATAs vigentes\n";
echo "📝 Criando itens de teste variados...\n\n";

// Produtos comuns em licitações
$produtos = [
    // Tecnologia
    ['NOTEBOOK DELL LATITUDE 5420 I7 16GB 512GB SSD 14" WINDOWS 11 PRO', 'UN', 'DELL COMPUTADORES DO BRASIL LTDA', '72381189000110', 4200.00, 30],
    ['NOTEBOOK HP PROBOOK 450 G9 I5 8GB 256GB SSD 15.6" WINDOWS 11', 'UN', 'HP DO BRASIL INDUSTRIA COMERCIO LTDA', '61797924006865', 3500.00, 25],
    ['COMPUTADOR DESKTOP I5 8GB 240GB SSD WINDOWS 11 PRO', 'UN', 'POSITIVO TECNOLOGIA SA', '81243735000148', 2300.00, 50],
    ['MOUSE OPTICO USB PRETO 1600 DPI', 'UN', 'LOGITECH DO BRASIL COMERCIO LTDA', '51938039000106', 35.00, 200],
    ['TECLADO USB ABNT2 PRETO RESISTENTE A AGUA', 'UN', 'MULTILASER INDUSTRIAL SA', '59717553000100', 55.00, 200],
    ['MONITOR LED 27 POLEGADAS FULL HD HDMI DISPLAYPORT', 'UN', 'LG ELECTRONICS DO BRASIL LTDA', '01166372000140', 950.00, 80],
    ['IMPRESSORA LASER MONOCROMATICA A4 30PPM USB REDE', 'UN', 'BROTHER INTERNATIONAL CORPORATION', '82413554000107', 1100.00, 40],
    ['HD EXTERNO 1TB USB 3.0 PORTATIL', 'UN', 'SEAGATE TECHNOLOGY BRASIL LTDA', '05687984000194', 320.00, 100],
    ['WEBCAM FULL HD 1080P USB 2.0 COM MICROFONE INTEGRADO', 'UN', 'LOGITECH DO BRASIL COMERCIO LTDA', '51938039000106', 250.00, 60],

    // Material de Escritório
    ['PAPEL A4 SULFITE 75G ALCALINO RESMA 500 FOLHAS', 'RESMA', 'SUZANO PAPEL E CELULOSE SA', '16404287000155', 19.90, 1000],
    ['CANETA ESFEROGRAFICA AZUL 1.0MM CAIXA 50 UNIDADES', 'CAIXA', 'BIC AMAZONIA SA', '48343903000106', 14.50, 500],
    ['CANETA ESFEROGRAFICA PRETA 1.0MM CAIXA 50 UNIDADES', 'CAIXA', 'BIC AMAZONIA SA', '48343903000106', 14.50, 500],
    ['LAPIS PRETO N2 HB CAIXA 144 UNIDADES', 'CAIXA', 'FABER CASTELL DO BRASIL LTDA', '59562584000110', 85.00, 200],
    ['GRAMPEADOR METALI CO 26/6 CAPACIDADE 25 FOLHAS', 'UN', 'JOCAR OFFICE PAPELARIA LTDA', '03045242000174', 28.00, 150],
    ['TESOURA ESCOLAR 13CM PONTA ARREDONDADA', 'UN', 'FABER CASTELL DO BRASIL LTDA', '59562584000110', 12.00, 300],
    ['COLA BRANCA 90G TUBO', 'UN', 'HENKEL LTDA', '43874499000145', 3.50, 500],
    ['PASTA SUSPENSA KRAFT CAIXA 25 UNIDADES', 'CAIXA', 'DELLO INDUSTRIA E COMERCIO LTDA', '44264687000109', 75.00, 100],
    ['CLIPS GALVANIZADO N 2/0 CAIXA 500G', 'CAIXA', 'BIC AMAZONIA SA', '48343903000106', 9.50, 300],

    // Limpeza
    ['ALCOOL EM GEL 70% ANTISSEPTICO 500ML', 'UN', 'START QUIMICA LTDA', '23149142000106', 12.00, 500],
    ['PAPEL HIGIENICO FOLHA DUPLA BRANCO FARDO 64 ROLOS', 'FARDO', 'SANTHER FABRICACAO PAPEL LTDA', '51466077000101', 45.00, 200],
    ['SABONETE LIQUIDO NEUTRO 5 LITROS', 'UN', 'BOMBRIL SA', '51950391000100', 35.00, 150],
    ['DETERGENTE LIQUIDO NEUTRO 500ML', 'UN', 'BOMBRIL SA', '51950391000100', 2.80, 800],
    ['AGUA SANITARIA 2 LITROS', 'UN', 'BOMBRIL SA', '51950391000100', 4.50, 600],
    ['DESINFETANTE 2 LITROS DIVERSOS AROMAS', 'UN', 'BOMBRIL SA', '51950391000100', 8.90, 400],
    ['SABAO EM PO 1KG', 'UN', 'UNILEVER BRASIL LTDA', '01417840000144', 12.50, 300],
    ['LUVA DE BORRACHA AMARELA TAMANHO G PAR', 'PAR', 'DANNY INDUSTRIA COMERCIO LTDA', '44943929000191', 8.50, 200],
    ['VASSOURA DE PELO SINTETICO COM CABO', 'UN', 'FLASHLIMP INDUSTRIA COMERCIO LTDA', '02914474000130', 15.00, 150],

    // Móveis
    ['CADEIRA GIRATORIA EXECUTIVA BRACOS REGULAVEIS ENCOSTO LOMBAR', 'UN', 'FLEXFORM INDUSTRIA COMERCIO MOVEIS LTDA', '93238903000190', 780.00, 100],
    ['MESA ESCRITORIO RETANGULAR 150X70CM TAMPO MELAMINA', 'UN', 'FLEXFORM INDUSTRIA COMERCIO MOVEIS LTDA', '93238903000190', 620.00, 80],
    ['ARMARIO ALTO 2 PORTAS 1600X800X400MM MELAMINA', 'UN', 'FLEXFORM INDUSTRIA COMERCIO MOVEIS LTDA', '93238903000190', 850.00, 60],
    ['ARQUIVO DE ACO 4 GAVETAS 1320X470X660MM', 'UN', 'PANDIN METALURGICA LTDA', '92662741000172', 1200.00, 40],
    ['ESTANTE DE ACO 5 PRATELEIRAS 2000X900X400MM', 'UN', 'PANDIN METALURGICA LTDA', '92662741000172', 650.00, 50],

    // Veículos e Manutenção
    ['PNEU ARO 15 195/65R15 PARA AUTOMOVEL', 'UN', 'BRIDGESTONE DO BRASIL INDUSTRIA COMERCIO LTDA', '59230656000147', 380.00, 100],
    ['OLEO LUBRIFICANTE MOTOR 15W40 MINERAL LITRO', 'LITRO', 'PETROLEO BRASILEIRO SA PETROBRAS', '33000167008901', 18.50, 500],
    ['FILTRO DE OLEO MOTOR PARA VEICULOS LEVES', 'UN', 'TECFIL SA', '51775755000185', 25.00, 200],

    // Serviços
    ['MANUTENCAO PREDIAL PREVENTIVA HORA TECNICA', 'HORA', 'ENGEPRED ENGENHARIA PREDIAL LTDA', '12345678000190', 85.00, 2000],
    ['DESENVOLVIMENTO SOFTWARE WEB HORA PROGRAMADOR PLENO', 'HORA', 'STEFANINI CONSULTORIA INFORMATICA SA', '58069360000115', 180.00, 1000],
    ['SUPORTE TECNICO TI NIVEL 2 HORA TECNICA', 'HORA', 'STEFANINI CONSULTORIA INFORMATICA SA', '58069360000115', 120.00, 1500],
];

$totalInseridos = 0;
$erros = 0;

foreach ($produtos as $index => $produto) {
    try {
        // Selecionar uma ATA aleatória
        $ata = $atas[array_rand($atas)];

        [$descricao, $unidade, $fornecedor, $cnpj, $valor, $quantidade] = $produto;

        // Criar variação de preço (+/- 15%)
        $variacaoPreco = $valor * (rand(85, 115) / 100);
        $variacaoPreco = round($variacaoPreco, 2);

        $item = new ItemAta(
            $ata->id,
            $index + 1000, // Número item único
            $descricao,
            $unidade,
            $fornecedor,
            $cnpj,
            $variacaoPreco,
            $quantidade,
            $quantidade
        );

        $itemRepository->create($item);
        $totalInseridos++;

        echo "  ✅ {$descricao} - R\$ " . number_format($variacaoPreco, 2, ',', '.') . "/{$unidade}\n";

    } catch (\Exception $e) {
        echo "  ❌ Erro: " . $e->getMessage() . "\n";
        $erros++;
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ CONCLUÍDO!\n";
echo "   • Itens inseridos: {$totalInseridos}\n";
echo "   • Erros: {$erros}\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
