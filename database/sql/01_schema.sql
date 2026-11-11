-- =============================================================================
-- ELOMOB · Estrutura do banco (PostgreSQL 16+ com PostGIS)
--
-- Estado final consolidado das migrations do Laravel: o banco criado por este
-- script é igual ao produzido por `php artisan migrate`. Aplicar em banco vazio.
--
-- Organização:
--   0. Extensões
--   1. Rede de transporte   transport_modes, stops, lines, connections,
--                           integrations, network_versions
--   2. Usuários             users, saved_places
--   3. Buscas e resultados  trip_searches, itineraries, itinerary_steps
--   4. Funções e gatilhos   geometria das conexões e versão da rede
--   5. Infraestrutura       tabelas padrão do Laravel e registro de migrations
--
-- Convenções: valores monetários em centavos (inteiros), durações em minutos,
-- distâncias em metros, datas em UTC. Regras numéricas ficam em CHECK, porque
-- o `unsigned` do Laravel não tem efeito no PostgreSQL.
-- =============================================================================

BEGIN;

-- -----------------------------------------------------------------------------
-- 0. Extensões
-- -----------------------------------------------------------------------------

CREATE EXTENSION IF NOT EXISTS postgis;

-- -----------------------------------------------------------------------------
-- 1. Rede de transporte
-- -----------------------------------------------------------------------------

-- Modais: ônibus, metrô, bicicleta, táxi, aplicativo e caminhada.
CREATE TABLE transport_modes (
    id    BIGSERIAL   PRIMARY KEY,
    code  VARCHAR(20) NOT NULL,
    name  VARCHAR(40) NOT NULL,
    color VARCHAR(7)  NOT NULL,

    CONSTRAINT transport_modes_code_unique UNIQUE (code)
);

-- Paradas, estações e pontos de embarque. `location` é gerado das coordenadas.
CREATE TABLE stops (
    id                BIGSERIAL     PRIMARY KEY,
    name              VARCHAR(120)  NOT NULL,
    address           VARCHAR(200)  NOT NULL,
    latitude          NUMERIC(10,7) NOT NULL,
    longitude         NUMERIC(10,7) NOT NULL,
    transport_mode_id BIGINT        NOT NULL,
    accessible        BOOLEAN       NOT NULL DEFAULT true,   -- acesso sem degraus
    bikes_available   SMALLINT,
    docks_available   SMALLINT,
    location          geography(Point, 4326) GENERATED ALWAYS AS (
                          ST_SetSRID(ST_MakePoint(longitude::double precision, latitude::double precision), 4326)::geography
                      ) STORED,
    tactile_paving    BOOLEAN       NOT NULL DEFAULT false,  -- piso tátil
    visual_signs      BOOLEAN       NOT NULL DEFAULT false,  -- painel de informações

    CONSTRAINT stops_name_unique UNIQUE (name),
    CONSTRAINT stops_transport_mode_id_foreign
        FOREIGN KEY (transport_mode_id) REFERENCES transport_modes (id) ON DELETE RESTRICT,
    CONSTRAINT stops_coordinates
        CHECK (latitude BETWEEN -90 AND 90 AND longitude BETWEEN -180 AND 180),
    CONSTRAINT stops_bike_counts
        CHECK (bikes_available >= 0 AND docks_available >= 0)
);

CREATE INDEX stops_transport_mode_id_index ON stops (transport_mode_id);
CREATE INDEX stops_location_gist           ON stops USING GIST (location);

-- Linhas: janela de funcionamento, frequência e tarifa.
CREATE TABLE lines (
    id                BIGSERIAL    PRIMARY KEY,
    transport_mode_id BIGINT       NOT NULL,
    code              VARCHAR(20)  NOT NULL,
    name              VARCHAR(150) NOT NULL,
    starts_at         TIME(0)      NOT NULL,
    ends_at           TIME(0)      NOT NULL,
    frequency_minutes SMALLINT     NOT NULL,
    fare_cents        INTEGER      NOT NULL,

    CONSTRAINT lines_code_unique UNIQUE (code),
    -- Alvo da chave composta de connections, que mantém o modal igual ao da linha.
    CONSTRAINT lines_id_transport_mode_id_unique UNIQUE (id, transport_mode_id),
    CONSTRAINT lines_transport_mode_id_foreign
        FOREIGN KEY (transport_mode_id) REFERENCES transport_modes (id) ON DELETE RESTRICT,
    CONSTRAINT lines_valid
        CHECK (frequency_minutes > 0 AND fare_cents >= 0)
);

