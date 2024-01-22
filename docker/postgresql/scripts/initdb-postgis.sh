#!/bin/bash

set -e

# Activate the PostGIS extension for the specified database
POSTGRES_DB=${POSTGRES_DB:-"pla"}
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS postgis;
    CREATE EXTENSION IF NOT EXISTS postgis_topology;
    CREATE EXTENSION IF NOT EXISTS pg_trgm;
EOSQL
