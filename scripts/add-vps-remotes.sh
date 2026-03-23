#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — Add VPS remote
# Run once on local:
#   bash scripts/add-vps-remotes.sh
# =============================================================================

set -euo pipefail

VPS_HOST="${VPS_HOST:-root@180.93.42.138}"
APP_DIR="${APP_DIR:-/opt/fbb-inspection}"
REMOTE_NAME="${REMOTE_NAME:-vps}"
REMOTE_URL="ssh://${VPS_HOST}${APP_DIR}/repo.git"

echo "Configuring VPS remote..."

if git remote get-url "$REMOTE_NAME" >/dev/null 2>&1; then
	git remote set-url "$REMOTE_NAME" "$REMOTE_URL"
	echo "  OK updated $REMOTE_NAME -> $REMOTE_URL"
else
	git remote add "$REMOTE_NAME" "$REMOTE_URL"
	echo "  OK added $REMOTE_NAME -> $REMOTE_URL"
fi

echo ""
echo "Remotes:"
git remote -v

echo ""
echo "Deploy: git push ${REMOTE_NAME} main"
