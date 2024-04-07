# Add needed arguments
ARG IMAGE_ADD

FROM ${IMAGE_ADD}postgres:14.11-bullseye

LABEL maintainer="PostGIS Project - https://postgis.net"

ENV POSTGIS_MAJOR 3
ENV POSTGIS_VERSION 3.4.2+dfsg-1.pgdg110+1

RUN apt-get update \
      && apt-cache showpkg postgresql-$PG_MAJOR-postgis-$POSTGIS_MAJOR \
      && apt-get install -y --no-install-recommends \
           postgresql-$PG_MAJOR-postgis-$POSTGIS_MAJOR=$POSTGIS_VERSION \
           postgresql-$PG_MAJOR-postgis-$POSTGIS_MAJOR-scripts \
      && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /docker-entrypoint-initdb.d

# Copy custom scripts
COPY ./scripts/initdb-postgis.sh /docker-entrypoint-initdb.d/10_postgis.sh
COPY ./scripts/increase-work-mem.sh /docker-entrypoint-initdb.d/20_increase.sh
COPY ./scripts/update-postgis.sh /usr/local/bin
