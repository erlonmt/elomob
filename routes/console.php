<?php

use Illuminate\Support\Facades\Schedule;

// Apaga buscas sem conta com mais de uma semana (TripSearch::prunable).
Schedule::command('model:prune')->daily();
