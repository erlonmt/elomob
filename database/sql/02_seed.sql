-- =============================================================================
-- ELOMOB · Carga demonstrativa da rede de transporte do Recife
--
-- Dados exclusivamente demonstrativos, sem contas pessoais: coordenadas,
-- tarifas, horários e recursos de acessibilidade precisam ser substituídos por
-- fontes validadas antes de qualquer uso público. Executar depois de
-- 01_schema.sql. Cada tabela termina ajustando sua sequência de IDs.
--
-- Tarifas em centavos, durações em minutos e distâncias em metros.
-- =============================================================================

BEGIN;

-- Modais ----------------------------------------------------------------------

INSERT INTO transport_modes (id, code, name, color) VALUES
    (1, 'bus',   'Ônibus',     '#028090'),
    (2, 'metro', 'Metrô',      '#3f51b5'),
    (3, 'bike',  'Bicicleta',  '#2ec4b6'),
    (4, 'taxi',  'Táxi',       '#ffb300'),
    (5, 'app',   'Aplicativo', '#9c27b0'),
    (6, 'walk',  'Caminhada',  '#526b74');
SELECT setval(pg_get_serial_sequence('transport_modes', 'id'), (SELECT MAX(id) FROM transport_modes));

-- Paradas ---------------------------------------------------------------------
-- accessible: sem degraus · tactile_paving: piso tátil · visual_signs: painel.
-- bikes_available/docks_available: só nas estações com bicicletas.

INSERT INTO stops (id, name, address, latitude, longitude, transport_mode_id, accessible, bikes_available, docks_available, tactile_paving, visual_signs) VALUES
    ( 1, 'Boa Viagem',             'Av. Boa Viagem',                   -8.1190000, -34.8990000, 1, true,  NULL, NULL, true,  true),
    ( 2, 'Estação Joana Bezerra',  'TI Joana Bezerra',                 -8.0740000, -34.8970000, 2, true,  NULL, NULL, true,  true),
    ( 3, 'Estação Recife',         'Praça Visconde de Mauá',           -8.0690000, -34.8820000, 2, false, NULL, NULL, true,  true),
    ( 4, 'Recife Antigo',          'Praça do Marco Zero',              -8.0630000, -34.8710000, 1, true,     4,    8, true,  true),
    ( 5, 'Cordeiro',               'Av. Caxangá',                      -8.0430000, -34.9320000, 1, true,  NULL, NULL, true,  true),
    ( 6, 'Casa Amarela',           'Estrada do Arraial',               -8.0240000, -34.9180000, 1, false, NULL, NULL, false, false),
    ( 7, 'Várzea',                 'Cidade Universitária',             -8.0490000, -34.9500000, 1, true,  NULL, NULL, false, false),
    ( 8, 'Derby',                  'Av. Agamenon Magalhães, 1500',     -8.0570000, -34.9000000, 1, true,  NULL, NULL, true,  true),
    ( 9, 'Afogados',               'Largo da Paz',                     -8.0800000, -34.9090000, 1, true,  NULL, NULL, true,  false),
    (10, 'Boa Vista',              'Rua da Aurora',                    -8.0560000, -34.8840000, 1, true,  NULL, NULL, true,  true),
    (11, 'Graças',                 'Rua das Graças, 142',              -8.0390000, -34.9020000, 1, true,  NULL, NULL, false, false),
    (12, 'Terminal Integrado CDU', 'Av. Caxangá',                      -8.0360000, -34.9470000, 1, true,  NULL, NULL, true,  true),
    (13, 'Bike Recife — Est. 12',  'Estação demonstrativa Boa Viagem', -8.1170000, -34.8970000, 3, true,     4,    8, false, false),
    (14, 'Pina',                   'Av. Herculano Bandeira',           -8.0900000, -34.8850000, 1, true,  NULL, NULL, true,  true),
    (15, 'Cais de Santa Rita',     'Terminal Cais de Santa Rita',      -8.0680000, -34.8780000, 1, true,  NULL, NULL, false, true),
    (16, 'Estação Imbiribeira',    'Av. Mascarenhas de Morais',        -8.0995000, -34.9105000, 2, true,  NULL, NULL, true,  true),
    (17, 'Estação Aeroporto',      'Praça Ministro Salgado Filho',     -8.1265000, -34.9177000, 2, true,  NULL, NULL, true,  true);
