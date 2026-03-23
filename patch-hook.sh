#!/bin/bash
sed -i 's|DB_PASSWORD=.*|DB_PASSWORD=${POSTGRES_PASSWORD}|' /opt/fbb-inspection/repo.git/hooks/post-receive
