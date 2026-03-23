# ── Stage 1: Build ──────────────────────────────────────────────────────────
FROM node:22-alpine AS build

WORKDIR /app

# Copy package files
COPY frontend/package*.json ./

RUN npm ci --frozen-lockfile

# Copy source and build
COPY frontend/ ./
RUN node node_modules/vite/bin/vite.js build

# ── Stage 2: Serve ──────────────────────────────────────────────────────────
FROM nginx:alpine

# Remove default site
RUN rm -rf /usr/share/nginx/html/*

# Copy built assets
COPY --from=build /app/dist /usr/share/nginx/html

# Copy custom Nginx config
COPY docker/frontend/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
