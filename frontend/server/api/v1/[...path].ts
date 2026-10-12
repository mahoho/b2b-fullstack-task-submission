// Forwards the portal's API calls to Laravel and adds the partner token here, so it never reaches the browser.
// The token is a single demo credential; a real portal would take it from the signed-in user's session.
export default defineEventHandler((event) => {
  if (!isAllowedApiRequest(event.method, event.path)) {
    throw createError({ statusCode: 404, statusMessage: 'Not Found' })
  }

  const { apiBase, apiToken } = useRuntimeConfig(event)

  // There are no defaults, so a missing setting fails loudly instead of forwarding a request with an empty token.
  // The setting names go to the server log only; the browser gets a generic message.
  if (!apiBase || !apiToken) {
    console.error('API proxy is not configured: set NUXT_API_BASE and NUXT_API_TOKEN (see frontend/.env.example).')
    throw createError({ statusCode: 500, statusMessage: 'API proxy is not configured' })
  }

  return proxyRequest(event, apiBase + event.path, {
    headers: { Authorization: `Bearer ${apiToken}` },
  })
})
