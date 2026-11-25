<?php

return [

    // Fuso usado para comparar a saída pedida com o horário das linhas.
    'timezone' => env('ELOMOB_TIMEZONE', 'America/Recife'),

    // Carga demonstrativa usada pelo DatabaseSeeder (mesma do script entregue).
    'seed_sql_path' => env('ELOMOB_SEED_SQL', base_path('../database/sql/02_seed.sql')),

    // Por quanto tempo a rede carregada fica no cache sob cada versão.
    'network_cache_ttl_minutes' => (int) env('ELOMOB_NETWORK_CACHE_TTL', 1440),

    // Raio padrão e máximo, em metros, da busca de paradas próximas.
    'nearby_radius_meters' => 800,
    'nearby_max_radius_meters' => 5000,

    // Emissão demonstrativa por passageiro, em gramas de CO2 por km, usada pelo
    // critério "mais sustentável". Substituir por fonte validada.
    'emission_grams_per_km' => [
        'walk' => 0,
        'bike' => 0,
        'metro' => 25,
        'bus' => 70,
        'app' => 130,
        'taxi' => 130,
    ],

];
