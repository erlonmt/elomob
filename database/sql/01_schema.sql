-- ELOMOB: estrutura PostgreSQL gerada das migrations. Aplicar em banco vazio.
BEGIN;
CREATE EXTENSION IF NOT EXISTS postgis;
create table "migrations" ("id" serial not null primary key, "migration" varchar(255) not null, "batch" integer not null);
create table "users" ("id" bigserial not null primary key, "name" varchar(255) not null, "email" varchar(255) not null, "email_verified_at" timestamp(0) without time zone null, "password" varchar(255) not null, "remember_token" varchar(100) null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "users" add constraint "users_email_unique" unique ("email");
create table "password_reset_tokens" ("email" varchar(255) not null, "token" varchar(255) not null, "created_at" timestamp(0) without time zone null);
alter table "password_reset_tokens" add primary key ("email");
create table "sessions" ("id" varchar(255) not null, "user_id" bigint null, "ip_address" varchar(45) null, "user_agent" text null, "payload" text not null, "last_activity" integer not null);
alter table "sessions" add primary key ("id");
create index "sessions_user_id_index" on "sessions" ("user_id");
create index "sessions_last_activity_index" on "sessions" ("last_activity");
create table "cache" ("key" varchar(255) not null, "value" text not null, "expiration" bigint not null);
alter table "cache" add primary key ("key");
create index "cache_expiration_index" on "cache" ("expiration");
create table "cache_locks" ("key" varchar(255) not null, "owner" varchar(255) not null, "expiration" bigint not null);
alter table "cache_locks" add primary key ("key");
create index "cache_locks_expiration_index" on "cache_locks" ("expiration");
create table "jobs" ("id" bigserial not null primary key, "queue" varchar(255) not null, "payload" text not null, "attempts" smallint not null, "reserved_at" integer null, "available_at" integer not null, "created_at" integer not null);
create index "jobs_queue_index" on "jobs" ("queue");
create table "job_batches" ("id" varchar(255) not null, "name" varchar(255) not null, "total_jobs" integer not null, "pending_jobs" integer not null, "failed_jobs" integer not null, "failed_job_ids" text not null, "options" text null, "cancelled_at" integer null, "created_at" integer not null, "finished_at" integer null);
alter table "job_batches" add primary key ("id");
create table "failed_jobs" ("id" bigserial not null primary key, "uuid" varchar(255) not null, "connection" varchar(255) not null, "queue" varchar(255) not null, "payload" text not null, "exception" text not null, "failed_at" timestamp(0) without time zone not null default CURRENT_TIMESTAMP);
create index "failed_jobs_connection_queue_failed_at_index" on "failed_jobs" ("connection", "queue", "failed_at");
alter table "failed_jobs" add constraint "failed_jobs_uuid_unique" unique ("uuid");
alter table "users" add column "preferred_route" varchar(20) not null default 'fastest';
alter table "users" add column "accessible_only" boolean not null default '0';
alter table "users" add column "avoid_stairs" boolean not null default '0';
alter table "users" add column "adapted_only" boolean not null default '0';
alter table "users" add column "transport_modes" json null;
create table "transport_modes" ("id" bigserial not null primary key, "code" varchar(20) not null, "name" varchar(40) not null, "color" varchar(7) not null);
alter table "transport_modes" add constraint "transport_modes_code_unique" unique ("code");
create table "stops" ("id" bigserial not null primary key, "name" varchar(120) not null, "address" varchar(200) not null, "latitude" decimal(10, 7) not null, "longitude" decimal(10, 7) not null, "transport_mode_id" bigint not null, "accessible" boolean not null default '1', "bikes_available" smallint null, "docks_available" smallint null);
alter table "stops" add constraint "stops_transport_mode_id_foreign" foreign key ("transport_mode_id") references "transport_modes" ("id") on delete restrict;
alter table "stops" add constraint "stops_name_unique" unique ("name");
create table "lines" ("id" bigserial not null primary key, "transport_mode_id" bigint not null, "code" varchar(20) not null, "name" varchar(150) not null, "starts_at" time(0) without time zone not null, "ends_at" time(0) without time zone not null, "frequency_minutes" smallint not null, "fare_cents" integer not null);
alter table "lines" add constraint "lines_transport_mode_id_foreign" foreign key ("transport_mode_id") references "transport_modes" ("id") on delete restrict;
alter table "lines" add constraint "lines_code_unique" unique ("code");
create table "connections" ("id" bigserial not null primary key, "from_stop_id" bigint not null, "to_stop_id" bigint not null, "transport_mode_id" bigint not null, "line_id" bigint null, "minutes" smallint not null, "distance_meters" integer not null, "boarding_fare_cents" integer not null default '0', "accessible" boolean not null default '1', "has_stairs" boolean not null default '0', "adapted" boolean not null default '1');
alter table "connections" add constraint "connections_from_stop_id_foreign" foreign key ("from_stop_id") references "stops" ("id") on delete restrict;
alter table "connections" add constraint "connections_to_stop_id_foreign" foreign key ("to_stop_id") references "stops" ("id") on delete restrict;
alter table "connections" add constraint "connections_transport_mode_id_foreign" foreign key ("transport_mode_id") references "transport_modes" ("id") on delete restrict;
alter table "connections" add constraint "connections_line_id_foreign" foreign key ("line_id") references "lines" ("id") on delete restrict;
create index "connections_from_stop_id_transport_mode_id_index" on "connections" ("from_stop_id", "transport_mode_id");
alter table "connections" add constraint "connections_from_stop_id_to_stop_id_transport_mode_id_unique" unique ("from_stop_id", "to_stop_id", "transport_mode_id");
create table "integrations" ("id" bigserial not null primary key, "stop_id" bigint not null, "from_mode_id" bigint not null, "to_mode_id" bigint not null, "transfer_minutes" smallint not null, "description" varchar(200) not null, "accessible" boolean not null default '1');
alter table "integrations" add constraint "integrations_stop_id_foreign" foreign key ("stop_id") references "stops" ("id") on delete restrict;
alter table "integrations" add constraint "integrations_from_mode_id_foreign" foreign key ("from_mode_id") references "transport_modes" ("id") on delete restrict;
alter table "integrations" add constraint "integrations_to_mode_id_foreign" foreign key ("to_mode_id") references "transport_modes" ("id") on delete restrict;
alter table "integrations" add constraint "integrations_stop_id_from_mode_id_to_mode_id_unique" unique ("stop_id", "from_mode_id", "to_mode_id");
create table "saved_places" ("id" bigserial not null primary key, "user_id" bigint not null, "stop_id" bigint not null, "label" varchar(60) not null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "saved_places" add constraint "saved_places_user_id_foreign" foreign key ("user_id") references "users" ("id") on delete cascade;
alter table "saved_places" add constraint "saved_places_stop_id_foreign" foreign key ("stop_id") references "stops" ("id") on delete restrict;
alter table "saved_places" add constraint "saved_places_user_id_label_unique" unique ("user_id", "label");
create table "trip_searches" ("id" bigserial not null primary key, "user_id" bigint null, "session_hash" varchar(64) not null, "origin_id" bigint not null, "destination_id" bigint not null, "filters" json not null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "trip_searches" add constraint "trip_searches_user_id_foreign" foreign key ("user_id") references "users" ("id") on delete set null;
alter table "trip_searches" add constraint "trip_searches_origin_id_foreign" foreign key ("origin_id") references "stops" ("id") on delete restrict;
alter table "trip_searches" add constraint "trip_searches_destination_id_foreign" foreign key ("destination_id") references "stops" ("id") on delete restrict;
create index "trip_searches_user_id_created_at_index" on "trip_searches" ("user_id", "created_at");
create index "trip_searches_session_hash_index" on "trip_searches" ("session_hash");
create table "itineraries" ("id" bigserial not null primary key, "trip_search_id" bigint not null, "criterion" varchar(25) not null, "total_minutes" integer not null, "total_cost_cents" integer not null, "total_distance_meters" integer not null, "transfers" smallint not null);
alter table "itineraries" add constraint "itineraries_trip_search_id_foreign" foreign key ("trip_search_id") references "trip_searches" ("id") on delete cascade;
alter table "itineraries" add constraint "itineraries_trip_search_id_criterion_unique" unique ("trip_search_id", "criterion");
create table "itinerary_steps" ("id" bigserial not null primary key, "itinerary_id" bigint not null, "connection_id" bigint not null, "sequence" smallint not null, "minutes" smallint not null, "cost_cents" integer not null, "distance_meters" integer not null);
alter table "itinerary_steps" add constraint "itinerary_steps_itinerary_id_foreign" foreign key ("itinerary_id") references "itineraries" ("id") on delete cascade;
alter table "itinerary_steps" add constraint "itinerary_steps_connection_id_foreign" foreign key ("connection_id") references "connections" ("id") on delete restrict;
alter table "itinerary_steps" add constraint "itinerary_steps_itinerary_id_sequence_unique" unique ("itinerary_id", "sequence");
ALTER TABLE connections ADD CONSTRAINT connections_valid CHECK (from_stop_id <> to_stop_id AND minutes > 0 AND distance_meters > 0 AND boarding_fare_cents >= 0);
ALTER TABLE stops ADD CONSTRAINT stops_coordinates CHECK (latitude BETWEEN -90 AND 90 AND longitude BETWEEN -180 AND 180);
ALTER TABLE trip_searches ADD CONSTRAINT searches_distinct_places CHECK (origin_id <> destination_id);
ALTER TABLE itinerary_steps ADD COLUMN wait_minutes smallint NOT NULL DEFAULT 0;
ALTER TABLE stops ADD COLUMN location geography(Point, 4326) GENERATED ALWAYS AS (ST_SetSRID(ST_MakePoint(longitude::double precision, latitude::double precision), 4326)::geography) STORED;
CREATE INDEX stops_location_gist ON stops USING GIST (location);
ALTER TABLE connections ADD COLUMN path geometry(LineString, 4326);
CREATE INDEX connections_path_gist ON connections USING GIST (path);
CREATE OR REPLACE FUNCTION elomob_sync_connection_path() RETURNS trigger AS $$
BEGIN
    SELECT ST_MakeLine(origin.location::geometry, destination.location::geometry)
    INTO NEW.path
    FROM stops origin, stops destination
    WHERE origin.id = NEW.from_stop_id AND destination.id = NEW.to_stop_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER connections_sync_path
BEFORE INSERT OR UPDATE OF from_stop_id, to_stop_id ON connections
FOR EACH ROW EXECUTE FUNCTION elomob_sync_connection_path();
CREATE OR REPLACE FUNCTION elomob_sync_stop_connections() RETURNS trigger AS $$
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
CREATE TRIGGER stops_sync_connections
AFTER UPDATE OF latitude, longitude ON stops
FOR EACH ROW EXECUTE FUNCTION elomob_sync_stop_connections();
create index "stops_transport_mode_id_index" on "stops" ("transport_mode_id");
create index "lines_transport_mode_id_index" on "lines" ("transport_mode_id");
create index "connections_to_stop_id_index" on "connections" ("to_stop_id");
create index "connections_transport_mode_id_index" on "connections" ("transport_mode_id");
create index "connections_line_id_index" on "connections" ("line_id");
create index "integrations_from_mode_id_index" on "integrations" ("from_mode_id");
create index "integrations_to_mode_id_index" on "integrations" ("to_mode_id");
create index "saved_places_stop_id_index" on "saved_places" ("stop_id");
create index "trip_searches_origin_id_index" on "trip_searches" ("origin_id");
create index "trip_searches_destination_id_index" on "trip_searches" ("destination_id");
create index "itinerary_steps_connection_id_index" on "itinerary_steps" ("connection_id");
ALTER TABLE lines ADD CONSTRAINT lines_valid CHECK (frequency_minutes > 0 AND fare_cents >= 0);
ALTER TABLE stops ADD CONSTRAINT stops_bike_counts CHECK (bikes_available >= 0 AND docks_available >= 0);
ALTER TABLE integrations ADD CONSTRAINT integrations_valid CHECK (transfer_minutes >= 0);
ALTER TABLE itineraries ADD CONSTRAINT itineraries_valid CHECK (total_minutes > 0 AND total_distance_meters > 0 AND total_cost_cents >= 0 AND transfers >= 0);
ALTER TABLE itineraries ADD CONSTRAINT itineraries_criterion CHECK (criterion IN ('fastest', 'cheapest', 'transfers', 'sustainable'));
ALTER TABLE itinerary_steps ADD CONSTRAINT itinerary_steps_valid CHECK (sequence > 0 AND minutes > 0 AND distance_meters > 0 AND cost_cents >= 0 AND wait_minutes BETWEEN 0 AND minutes);
ALTER TABLE users ADD CONSTRAINT users_preferred_route CHECK (preferred_route IN ('fastest', 'cheapest', 'transfers', 'sustainable'));
INSERT INTO migrations (migration,batch) VALUES ('0001_01_01_000000_create_users_table',1);
INSERT INTO migrations (migration,batch) VALUES ('0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations (migration,batch) VALUES ('0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_10_30_174536_create_mobility_tables',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_10_31_012655_add_spatial_network_and_wait_times',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_10_31_205505_add_integrity_checks_and_foreign_key_indexes',1);
COMMIT;