CREATE INDEX lines_transport_mode_id_index ON lines (transport_mode_id);

-- Trechos dirigidos entre duas paradas. Com linha, cobra a tarifa da linha;
-- sem linha (táxi, aplicativo, bicicleta, caminhada), cobra boarding_fare_cents.
CREATE TABLE connections (
    id                  BIGSERIAL PRIMARY KEY,
    from_stop_id        BIGINT    NOT NULL,
    to_stop_id          BIGINT    NOT NULL,
    transport_mode_id   BIGINT    NOT NULL,
    line_id             BIGINT,
    minutes             SMALLINT  NOT NULL,
    distance_meters     INTEGER   NOT NULL,
    boarding_fare_cents INTEGER,
    accessible          BOOLEAN   NOT NULL DEFAULT true,   -- transitável em cadeira de rodas
    has_stairs          BOOLEAN   NOT NULL DEFAULT false,
    adapted             BOOLEAN   NOT NULL DEFAULT true,   -- veículo com elevador ou rampa
    path                geometry(LineString, 4326),        -- mantido por gatilho
    audible_signals     BOOLEAN   NOT NULL DEFAULT false,
    visual_signs        BOOLEAN   NOT NULL DEFAULT false,

    -- Várias linhas podem atender o mesmo trecho; sem linha, um trecho por modal.
    CONSTRAINT connections_stops_mode_line_unique
        UNIQUE NULLS NOT DISTINCT (from_stop_id, to_stop_id, transport_mode_id, line_id),
    CONSTRAINT connections_from_stop_id_foreign
        FOREIGN KEY (from_stop_id) REFERENCES stops (id) ON DELETE RESTRICT,
    CONSTRAINT connections_to_stop_id_foreign
        FOREIGN KEY (to_stop_id) REFERENCES stops (id) ON DELETE RESTRICT,
    CONSTRAINT connections_transport_mode_id_foreign
        FOREIGN KEY (transport_mode_id) REFERENCES transport_modes (id) ON DELETE RESTRICT,
    CONSTRAINT connections_line_id_foreign
        FOREIGN KEY (line_id) REFERENCES lines (id) ON DELETE RESTRICT,
    CONSTRAINT connections_line_id_transport_mode_id_foreign
        FOREIGN KEY (line_id, transport_mode_id) REFERENCES lines (id, transport_mode_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT connections_valid
        CHECK (from_stop_id <> to_stop_id AND minutes > 0 AND distance_meters > 0 AND boarding_fare_cents >= 0),
    CONSTRAINT connections_fare_source
        CHECK ((line_id IS NULL) <> (boarding_fare_cents IS NULL))
);

CREATE INDEX connections_from_stop_id_transport_mode_id_index ON connections (from_stop_id, transport_mode_id);
CREATE INDEX connections_to_stop_id_index                     ON connections (to_stop_id);
CREATE INDEX connections_transport_mode_id_index              ON connections (transport_mode_id);
CREATE INDEX connections_line_id_transport_mode_id_index      ON connections (line_id, transport_mode_id);
CREATE INDEX connections_path_gist                            ON connections USING GIST (path);

-- Troca de modal em uma parada, em um sentido. A carga registra os dois sentidos.
CREATE TABLE integrations (
    id               BIGSERIAL    PRIMARY KEY,
    stop_id          BIGINT       NOT NULL,
    from_mode_id     BIGINT       NOT NULL,
    to_mode_id       BIGINT       NOT NULL,
    transfer_minutes SMALLINT     NOT NULL,
    description      VARCHAR(200) NOT NULL,
    accessible       BOOLEAN      NOT NULL DEFAULT true,
    fare_integrated  BOOLEAN      NOT NULL DEFAULT false,

    CONSTRAINT integrations_stop_id_from_mode_id_to_mode_id_unique
        UNIQUE (stop_id, from_mode_id, to_mode_id),
    CONSTRAINT integrations_stop_id_foreign
        FOREIGN KEY (stop_id) REFERENCES stops (id) ON DELETE RESTRICT,
    CONSTRAINT integrations_from_mode_id_foreign
        FOREIGN KEY (from_mode_id) REFERENCES transport_modes (id) ON DELETE RESTRICT,
    CONSTRAINT integrations_to_mode_id_foreign
        FOREIGN KEY (to_mode_id) REFERENCES transport_modes (id) ON DELETE RESTRICT,
    CONSTRAINT integrations_valid
        CHECK (transfer_minutes >= 0)
);

CREATE INDEX integrations_from_mode_id_index ON integrations (from_mode_id);
CREATE INDEX integrations_to_mode_id_index   ON integrations (to_mode_id);

-- Versão atual da rede (linha única), renovada por gatilho. O planejador guarda
-- a rede no Redis sob essa versão.
CREATE TABLE network_versions (
    id      SMALLINT    PRIMARY KEY,
    version VARCHAR(36) NOT NULL,

    CONSTRAINT network_versions_single_row CHECK (id = 1)
);

INSERT INTO network_versions (id, version) VALUES (1, gen_random_uuid()::text);

-- -----------------------------------------------------------------------------
-- 2. Usuários
-- -----------------------------------------------------------------------------

CREATE TABLE users (
    id                  BIGSERIAL    PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL,
    email_verified_at   TIMESTAMP(0),
    password            VARCHAR(255) NOT NULL,
    remember_token      VARCHAR(100),
    created_at          TIMESTAMP(0),
    updated_at          TIMESTAMP(0),
    preferred_route     VARCHAR(20)  NOT NULL DEFAULT 'fastest',
    transport_modes     JSONB,                                  -- modais preferidos
    accessibility_needs JSONB        NOT NULL DEFAULT '[]'::jsonb,

    CONSTRAINT users_email_unique UNIQUE (email),
    CONSTRAINT users_preferred_route
        CHECK (preferred_route IN ('fastest', 'cheapest', 'transfers', 'sustainable')),
    CONSTRAINT users_accessibility_needs
        CHECK (jsonb_typeof(accessibility_needs) = 'array'
               AND accessibility_needs <@ '["wheelchair","reduced_mobility","visual","hearing"]'::jsonb)
);

-- Paradas favoritas, com nome único por usuário.
CREATE TABLE saved_places (
    id         BIGSERIAL   PRIMARY KEY,
    user_id    BIGINT      NOT NULL,
    stop_id    BIGINT      NOT NULL,
    label      VARCHAR(60) NOT NULL,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),

    CONSTRAINT saved_places_user_id_label_unique UNIQUE (user_id, label),
    CONSTRAINT saved_places_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT saved_places_stop_id_foreign
        FOREIGN KEY (stop_id) REFERENCES stops (id) ON DELETE RESTRICT
);

