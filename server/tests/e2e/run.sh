#!/usr/bin/env bash
# Browser-Durchlauf der geführten Einheit S9 (AP-14 T5, tests/e2e/gefuehrt.e2e.cjs) gegen eine lokal gestartete App.
# Nutzt die Testdatenbank aus TEST_DB_* (alle Tabellen werden gelöscht) und ein eigenes Basisverzeichnis mit eigener
# .env (eine vorhandene server/.env bleibt unberührt). Aufruf in server/: bash tests/e2e/run.sh
# Braucht PHP, Node und Playwright (lokal, global oder im Ordner installiert); CHROME_PATH wählt einen anderen Browser.
# Port: E2E_PORT, sonst ein freier Port (eine schon laufende App auf dem Port würde sonst mitgetestet).
set -euo pipefail

SERVER="$(cd "$(dirname "$0")/../.." && pwd)"
PORT="${E2E_PORT:-$(php -r '$s = stream_socket_server("tcp://127.0.0.1:0"); echo explode(":", stream_socket_get_name($s, false))[1];')}"
export TEST_DB_HOST="${TEST_DB_HOST:-127.0.0.1}" # wie die .env der App (ohne Host nähme PDO den lokalen Socket)
SECRET="e2e-migration-secret-e2e-migration-secret"
TOKEN="e2e-statisches-token-e2e-statisches-token"
BASE="$(mktemp -d)"
PID=""
cleanup() {
  if [ -n "$PID" ]; then kill "$PID" 2>/dev/null || true; fi
  rm -rf "$BASE"
}
trap cleanup EXIT

# Basisverzeichnis: public/ und bin/ als Kopie (index.php bestimmt das Basisverzeichnis über seinen echten Pfad),
# der Rest als Verweis
cp -r "$SERVER/public" "$SERVER/bin" "$BASE/"
for d in vendor src templates migrations schemas; do ln -s "$SERVER/$d" "$BASE/$d"; done
mkdir -p "$BASE/var" "$BASE/backups"
cat > "$BASE/.env" <<EOF
APP_ENV=test
APP_URL=http://127.0.0.1:$PORT
DB_HOST=$TEST_DB_HOST
DB_PORT=${TEST_DB_PORT:-3306}
DB_NAME=${TEST_DB_NAME:?TEST_DB_NAME fehlt}
DB_USER=${TEST_DB_USER:?TEST_DB_USER fehlt}
DB_PASSWORD=${TEST_DB_PASSWORD:-}
MIGRATION_SECRET=$SECRET
OAUTH_JWT_SECRET=e2e-jwt-secret-e2e-jwt-secret-e2e-jwt
BACKUP_PASSWORD=e2e-backup-passwort-e2e
MCP_STATIC_TOKEN=$TOKEN
MCP_STATIC_TOKEN_ENABLED=true
EOF

# Testdatenbank leeren
php -r '
$pdo = new PDO("mysql:host=" . getenv("TEST_DB_HOST") . ";port=" . (getenv("TEST_DB_PORT") ?: "3306") . ";dbname=" . getenv("TEST_DB_NAME"), getenv("TEST_DB_USER"), getenv("TEST_DB_PASSWORD") ?: "");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) { $pdo->exec("DROP TABLE `" . str_replace("`", "``", $t) . "`"); }
'

php -S "127.0.0.1:$PORT" -t "$BASE/public" "$BASE/bin/dev-router.php" > "$BASE/server.log" 2>&1 &
PID=$!
BEREIT=""
for _ in $(seq 1 50); do
  # eigener Server beendet (z. B. Port belegt)? Dann nicht gegen einen fremden Server testen
  kill -0 "$PID" 2>/dev/null || { echo "Server auf Port $PORT nicht gestartet:"; cat "$BASE/server.log"; exit 1; }
  if curl -s -o /dev/null "http://127.0.0.1:$PORT/health"; then BEREIT=1; break; fi
  sleep 0.2
done
[ -n "$BEREIT" ] || { echo "Server auf Port $PORT antwortet nicht:"; cat "$BASE/server.log"; exit 1; }
curl -fsS -X POST -H "X-Migration-Secret: $SECRET" "http://127.0.0.1:$PORT/admin/migrate" > /dev/null

E2E_BASE_URL="http://127.0.0.1:$PORT" E2E_MCP_TOKEN="$TOKEN" E2E_MIGRATION_SECRET="$SECRET" \
  node "$SERVER/tests/e2e/gefuehrt.e2e.cjs" || { echo "--- Server-Protokoll ---"; tail -n 50 "$BASE/server.log"; exit 1; }
