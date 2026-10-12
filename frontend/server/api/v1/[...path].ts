// Forwards the portal's API calls to Laravel and adds the partner token here, so it never reaches the browser.
// The token is a single demo credential; a real portal would take it from the signed-in user's session.
export default defineEventHandler((event) => {
  if (!isAllowedApiRequest(event.method, event.path)) {
    throw createError({ statusCode: 404, statusMessage: 'Not Found' })
  }

  const { apiBase, apiToken } = useRuntimeConfig(event)

  if (!apiBase || !apiToken) {
    throw createError({ statusCode: 500, statusMessage: 'NUXT_API_BASE and NUXT_API_TOKEN must be set' })
  }

  return proxyRequest(event, apiBase + event.path, {
    headers: { Authorization: `Bearer ${apiToken}` },
  })
})
