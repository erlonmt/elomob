<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Nenhum trajeto atende à busca com os modais e necessidades pedidos.
 */
class NoRouteFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Nenhum trajeto encontrado com os modais e as necessidades de acessibilidade escolhidos.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
