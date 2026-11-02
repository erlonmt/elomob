<?php

/**
 * Brazilian Portuguese messages for the validation rules the API uses. Rules missing here fall back
 * to Laravel's English messages, so a new rule needs its message added here.
 */
return [

    'array' => 'O campo :attribute deve ser uma lista.',
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha está incorreta.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'distinct' => 'O campo :attribute tem um valor repetido.',
    'email' => 'Informe um :attribute válido.',
    'enum' => 'O valor escolhido para :attribute é inválido.',
    'exists' => 'O valor escolhido para :attribute não existe.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'list' => 'O campo :attribute deve ser uma lista.',
    'max' => [
        'array' => 'Escolha no máximo :max itens em :attribute.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'array' => 'Escolha ao menos :min item em :attribute.',
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_with' => 'O campo :attribute é obrigatório quando :values é informado.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',

    'custom' => [
        'current_password' => [
            'current_password' => 'A senha atual está incorreta.',
        ],
        'label' => [
            'unique' => 'Você já tem um local salvo com este nome.',
        ],
        'transport_modes' => [
            'required' => 'Selecione ao menos um modo de transporte.',
        ],
    ],

    'attributes' => [
        'accessibility_needs' => 'tipos de necessidade',
        'current_password' => 'senha atual',
        'accessibility_needs.*' => 'tipo de necessidade',
        'departure_at' => 'horário de saída',
        'destination_id' => 'destino',
        'email' => 'e-mail',
        'label' => 'nome do local',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'mode' => 'modo de transporte',
        'modes' => 'transportes',
        'modes.*' => 'transporte',
        'name' => 'nome',
        'origin_id' => 'origem',
        'password' => 'senha',
        'preferred_route' => 'tipo de rota preferida',
        'q' => 'busca',
        'radius' => 'raio',
        'stop_id' => 'local',
        'transport_modes' => 'modos de transporte',
        'transport_modes.*' => 'modo de transporte',
    ],

];
