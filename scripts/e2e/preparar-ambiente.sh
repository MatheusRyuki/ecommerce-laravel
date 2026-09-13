#!/usr/bin/env bash
set -euo pipefail

raiz="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$raiz"

if [[ ! -f .env.e2e ]]; then
  cp .env.e2e.example .env.e2e
fi

if ! grep -q '^APP_KEY=base64:' .env.e2e; then
  ./vendor/bin/sail artisan key:generate --env=e2e --force
fi

if ! grep -q '^E2E_TOKEN=.\+' .env.e2e || grep -q '^E2E_TOKEN=$' .env.e2e; then
  token="$(openssl rand -hex 24)"
  if grep -q '^E2E_TOKEN=' .env.e2e; then
    sed -i "s/^E2E_TOKEN=.*/E2E_TOKEN=${token}/" .env.e2e
  else
    echo "E2E_TOKEN=${token}" >> .env.e2e
  fi
fi

echo "Confirmando MySQL do Compose ecommerce..."
db_atual="$(docker exec ecommerce-mysql-1 mysql -usail -ppassword -N -e 'SELECT DATABASE();' ecommerce)"
if [[ "$db_atual" != "ecommerce" ]]; then
  echo "Abortado: esperado conferir o banco ecommerce, obtido: ${db_atual}"
  exit 1
fi

echo "Criando banco e usuário restritos a ecommerce_e2e..."
docker exec ecommerce-mysql-1 mysql -uroot -ppassword -e "
CREATE DATABASE IF NOT EXISTS ecommerce_e2e CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ecommerce_e2e'@'%' IDENTIFIED BY 'e2e-local-apenas';
GRANT ALL PRIVILEGES ON ecommerce_e2e.* TO 'ecommerce_e2e'@'%';
FLUSH PRIVILEGES;
"

echo "Recriando schema E2E com as cinco migrations consolidadas..."
./vendor/bin/sail artisan e2e:reiniciar --env=e2e --no-interaction
