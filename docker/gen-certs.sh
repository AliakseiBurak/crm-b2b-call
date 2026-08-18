#!/bin/bash
set -euo pipefail

CERTS_DIR="${CERTS_DIR:-/certs}"
CA_CN="${CA_CN:-B2B CRM Dev CA}"
SERVER_CN="${SERVER_CN:-b2b-crm.local}"
SAN="${SAN:-DNS:b2b-crm.local,DNS:localhost,IP:127.0.0.1,DNS:host.docker.internal}"
DAYS=3650

mkdir -p "${CERTS_DIR}"
cd "${CERTS_DIR}"

if [ -f server.crt ] && [ -f server.key ] && [ -f ca.crt ]; then
    echo "[gen-certs] Сертификаты уже существуют, пропускаю генерацию."
    exit 0
fi

echo "[gen-certs] Генерация CA (${CA_CN})..."
openssl genrsa -out ca.key 4096 2>/dev/null
openssl req -x509 -new -nodes -key ca.key -sha256 -days "${DAYS}" \
    -subj "/CN=${CA_CN}/O=B2B CRM Dev" -out ca.crt

echo "[gen-certs] Генерация серверного ключа и CSR..."
openssl genrsa -out server.key 2048 2>/dev/null
openssl req -new -key server.key \
    -subj "/CN=${SERVER_CN}/O=B2B CRM Dev" -out server.csr

cat > server.ext <<EOF
basicConstraints = CA:FALSE
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = ${SAN}
EOF

echo "[gen-certs] Подпись серверного сертификата CA..."
openssl x509 -req -in server.csr -CA ca.crt -CAkey ca.key -CAcreateserial \
    -out server.crt -days "${DAYS}" -sha256 -extfile server.ext

rm -f server.csr server.ext ca.srl
chmod 600 ca.key server.key

echo "[gen-certs] Готово: ${CERTS_DIR}/{ca.crt, ca.key, server.crt, server.key}"
