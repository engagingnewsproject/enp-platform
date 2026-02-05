#!/usr/bin/env bash
set -euo pipefail

# === CONFIGURE THESE ===
PROD_SSH="ssh -i ~/.ssh/wpengine_rsa -o IdentitiesOnly=yes cmengage@cmengage.ssh.wpengine.net"
DEV_SSH="ssh -i ~/.ssh/wpengine_rsa -o IdentitiesOnly=yes cmedev@cmengage.ssh.wpengine.net"
LIVE_URL="mediaengagement.org"
DEV_URL="cmedev.wpengine.com"

echo "🔸 Exporting Production DB and importing into Development…"
$PROD_SSH "wp db export - --add-drop-table" \
  | $DEV_SSH "wp db import - && \
              wp search-replace '$LIVE_URL' '$DEV_URL' --skip-columns=guid && \
              wp cache flush"

echo "✅ Dev database is now in sync!"
