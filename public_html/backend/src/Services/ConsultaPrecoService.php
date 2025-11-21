<?php

namespace App\Services;

use App\Repositories\ItemAtaRepository;
use App\Repositories\AtaRegistroPrecoRepository;

/**
 * Service: ConsultaPrecoService
 *
 * Lógica de negócio para consulta de preços
 * Usado por servidores públicos para pesquisa de mercado
 */
class ConsultaPrecoService
{
    private ItemAtaRepository $itemRepository;
    private AtaRegistroPrecoRepository $ataRepository;

    public function __construct(
        ItemAtaRepository $itemRepository,
        AtaRegistroPrecoRepository $ataRepository
    ) {
        $this->itemRepository = $itemRepository;
        $this->ataRepository = $ataRepository;
    }

    /**
     * Buscar preços por descrição do produto/serviço
     */
    public function buscarPrecos(string $descricao, array $filtros = []): array
    {
        // Validar descrição mínima
        if (strlen($descricao) < 3) {
            throw new \InvalidArgumentException('Descrição deve ter no mínimo 3 caracteres');
        }

        // Aplicar filtros padrão
        $filtrosCompletos = array_merge([
            'vigente' => true, // Apenas atas vigentes por padrão
            'com_saldo' => false, // Não filtrar por saldo (queremos histórico)
            'orderBy' => 'valor_unitario',
            'order' => 'ASC',
            'limit' => 100,
            'offset' => 0
        ], $filtros);

        // Buscar itens
        $itens = $this->itemRepository->buscarPorDescricao($descricao, $filtrosCompletos);

        // Se não encontrou com FULLTEXT, tentar busca por palavra-chave
        if (empty($itens)) {
            $itens = $this->itemRepository->buscarPorPalavraChave($descricao, $filtrosCompletos);
        }

        // Enriquecer itens com dados adicionais
        $itensEnriquecidos = array_map(function($item) {
            return $this->enriquecerItem($item);
        }, $itens);

        return $itensEnriquecidos;
    }

    /**
     * Obter estatísticas de preços
     */
    public function obterEstatisticas(string $descricao, array $filtros = []): array
    {
        // Validar descrição
        if (strlen($descricao) < 3) {
            throw new \InvalidArgumentException('Descrição deve ter no mínimo 3 caracteres');
        }

        // Filtros para estatísticas
        $filtrosStats = array_merge([
            'vigente' => true
        ], $filtros);

        // Buscar estatísticas
        $stats = $this->itemRepository->obterEstatisticasPreco($descricao, $filtrosStats);

        // Se não há dados, retornar zeros
        if (!$stats || $stats['total_registros'] == 0) {
            return [
                'total_registros' => 0,
                'menor_preco' => null,
                'maior_preco' => null,
                'preco_medio' => null,
                'preco_mediano' => null,
                'desvio_padrao' => null,
                'percentil_25' => null,
                'percentil_75' => null,
            ];
        }

        // Calcular mediana (precisa buscar os valores ordenados)
        $precosOrdenados = $this->obterPrecosOrdenados($descricao, $filtrosStats);
        $mediana = $this->calcularMediana($precosOrdenados);
        $percentil25 = $this->calcularPercentil($precosOrdenados, 25);
        $percentil75 = $this->calcularPercentil($precosOrdenados, 75);

        return [
            'total_registros' => (int) $stats['total_registros'],
            'menor_preco' => (float) $stats['menor_preco'],
            'maior_preco' => (float) $stats['maior_preco'],
            'preco_medio' => (float) $stats['preco_medio'],
            'preco_mediano' => $mediana,
            'desvio_padrao' => (float) ($stats['desvio_padrao'] ?? 0),
            'percentil_25' => $percentil25,
            'percentil_75' => $percentil75,
        ];
    }

    /**
     * Buscar preços agrupados por UF
     */
    public function buscarPorUF(string $descricao, array $filtros = []): array
    {
        // Buscar todos os itens
        $itens = $this->buscarPrecos($descricao, array_merge($filtros, ['limit' => 1000]));

        // Agrupar por UF
        $porUF = [];

        foreach ($itens as $item) {
            $uf = $item['uf'] ?? 'N/D';

            if (!isset($porUF[$uf])) {
                $porUF[$uf] = [
                    'uf' => $uf,
                    'quantidade' => 0,
                    'itens' => []
                ];
            }

            $porUF[$uf]['quantidade']++;
            $porUF[$uf]['itens'][] = $item;
        }

        // Calcular estatísticas por UF
        foreach ($porUF as $uf => &$dados) {
            $precos = array_filter(array_column($dados['itens'], 'valor_unitario'));

            $dados['menor_preco'] = !empty($precos) ? min($precos) : null;
            $dados['maior_preco'] = !empty($precos) ? max($precos) : null;
            $dados['preco_medio'] = !empty($precos) ? array_sum($precos) / count($precos) : null;
        }

        // Ordenar por quantidade (UFs com mais registros primeiro)
        uasort($porUF, function($a, $b) {
            return $b['quantidade'] - $a['quantidade'];
        });

        return array_values($porUF);
    }

