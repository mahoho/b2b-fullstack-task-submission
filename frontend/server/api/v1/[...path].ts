// Forwards the portal's API calls to Laravel and adds the partner token here, so it never reaches the browser.
export default defineEventHandler((event) => {
  const { apiBase, apiToken } = useRuntimeConfig(event)

  return proxyRequest(event, apiBase + event.path, {
    headers: { Authorization: `Bearer ${apiToken}` },
  })
})
