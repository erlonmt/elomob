<?php

namespace App\Services\Planning;

use SplPriorityQueue;

/**
 * Fila que entrega primeiro a menor prioridade. Prioridades são tuplas
 * comparadas em ordem lexicográfica.
 *
 * @extends SplPriorityQueue<int, list<int>>
 */
final class MinPriorityQueue extends SplPriorityQueue
{
    public function compare(mixed $priority1, mixed $priority2): int
    {
        return $priority2 <=> $priority1;
    }
}
