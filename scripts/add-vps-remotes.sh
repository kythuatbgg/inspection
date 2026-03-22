#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — Add VPS remote
# Run once on local:
#   bash scripts/add-vps-remotes.sh
# =============================================================================

set -euo pipefail

VPS_HOST="root@180.93.42.138"
APP_DIR="/opt/fbb-inspection"
SSH_KEY="${USERPROFILE:-C:/Users/Admin}/.ssh/fbb_vps"

echo "Adding VPS remote..."
git remote add vps "ssh://${VPS_HOST}${APP_DIR}/repo.git" 2>/dev/null && echo "  OK vps added" || echo "  OK already exists"

echo ""
echo "Remotes:"
git remote -v

echo ""
echo "Deploy: git push vps main"