CREATE INDEX saved_places_stop_id_index ON saved_places (stop_id);

-- -----------------------------------------------------------------------------
-- 3. Buscas e resultados
-- -----------------------------------------------------------------------------

-- Busca de trajeto. Sem conta, fica ligada ao hash da sessão; departure_at vazio
-- significa sair agora. `filters` guarda modais e necessidades da busca.
CREATE TABLE trip_searches (
    id             BIGSERIAL   PRIMARY KEY,
    user_id        BIGINT,
    session_hash   VARCHAR(64) NOT NULL,
    origin_id      BIGINT      NOT NULL,
    destination_id BIGINT      NOT NULL,
    filters        JSONB       NOT NULL,
    created_at     TIMESTAMP(0),
    updated_at     TIMESTAMP(0),
    departure_at   TIMESTAMP(0),

    CONSTRAINT trip_searches_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT trip_searches_origin_id_foreign
        FOREIGN KEY (origin_id) REFERENCES stops (id) ON DELETE RESTRICT,
    CONSTRAINT trip_searches_destination_id_foreign
        FOREIGN KEY (destination_id) REFERENCES stops (id) ON DELETE RESTRICT,
    CONSTRAINT searches_distinct_places
        CHECK (origin_id <> destination_id)
);

CREATE INDEX trip_searches_user_id_created_at_index ON trip_searches (user_id, created_at);
CREATE INDEX trip_searches_session_hash_index       ON trip_searches (session_hash);
CREATE INDEX trip_searches_origin_id_index          ON trip_searches (origin_id);
CREATE INDEX trip_searches_destination_id_index     ON trip_searches (destination_id);