    /**
     * Gerar dados para relatório PDF
     */
    public function gerarDadosRelatorio(string $descricao, array $itensSelecionados, array $opcoes = []): array
    {
        // Buscar estatísticas
        $stats = $this->obterEstatisticas($descricao, $opcoes['filtros'] ?? []);

        // Buscar itens selecionados
        $itens = [];
        foreach ($itensSelecionados as $itemId) {
            $item = $this->itemRepository->findById($itemId);
            if ($item) {
                $itens[] = $this->enriquecerItem($item);
            }
        }

        // Ordenar por preço
        usort($itens, function($a, $b) {
            return $a['valor_unitario'] <=> $b['valor_unitario'];
        });

        // Dados do relatório
        return [
            'descricao_pesquisada' => $descricao,
            'data_pesquisa' => date('d/m/Y H:i:s'),
            'periodo' => [
                'inicio' => $opcoes['data_inicio'] ?? null,
                'fim' => $opcoes['data_fim'] ?? null,
            ],
            'filtros_aplicados' => $opcoes['filtros'] ?? [],
            'estatisticas' => $stats,
            'itens' => $itens,
            'total_itens_selecionados' => count($itens),
            'observacoes' => $opcoes['observacoes'] ?? '',
            'conclusao' => $this->gerarConclusao($stats),
        ];
    }

    /**
     * Buscar itens similares (sugestões)
     */
    public function buscarSimilares(string $itemId, int $limit = 5): array
    {
        $itens = $this->itemRepository->buscarSimilares($itemId, $limit);

        return array_map(function($item) {
            return $this->enriquecerItem($item);
        }, $itens);
    }

    /**
     * Obter itens com menores preços (ranking)
     */
    public function obterMelhoresOfertas(int $limit = 10): array
    {
        $itens = $this->itemRepository->obterMenoresPrecos($limit);

        return array_map(function($item) {
            return $this->enriquecerItem($item);
        }, $itens);
    }

    /**
     * Validar se usuário pode fazer consulta (limites de plano)
     */
    public function validarLimiteConsultas(string $usuarioId, string $plano): bool
    {
        // TODO: Implementar verificação de limites por plano
        // FREE: 3 consultas/dia
        // ESSENCIAL: ilimitadas
        // PROFISSIONAL: ilimitadas + extras
        // INSTITUCIONAL: ilimitadas + API

        return true;
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Enriquecer item com dados formatados e calculados
     */
    private function enriquecerItem($item): array
    {
        $array = is_object($item) ? $item->toArray() : $item;

        // Adicionar campos calculados
        $array['preco_formatado'] = $this->formatarPreco($array['valor_unitario'] ?? 0);
        $array['quantidade_formatada'] = $this->formatarQuantidade(
            $array['quantidade_disponivel'] ?? 0,
            $array['unidade'] ?? 'UN'
        );

        // Calcular economia vs média (se houver estatísticas)
        // TODO: Implementar quando necessário

        return $array;
    }

    /**
     * Obter preços ordenados para cálculos estatísticos
     */
    private function obterPrecosOrdenados(string $descricao, array $filtros): array
    {
        $itens = $this->itemRepository->buscarPorDescricao($descricao, array_merge($filtros, [
            'orderBy' => 'valor_unitario',
            'order' => 'ASC',
            'limit' => 1000
        ]));

        // Extrair apenas valores válidos
        $precos = [];
        foreach ($itens as $item) {
            $valor = is_object($item) ? $item->valor_unitario : $item['valor_unitario'];
            if ($valor !== null && $valor > 0) {
                $precos[] = (float) $valor;
            }
        }

        return $precos;
    }

    /**
     * Calcular mediana
     */
    private function calcularMediana(array $valores): ?float
    {
        if (empty($valores)) {
            return null;
        }

        sort($valores);
        $count = count($valores);
        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($valores[$middle - 1] + $valores[$middle]) / 2;
        }

        return $valores[$middle];
    }

    /**
     * Calcular percentil
     */
    private function calcularPercentil(array $valores, int $percentil): ?float
    {
        if (empty($valores)) {
            return null;
        }

        sort($valores);
        $index = ceil((count($valores) * $percentil) / 100) - 1;
        $index = max(0, min($index, count($valores) - 1));

        return $valores[$index];
    }

    /**
     * Formatar preço para exibição
     */
    private function formatarPreco(?float $valor): string
    {
        if ($valor === null || $valor === 0) {
            return 'Não informado';
        }

        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Formatar quantidade
     */
    private function formatarQuantidade(float $quantidade, string $unidade): string
    {
        return number_format($quantidade, 2, ',', '.') . ' ' . $unidade;
    }

    /**
     * Gerar conclusão automática para relatório
     */
    private function gerarConclusao(array $stats): string
    {
        if ($stats['total_registros'] == 0) {
            return 'Nenhum registro encontrado para análise.';
        }

        $media = $stats['preco_medio'];
        $mediana = $stats['preco_mediano'];

        $conclusao = sprintf(
            'Com base na análise de %d registros de atas de registro de preços, ',
            $stats['total_registros']
        );

        $conclusao .= sprintf(
            'o preço médio praticado é de R$ %.2f, ',
            $media
        );

        $conclusao .= sprintf(
            'com preços variando entre R$ %.2f (mínimo) e R$ %.2f (máximo). ',
            $stats['menor_preco'],
            $stats['maior_preco']
        );

        // Sugerir valor de referência (pode ser mediana ou média, dependendo da variação)
        $variacaoPercentual = (($stats['maior_preco'] - $stats['menor_preco']) / $stats['menor_preco']) * 100;

        if ($variacaoPercentual > 50) {
            $conclusao .= sprintf(
                'Devido à alta variação nos preços (%.0f%%), sugere-se utilizar a mediana de R$ %.2f como valor de referência.',
                $variacaoPercentual,
                $mediana
            );
        } else {
            $conclusao .= sprintf(
                'Sugere-se utilizar o preço médio de R$ %.2f como valor de referência para a contratação.',
                $media
            );
        }

        return $conclusao;
    }
}
