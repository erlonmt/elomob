<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carrega a rede demonstrativa do Recife a partir de database/sql/02_seed.sql,
 * a mesma carga entregue como script, para que exista uma única fonte de dados.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $path = config('elomob.seed_sql_path');

        if (! is_file($path)) {
            throw new \RuntimeException("Carga não encontrada em {$path}.");
        }

        DB::unprepared(file_get_contents($path));
    }
}