-- Uma opção calculada por critério para cada busca.
CREATE TABLE itineraries (
    id                    BIGSERIAL   PRIMARY KEY,
    trip_search_id        BIGINT      NOT NULL,
    criterion             VARCHAR(25) NOT NULL,
    total_minutes         INTEGER     NOT NULL,
    total_cost_cents      INTEGER     NOT NULL,
    total_distance_meters INTEGER     NOT NULL,
    transfers             SMALLINT    NOT NULL,

    CONSTRAINT itineraries_trip_search_id_criterion_unique UNIQUE (trip_search_id, criterion),
    CONSTRAINT itineraries_trip_search_id_foreign
        FOREIGN KEY (trip_search_id) REFERENCES trip_searches (id) ON DELETE CASCADE,
    CONSTRAINT itineraries_criterion
        CHECK (criterion IN ('fastest', 'cheapest', 'transfers', 'sustainable')),
    CONSTRAINT itineraries_valid
        CHECK (total_minutes > 0 AND total_distance_meters > 0 AND total_cost_cents >= 0 AND transfers >= 0)
);

-- Etapas de um itinerário. `minutes` já inclui a espera e o tempo de integração.
CREATE TABLE itinerary_steps (
    id               BIGSERIAL PRIMARY KEY,
    itinerary_id     BIGINT    NOT NULL,
    connection_id    BIGINT    NOT NULL,
    sequence         SMALLINT  NOT NULL,
    minutes          SMALLINT  NOT NULL,
    cost_cents       INTEGER   NOT NULL,
    distance_meters  INTEGER   NOT NULL,
    wait_minutes     SMALLINT  NOT NULL DEFAULT '0',
    transfer_minutes SMALLINT  NOT NULL DEFAULT '0',
    fare_integrated  BOOLEAN   NOT NULL DEFAULT false,

    CONSTRAINT itinerary_steps_itinerary_id_sequence_unique UNIQUE (itinerary_id, sequence),
    CONSTRAINT itinerary_steps_itinerary_id_foreign
        FOREIGN KEY (itinerary_id) REFERENCES itineraries (id) ON DELETE CASCADE,
    CONSTRAINT itinerary_steps_connection_id_foreign
        FOREIGN KEY (connection_id) REFERENCES connections (id) ON DELETE RESTRICT,
    CONSTRAINT itinerary_steps_valid
        CHECK (sequence > 0 AND minutes > 0 AND distance_meters > 0 AND cost_cents >= 0
               AND wait_minutes >= 0 AND transfer_minutes >= 0
               AND wait_minutes + transfer_minutes <= minutes)
);

CREATE INDEX itinerary_steps_connection_id_index ON itinerary_steps (connection_id);

-- -----------------------------------------------------------------------------
-- 4. Funções e gatilhos
-- -----------------------------------------------------------------------------

-- connections.path: segmento entre as paradas de origem e destino.
CREATE FUNCTION elomob_sync_connection_path() RETURNS trigger AS $$
BEGIN
    SELECT ST_MakeLine(origin.location::geometry, destination.location::geometry)
    INTO NEW.path
    FROM stops origin, stops destination
    WHERE origin.id = NEW.from_stop_id AND destination.id = NEW.to_stop_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE FUNCTION elomob_sync_stop_connections() RETURNS trigger AS $$
BEGIN
    UPDATE connections
    SET path = ST_MakeLine(origin.location::geometry, destination.location::geometry)
    FROM stops origin, stops destination
    WHERE origin.id = connections.from_stop_id
      AND destination.id = connections.to_stop_id
      AND (connections.from_stop_id = NEW.id OR connections.to_stop_id = NEW.id);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER connections_sync_path
    BEFORE INSERT OR UPDATE OF from_stop_id, to_stop_id ON connections
    FOR EACH ROW EXECUTE FUNCTION elomob_sync_connection_path();

CREATE TRIGGER stops_sync_connections
    AFTER UPDATE OF latitude, longitude ON stops
    FOR EACH ROW EXECUTE FUNCTION elomob_sync_stop_connections();

-- network_versions: nova versão após qualquer mudança em uma tabela da rede.
CREATE FUNCTION elomob_renew_network_version() RETURNS trigger AS $$
BEGIN
    UPDATE network_versions SET version = gen_random_uuid()::text;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER transport_modes_renew_network_version
    AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON transport_modes
    FOR EACH STATEMENT EXECUTE FUNCTION elomob_renew_network_version();

