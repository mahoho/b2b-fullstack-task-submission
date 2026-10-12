import { describe, expect, it } from 'vitest'
import { isAllowedApiRequest } from '~/server/utils/apiAllowlist'

const CAMPAIGN = '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81'
const APPLICANT = '11111111-1111-4111-8111-111111111111'

describe('isAllowedApiRequest', () => {
  it('allows the summary GET, with or without a query string', () => {
    expect(isAllowedApiRequest('GET', `/api/v1/campaigns/${CAMPAIGN}/applicant-summary`)).toBe(true)
    expect(isAllowedApiRequest('GET', `/api/v1/campaigns/${CAMPAIGN}/applicant-summary?min_score=80&page%5Bnumber%5D=2`)).toBe(true)
  })

  it('allows the status PATCH', () => {
    expect(isAllowedApiRequest('PATCH', `/api/v1/applicants/${APPLICANT}`)).toBe(true)
  })

  it('rejects other methods on the allowed paths', () => {
    expect(isAllowedApiRequest('DELETE', `/api/v1/applicants/${APPLICANT}`)).toBe(false)
    expect(isAllowedApiRequest('POST', `/api/v1/campaigns/${CAMPAIGN}/applicant-summary`)).toBe(false)
    expect(isAllowedApiRequest('PATCH', `/api/v1/campaigns/${CAMPAIGN}/applicant-summary`)).toBe(false)
  })

  it('rejects any other path, including traversal attempts', () => {
    expect(isAllowedApiRequest('GET', '/api/v1/campaigns')).toBe(false)
    expect(isAllowedApiRequest('GET', `/api/v1/applicants/${APPLICANT}`)).toBe(false)
    expect(isAllowedApiRequest('PATCH', `/api/v1/applicants/${APPLICANT}/notes`)).toBe(false)
    expect(isAllowedApiRequest('PATCH', '/api/v1/applicants/../partners')).toBe(false)
    expect(isAllowedApiRequest('GET', `/api/v1/campaigns/${CAMPAIGN}/applicant-summary/%2e%2e/secrets`)).toBe(false)
  })
})
