#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
mysql -u root -p < backend/mysql/schema.sql
echo "Base de dados importada com sucesso."
