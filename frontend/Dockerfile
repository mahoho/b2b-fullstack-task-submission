# One install for both the dev server and the production build. postinstall (nuxt prepare) needs the source,
# which is not copied yet; nuxt dev and nuxt build both prepare the app themselves.
FROM node:22.23.1 AS dev

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

# docker-compose.override.yml mounts the source over /app and runs the dev server.
EXPOSE 3000
CMD ["npx", "nuxt", "dev", "--host", "0.0.0.0", "--port", "3000"]

FROM dev AS build
COPY . .
RUN npm run build

# Production runs only Nuxt's self-contained .output, on a slim image.
FROM node:22.23.1-slim

WORKDIR /app
COPY --from=build /app/.output ./.output

ENV HOST=0.0.0.0 PORT=3000 NODE_ENV=production
EXPOSE 3000

# NUXT_API_BASE and NUXT_API_TOKEN are read at runtime, so they come from compose.
CMD ["node", ".output/server/index.mjs"]
