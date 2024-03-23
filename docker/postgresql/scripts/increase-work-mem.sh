#!/bin/bash
set -e

# Setze work_mem auf 256MB
sed -i "/^#work_mem = 4MB/c\work_mem = 256MB" /var/lib/postgresql/data/postgresql.conf

# Setze temp_buffers auf 32MB
sed -i "/^#\\s*temp_buffers/c\temp_buffers = 32MB" /var/lib/postgresql/data/postgresql.conf

# Setze shared_buffers auf 1GB
sed -i "s/^\\s*shared_buffers\\s*=.*/shared_buffers = 1GB/" /var/lib/postgresql/data/postgresql.conf
