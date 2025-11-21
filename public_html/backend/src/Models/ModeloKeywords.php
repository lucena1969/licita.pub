<?php

namespace App\Models;

class ModeloKeywords
{
    public ?int $id = null;
    public string $palavra;
    public float $peso = 1.00;
    public int $ocorrencias = 0;
    public ?string $ultima_atualizacao = null;

    /**
     * Criar instância a partir de array
     */
    public static function fromArray(array $data): self
    {
        $keyword = new self();

        $keyword->id = isset($data['id']) ? (int)$data['id'] : null;
        $keyword->palavra = $data['palavra'];
        $keyword->peso = isset($data['peso']) ? (float)$data['peso'] : 1.00;
        $keyword->ocorrencias = isset($data['ocorrencias']) ? (int)$data['ocorrencias'] : 0;
        $keyword->ultima_atualizacao = $data['ultima_atualizacao'] ?? null;

        return $keyword;
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'palavra' => $this->palavra,
            'peso' => $this->peso,
            'ocorrencias' => $this->ocorrencias,
            'ultima_atualizacao' => $this->ultima_atualizacao,
        ];
    }

    /**
     * Incrementar feedback positivo (palavra foi útil)
     * Aumenta o peso em +0.1 (máximo 3.0)
     */
    public function feedbackPositivo(): void
    {
        $this->peso = min(3.0, $this->peso + 0.1);
        $this->ocorrencias++;
    }

    /**
     * Incrementar feedback negativo (palavra não foi útil)
     * Diminui o peso em -0.05 (mínimo 0.5)
     */
    public function feedbackNegativo(): void
    {
        $this->peso = max(0.5, $this->peso - 0.05);
        $this->ocorrencias++;
    }

    /**
     * Registrar uso da palavra (sem feedback explícito)
     */
    public function registrarUso(): void
    {
        $this->ocorrencias++;
    }

    /**
     * Verificar se a palavra tem alta relevância (peso >= 2.0)
     */
    public function isAltaRelevancia(): bool
    {
        return $this->peso >= 2.0;
    }

    /**
     * Verificar se a palavra tem baixa relevância (peso < 1.0)
     */
    public function isBaixaRelevancia(): bool
    {
        return $this->peso < 1.0;
    }

    /**
     * Obter classificação da relevância
     */
    public function getClassificacao(): string
    {
        if ($this->peso >= 2.5) return 'Muito Alta';
        if ($this->peso >= 2.0) return 'Alta';
        if ($this->peso >= 1.5) return 'Média-Alta';
        if ($this->peso >= 1.0) return 'Média';
        if ($this->peso >= 0.7) return 'Baixa';
        return 'Muito Baixa';
    }

    /**
     * Obter emoji baseado na relevância
     */
    public function getEmoji(): string
    {
        if ($this->peso >= 2.5) return '🔥';
        if ($this->peso >= 2.0) return '⭐';
        if ($this->peso >= 1.5) return '✅';
        if ($this->peso >= 1.0) return '➡️';
        if ($this->peso >= 0.7) return '⚠️';
        return '❌';
    }
}
