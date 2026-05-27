#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

if [ ! -f .env ]; then
  cp .env.example .env
fi

echo "Subindo a stack com Docker Compose..."
docker compose up --build
