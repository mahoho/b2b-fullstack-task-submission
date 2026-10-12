FROM node:22

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

ENV HOST=0.0.0.0 PORT=3000
EXPOSE 3000

# NUXT_API_BASE and NUXT_API_TOKEN are read at runtime, so they come from compose.
CMD ["node", ".output/server/index.mjs"]
