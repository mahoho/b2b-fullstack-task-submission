# Build with the full Node image, then run only Nuxt's self-contained .output on a slim one.
FROM node:22.23.1 AS build

WORKDIR /app
COPY package.json package-lock.json ./
# postinstall (nuxt prepare) needs the source, which is not copied yet; the build below prepares the app itself.
RUN npm ci --ignore-scripts

COPY . .
RUN npm run build

FROM node:22.23.1-slim

WORKDIR /app
COPY --from=build /app/.output ./.output

ENV HOST=0.0.0.0 PORT=3000 NODE_ENV=production
EXPOSE 3000

# NUXT_API_BASE and NUXT_API_TOKEN are read at runtime, so they come from compose.
CMD ["node", ".output/server/index.mjs"]
