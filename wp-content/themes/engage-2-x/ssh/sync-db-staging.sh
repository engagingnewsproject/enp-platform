#!/usr/bin/env bash
set -euo pipefail

# === CONFIGURE THESE ===
PROD_SSH="ssh -i ~/.ssh/wpengine_rsa -o IdentitiesOnly=yes cmengage@cmengage.ssh.wpengine.net"
STAGING_SSH="ssh -i ~/.ssh/wpengine_rsa -o IdentitiesOnly=yes cmestaging@cmengage.ssh.wpengine.net"
LIVE_URL="mediaengagement.org"
STAGING_URL="cmestaging.wpengine.com"

echo "🔸 Exporting Production DB and importing into Staging…"
$PROD_SSH "wp db export - --add-drop-table" \
  | $STAGING_SSH "wp db import - && \
              wp search-replace '$LIVE_URL' '$STAGING_URL' --skip-columns=guid && \
              wp cache flush"

echo "✅ Dev database is now in sync!"