SELECT setval(pg_get_serial_sequence('stops', 'id'), (SELECT MAX(id) FROM stops));

-- Linhas ----------------------------------------------------------------------

INSERT INTO lines (id, transport_mode_id, code, name, starts_at, ends_at, frequency_minutes, fare_cents) VALUES
    (1, 1, '042',    'Boa Viagem → Centro',                          '05:00:00', '23:00:00', 12, 380),
    (2, 1, '081',    'Cordeiro → Recife Antigo',                     '05:30:00', '22:30:00', 15, 380),
    (3, 1, '033',    'Casa Amarela → Afogados',                      '05:15:00', '22:00:00', 18, 380),
    (4, 2, 'CENTRO', 'Linha Centro',                                 '05:00:00', '23:00:00', 10, 160),
    (5, 2, 'SUL',    'Linha Sul',                                    '05:00:00', '23:00:00', 15, 160),
    (6, 1, '061',    'Boa Viagem → Cais de Santa Rita (Via Mangue)', '05:30:00', '22:00:00', 20, 380),
    (7, 1, 'BRT',    'BRT Caxangá (TI CDU → Boa Vista)',             '05:30:00', '22:30:00', 10, 380);
SELECT setval(pg_get_serial_sequence('lines', 'id'), (SELECT MAX(id) FROM lines));

-- Conexões --------------------------------------------------------------------
-- Um registro por sentido. Com linha, a tarifa vem de lines.fare_cents e
-- boarding_fare_cents fica NULL; sem linha, a conexão cobra a própria tarifa.
-- adapted vale true nos trechos a pé, porque não há veículo.

