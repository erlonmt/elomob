<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TripSearchController extends Controller
{
    public function search(Request $request)
    {
        // 1. Recebe os dados do Front-end
        $origem = $request->input('origem');
        $destino = $request->input('destino');
        $data = $request->input('data');

        // 2. Aqui você chamaria o algoritmo do Erlon (Back-end)
        // Simulando a resposta do Back-end com base na pesquisa:
        $viagens = [
            [
                'tipo' => 'Trajeto Mais Rápido',
                'classe' => 'fastest',
                'cor' => '#2563eb',
                'tempo' => '25 min',
                'transporte' => 'Metro + Autocarro',
                'preco' => '4,50',
                'origem' => $origem,
                'destino' => $destino,
                'data' => $data
            ],
            [
                'tipo' => 'Opção Mais Económica',
                'classe' => 'cheapest',
                'cor' => '#10b981',
                'tempo' => '40 min',
                'transporte' => 'Autocarro',
                'preco' => '3,20',
                'origem' => $origem,
                'destino' => $destino,
                'data' => $data
            ],
            [
                'tipo' => 'Opção Sustentável',
                'classe' => 'sustainable',
                'cor' => '#8b5cf6',
                'tempo' => '30 min',
                'transporte' => 'Bicicleta',
                'preco' => '0,00',
                'origem' => $origem,
                'destino' => $destino,
                'data' => $data
            ]
        ];

        // 3. Devolve os dados processados para a View (Front-end)
        return view('resultados', compact('viagens', 'origem', 'destino', 'data'));
    }
}