CREATE TRIGGER stops_renew_network_version
    AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON stops
    FOR EACH STATEMENT EXECUTE FUNCTION elomob_renew_network_version();

CREATE TRIGGER lines_renew_network_version
    AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON lines
    FOR EACH STATEMENT EXECUTE FUNCTION elomob_renew_network_version();

CREATE TRIGGER connections_renew_network_version
    AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON connections
    FOR EACH STATEMENT EXECUTE FUNCTION elomob_renew_network_version();

CREATE TRIGGER integrations_renew_network_version
    AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON integrations
    FOR EACH STATEMENT EXECUTE FUNCTION elomob_renew_network_version();

-- -----------------------------------------------------------------------------
-- 5. Infraestrutura do Laravel
-- -----------------------------------------------------------------------------

CREATE TABLE password_reset_tokens (
    email      VARCHAR(255) PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0)
);

CREATE TABLE sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT,
    ip_address    VARCHAR(45),
    user_agent    TEXT,
    payload       TEXT         NOT NULL,
    last_activity INTEGER      NOT NULL
);

CREATE INDEX sessions_user_id_index       ON sessions (user_id);
CREATE INDEX sessions_last_activity_index ON sessions (last_activity);

CREATE TABLE cache (
    key        VARCHAR(255) PRIMARY KEY,
    value      TEXT         NOT NULL,
    expiration BIGINT       NOT NULL
);

CREATE INDEX cache_expiration_index ON cache (expiration);

CREATE TABLE cache_locks (
    key        VARCHAR(255) PRIMARY KEY,
    owner      VARCHAR(255) NOT NULL,
    expiration BIGINT       NOT NULL
);

CREATE INDEX cache_locks_expiration_index ON cache_locks (expiration);

CREATE TABLE jobs (
    id           BIGSERIAL    PRIMARY KEY,
    queue        VARCHAR(255) NOT NULL,
    payload      TEXT         NOT NULL,
    attempts     SMALLINT     NOT NULL,
    reserved_at  INTEGER,
    available_at INTEGER      NOT NULL,
    created_at   INTEGER      NOT NULL
);

CREATE INDEX jobs_queue_index ON jobs (queue);

CREATE TABLE job_batches (
    id             VARCHAR(255) PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    total_jobs     INTEGER      NOT NULL,
    pending_jobs   INTEGER      NOT NULL,
    failed_jobs    INTEGER      NOT NULL,
    failed_job_ids TEXT         NOT NULL,
    options        TEXT,
    cancelled_at   INTEGER,
    created_at     INTEGER      NOT NULL,
    finished_at    INTEGER
);

CREATE TABLE failed_jobs (
    id         BIGSERIAL    PRIMARY KEY,
    uuid       VARCHAR(255) NOT NULL,
    connection VARCHAR(255) NOT NULL,
    queue      VARCHAR(255) NOT NULL,
    payload    TEXT         NOT NULL,
    exception  TEXT         NOT NULL,
    failed_at  TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid)
);

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON failed_jobs (connection, queue, failed_at);

-- Migrations já representadas acima, para `php artisan migrate` não repeti-las.
CREATE TABLE migrations (
    id        SERIAL       PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch     INTEGER      NOT NULL
);

INSERT INTO migrations (migration, batch) VALUES
    ('0001_01_01_000000_create_users_table', 1),
    ('0001_01_01_000001_create_cache_table', 1),
    ('0001_01_01_000002_create_jobs_table', 1),
    ('2026_10_30_174536_create_mobility_tables', 1),
    ('2026_10_31_012655_add_spatial_network_and_wait_times', 1),
    ('2026_10_31_205505_add_integrity_checks_and_foreign_key_indexes', 1),
    ('2026_10_31_212051_make_lines_the_source_of_connection_fares_and_modes', 1),
    ('2026_10_31_222451_add_transfer_minutes_to_itinerary_steps', 1),
    ('2026_10_31_225510_allow_several_lines_on_the_same_stretch', 1),
    ('2026_10_31_230854_move_search_departure_out_of_filters', 1),
    ('2026_11_02_004520_add_accessibility_needs_and_features', 1),
    ('2026_11_02_132510_add_fare_integration_to_integrations_and_steps', 1),
    ('2026_11_02_141210_create_network_versions_table', 1);

COMMIT;