INSERT INTO connections (id, from_stop_id, to_stop_id, transport_mode_id, line_id, minutes, distance_meters, boarding_fare_cents, accessible, has_stairs, adapted, audible_signals, visual_signs) VALUES
    ( 1,  1,  2, 1,    1, 18, 4200, NULL, true,  false, true,  true,  false),  -- Linha 042: Boa Viagem → Estação Joana Bezerra
    ( 2,  2,  1, 1,    1, 18, 4200, NULL, true,  false, true,  true,  false),  -- Linha 042: Estação Joana Bezerra → Boa Viagem
    ( 3,  2,  3, 2,    4, 10, 1800, NULL, false, true,  true,  true,  false),  -- Linha CENTRO: Estação Joana Bezerra → Estação Recife
    ( 4,  3,  2, 2,    4, 10, 1800, NULL, false, true,  true,  true,  false),  -- Linha CENTRO: Estação Recife → Estação Joana Bezerra
    ( 5,  3,  4, 6, NULL,  7,  550,    0, true,  false, true,  false, true),   -- Caminhada: Estação Recife → Recife Antigo
    ( 6,  4,  3, 6, NULL,  7,  550,    0, true,  false, true,  false, true),   -- Caminhada: Recife Antigo → Estação Recife
    ( 7,  1,  4, 4, NULL, 25, 7800, 1850, true,  false, true,  true,  true),   -- Táxi: Boa Viagem → Recife Antigo
    ( 8,  4,  1, 4, NULL, 25, 7800, 1850, true,  false, true,  true,  true),   -- Táxi: Recife Antigo → Boa Viagem
    ( 9,  1,  4, 5, NULL, 29, 7800, 1600, true,  false, false, true,  true),   -- Aplicativo: Boa Viagem → Recife Antigo
    (10,  4,  1, 5, NULL, 29, 7800, 1600, true,  false, false, true,  true),   -- Aplicativo: Recife Antigo → Boa Viagem
    (11,  1, 13, 6, NULL,  3,  200,    0, true,  false, true,  true,  true),   -- Caminhada: Boa Viagem → Bike Recife — Est. 12
    (12, 13,  1, 6, NULL,  3,  200,    0, true,  false, true,  true,  true),   -- Caminhada: Bike Recife — Est. 12 → Boa Viagem
    (13, 13,  4, 3, NULL, 42, 8200,  380, true,  false, false, false, true),   -- Bicicleta: Bike Recife — Est. 12 → Recife Antigo
    (14,  4, 13, 3, NULL, 42, 8200,  380, true,  false, false, false, true),   -- Bicicleta: Recife Antigo → Bike Recife — Est. 12
    (15,  5,  8, 1,    2, 14, 3500, NULL, true,  false, true,  true,  false),  -- Linha 081: Cordeiro → Derby
    (16,  8,  5, 1,    2, 14, 3500, NULL, true,  false, true,  true,  false),  -- Linha 081: Derby → Cordeiro
    (17,  8, 10, 1,    2,  8, 1800, NULL, true,  false, true,  true,  false),  -- Linha 081: Derby → Boa Vista
    (18, 10,  8, 1,    2,  8, 1800, NULL, true,  false, true,  true,  false),  -- Linha 081: Boa Vista → Derby
    (19, 10,  4, 1,    2, 10, 2100, NULL, true,  false, true,  true,  false),  -- Linha 081: Boa Vista → Recife Antigo
    (20,  4, 10, 1,    2, 10, 2100, NULL, true,  false, true,  true,  false),  -- Linha 081: Recife Antigo → Boa Vista
    (21,  6, 11, 1,    3, 12, 2600, NULL, true,  false, false, true,  false),  -- Linha 033: Casa Amarela → Graças
    (22, 11,  6, 1,    3, 12, 2600, NULL, true,  false, false, true,  false),  -- Linha 033: Graças → Casa Amarela
    (23, 11,  8, 1,    3,  8, 1900, NULL, true,  false, false, true,  false),  -- Linha 033: Graças → Derby
    (24,  8, 11, 1,    3,  8, 1900, NULL, true,  false, false, true,  false),  -- Linha 033: Derby → Graças
    (25,  8,  9, 1,    3, 15, 3200, NULL, true,  false, false, true,  false),  -- Linha 033: Derby → Afogados
    (26,  9,  8, 1,    3, 15, 3200, NULL, true,  false, false, true,  false),  -- Linha 033: Afogados → Derby
    (27,  9,  2, 6, NULL, 12,  950,    0, false, false, true,  true,  true),   -- Caminhada: Afogados → Estação Joana Bezerra
    (28,  2,  9, 6, NULL, 12,  950,    0, false, false, true,  true,  true),   -- Caminhada: Estação Joana Bezerra → Afogados
    (29,  7, 12, 1,    2,  8, 1800, NULL, true,  false, true,  true,  false),  -- Linha 081: Várzea → Terminal Integrado CDU
    (30, 12,  7, 1,    2,  8, 1800, NULL, true,  false, true,  true,  false),  -- Linha 081: Terminal Integrado CDU → Várzea
    (31, 12,  5, 1,    2, 10, 2200, NULL, true,  false, true,  true,  false),  -- Linha 081: Terminal Integrado CDU → Cordeiro
    (32,  5, 12, 1,    2, 10, 2200, NULL, true,  false, true,  true,  false),  -- Linha 081: Cordeiro → Terminal Integrado CDU
    (33,  2,  3, 2,    5, 13, 1800, NULL, true,  false, true,  true,  true),   -- Linha SUL: Estação Joana Bezerra → Estação Recife
    (34,  3,  2, 2,    5, 13, 1800, NULL, true,  false, true,  true,  true),   -- Linha SUL: Estação Recife → Estação Joana Bezerra
    (35,  2, 16, 2,    5,  6, 3300, NULL, true,  false, true,  true,  true),   -- Linha SUL: Estação Joana Bezerra → Estação Imbiribeira
    (36, 16,  2, 2,    5,  6, 3300, NULL, true,  false, true,  true,  true),   -- Linha SUL: Estação Imbiribeira → Estação Joana Bezerra
    (37, 16, 17, 2,    5,  6, 3200, NULL, true,  false, true,  true,  true),   -- Linha SUL: Estação Imbiribeira → Estação Aeroporto
    (38, 17, 16, 2,    5,  6, 3200, NULL, true,  false, true,  true,  true),   -- Linha SUL: Estação Aeroporto → Estação Imbiribeira
    (39,  1, 14, 1,    6, 18, 4500, NULL, true,  false, true,  true,  true),   -- Linha 061: Boa Viagem → Pina
    (40, 14,  1, 1,    6, 18, 4500, NULL, true,  false, true,  true,  true),   -- Linha 061: Pina → Boa Viagem
    (41, 14, 15, 1,    6, 22, 5000, NULL, true,  false, true,  true,  true),   -- Linha 061: Pina → Cais de Santa Rita
    (42, 15, 14, 1,    6, 22, 5000, NULL, true,  false, true,  true,  true),   -- Linha 061: Cais de Santa Rita → Pina
    (43, 12,  5, 1,    7, 10, 2200, NULL, true,  false, true,  true,  true),   -- Linha BRT: Terminal Integrado CDU → Cordeiro
    (44,  5, 12, 1,    7, 10, 2200, NULL, true,  false, true,  true,  true),   -- Linha BRT: Cordeiro → Terminal Integrado CDU
    (45,  5,  8, 1,    7, 14, 3500, NULL, true,  false, true,  true,  true),   -- Linha BRT: Cordeiro → Derby
    (46,  8,  5, 1,    7, 14, 3500, NULL, true,  false, true,  true,  true),   -- Linha BRT: Derby → Cordeiro
    (47,  8, 10, 1,    7,  8, 1800, NULL, true,  false, true,  true,  true),   -- Linha BRT: Derby → Boa Vista
    (48, 10,  8, 1,    7,  8, 1800, NULL, true,  false, true,  true,  true),   -- Linha BRT: Boa Vista → Derby
    (49, 15,  4, 6, NULL, 12,  950,    0, true,  false, true,  true,  true),   -- Caminhada: Cais de Santa Rita → Recife Antigo
    (50,  4, 15, 6, NULL, 12,  950,    0, true,  false, true,  true,  true),   -- Caminhada: Recife Antigo → Cais de Santa Rita
    (51, 10,  4, 6, NULL, 18, 1300,    0, true,  false, true,  true,  true),   -- Caminhada: Boa Vista → Recife Antigo
    (52,  4, 10, 6, NULL, 18, 1300,    0, true,  false, true,  true,  true);   -- Caminhada: Recife Antigo → Boa Vista
