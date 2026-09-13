#!/usr/bin/env bash
set -euo pipefail

raiz="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$raiz"

mkdir -p storage/e2e
lock="$raiz/storage/e2e/execucao.lock"

exec 9>"$lock"
if ! flock -n 9; then
  cat >&2 <<'EOF'
Já existe uma execução E2E deste repositório (teste ou preparação).
A segunda execução foi recusada para não reiniciar o banco ecommerce_e2e nem sobrescrever o relatório.
Aguarde a execução atual terminar.
EOF
  exit 1
fi

if [[ $# -eq 0 ]]; then
  echo "Uso: scripts/e2e/com-exclusividade.sh <comando> [args...]" >&2
  exit 1
fi

exec "$@"
