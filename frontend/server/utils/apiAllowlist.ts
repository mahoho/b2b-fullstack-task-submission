const UUID = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}'

// The proxy attaches the partner token, so it forwards only the two calls the review page makes.
const ALLOWED: Array<[method: string, path: RegExp]> = [
  ['GET', new RegExp(`^/api/v1/campaigns/${UUID}/applicant-summary$`)],
  ['PATCH', new RegExp(`^/api/v1/applicants/${UUID}$`)],
]

export function isAllowedApiRequest(method: string, path: string): boolean {
  const pathname = path.split('?')[0] ?? ''

  return ALLOWED.some(([allowedMethod, pattern]) => allowedMethod === method && pattern.test(pathname))
}