SELECT setval(pg_get_serial_sequence('connections', 'id'), (SELECT MAX(id) FROM connections));

-- Integrações -----------------------------------------------------------------
-- Um registro por sentido. Só a Estação Joana Bezerra integra a tarifa.

INSERT INTO integrations (id, stop_id, from_mode_id, to_mode_id, transfer_minutes, description, accessible, fare_integrated) VALUES
    (1,  2, 1, 2, 3, 'Terminal integrado: ônibus 042 e Linhas Centro e Sul do metrô', true,  true),   -- Estação Joana Bezerra: Ônibus → Metrô
    (2,  3, 2, 3, 2, 'Estação de bicicletas demonstrativa, com degraus na saída',     false, false),  -- Estação Recife: Metrô → Bicicleta
    (3, 12, 1, 3, 5, 'Integração demonstrativa na CDU',                               true,  false),  -- Terminal Integrado CDU: Ônibus → Bicicleta
    (4,  2, 2, 1, 3, 'Terminal integrado: ônibus 042 e Linhas Centro e Sul do metrô', true,  true),   -- Estação Joana Bezerra: Metrô → Ônibus
    (5,  3, 3, 2, 2, 'Estação de bicicletas demonstrativa, com degraus na saída',     false, false),  -- Estação Recife: Bicicleta → Metrô
    (6, 12, 3, 1, 5, 'Integração demonstrativa na CDU',                               true,  false);  -- Terminal Integrado CDU: Bicicleta → Ônibus
SELECT setval(pg_get_serial_sequence('integrations', 'id'), (SELECT MAX(id) FROM integrations));

COMMIT;