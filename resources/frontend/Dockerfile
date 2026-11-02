FROM node:24-bookworm-slim AS dependencies
WORKDIR /app/resources/frontend
COPY resources/frontend/package.json resources/frontend/package-lock.json ./
RUN npm ci --ignore-scripts

FROM node:24-bookworm-slim AS builder
WORKDIR /app/resources/frontend
ENV NEXT_TELEMETRY_DISABLED=1
COPY --from=dependencies /app/resources/frontend/node_modules ./node_modules
COPY resources/frontend/ ./
COPY public/assets/ /app/public/assets/
RUN npm run build

FROM node:24-bookworm-slim AS runtime
WORKDIR /app
ENV NODE_ENV=production NEXT_TELEMETRY_DISABLED=1 HOSTNAME=0.0.0.0 PORT=3000
COPY --from=builder --chown=node:node /app/resources/frontend/.next/standalone ./
COPY --from=builder --chown=node:node /app/resources/frontend/.next/static ./.next/static
COPY --from=builder --chown=node:node /app/resources/frontend/public ./public
USER node
EXPOSE 3000
CMD ["node", "server.js"]
