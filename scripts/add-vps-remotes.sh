#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — Add VPS remote repos
# Run once on local (after setup script has been run on VPS):
#   bash scripts/add-vps-remotes.sh
# =============================================================================

set -euo pipefail

VPS_HOST="root@180.93.42.138"
SSH_KEY="$USERPROFILE/.ssh/fbb_vps"

echo "Adding VPS backend remote..."
git remote add vps-backend "ssh://${VPS_HOST}/opt/fbb-inspection/repo-backend.git" 2>/dev/null && echo "  ✓ vps-backend added" || echo "  ✓ already exists"

echo "Adding VPS frontend remote..."
git remote add vps-frontend "ssh://${VPS_HOST}/opt/fbb-inspection/repo-frontend.git" 2>/dev/null && echo "  ✓ vps-frontend added" || echo "  ✓ already exists"

echo ""
echo "Remotes:"
git remote -v | grep -E "vps-"

echo ""
echo "Deploy commands:"
echo "  git push vps-backend main     # backend + auto migrate"
echo "  git push vps-frontend main   # frontend rebuild"
