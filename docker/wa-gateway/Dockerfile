# Dockerfile WA Gateway — whatsapp-web.js (Puppeteer/Chromium).
# Dipakai service `wa-gateway` di docker-compose.yml.
# Port 3001 TIDAK dipublish — hanya backend yang akses via
# WHATSAPP_URL=http://wa-gateway:3001/kirim + Bearer GATEWAY_TOKEN.
FROM node:20-slim

# Shared libs untuk headless Chromium yang diunduh Puppeteer saat build.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 \
        libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 \
        libgbm1 libasound2 libpango-1.0-0 libcairo2 \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY package.json package-lock.json* ./
# JANGAN pakai --ignore-scripts: postinstall Puppeteer mengunduh browser headless.
RUN npm ci --omit=dev || npm install --omit=dev

COPY src/ ./src/
COPY ecosystem.config.js ./

# .env dan .wwebjs_auth di-mount dari host (lihat volumes service),
# BUKAN di-copy ke image — agar token & sesi tidak bocor ke image.

CMD ["node", "src/index.js"]
