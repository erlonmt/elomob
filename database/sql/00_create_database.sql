-- =============================================================================
-- ELOMOB · Preparação do banco local (executar uma vez, como superusuário)
--
--   psql -U postgres -h 127.0.0.1 -f database/sql/00_create_database.sql
--
-- Cria o usuário e o banco `elomob` e ativa o PostGIS, que só um superusuário
-- pode ativar. A senha `elomob` é apenas para desenvolvimento local.
-- =============================================================================

CREATE ROLE elomob LOGIN PASSWORD 'elomob';
CREATE DATABASE elomob OWNER elomob;

\connect elomob
CREATE EXTENSION IF NOT EXISTS postgis;
