import { expect, test, type Locator } from '@playwright/test'
import { makeApplicant, makeDocument } from '../nuxt/fixtures'

const CAMPAIGN = '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81'

const cursor = (locator: Locator) => locator.evaluate(element => getComputedStyle(element).cursor)

test('interactive elements show a pointer, disabled ones do not', async ({ page }) => {
  const ada = makeApplicant({ name: 'Ada Lovelace', aiScore: 94, tier: 'top', status: 'shortlisted' })
  const charles = makeApplicant({ name: 'Charles Babbage', aiScore: 62, tier: 'below' })

  await page.route('**/api/v1/campaigns/*/applicant-summary**', route => route.fulfill({
    json: makeDocument([ada, charles], { lastPage: 2 }),
  }))

  await page.goto(`/campaigns/${CAMPAIGN}`)

  const shortlisted = page.getByRole('article', { name: 'Ada Lovelace' })
  const pending = page.getByRole('article', { name: 'Charles Babbage' })
  await expect(pending).toBeVisible()

  expect(await cursor(pending.getByRole('button', { name: 'Shortlist' }))).toBe('pointer')
  expect(await cursor(pending.getByRole('button', { name: 'Reject' }))).toBe('pointer')
  expect(await cursor(pending.getByRole('button', { name: 'View details' }))).toBe('pointer')
  expect(await cursor(page.getByRole('button', { name: 'Next' }))).toBe('pointer')
  expect(await cursor(page.getByLabel('Minimum AI score'))).toBe('pointer')

  // Shortlist is disabled on an applicant who is already shortlisted, and Previous on the first page.
  expect(await cursor(shortlisted.getByRole('button', { name: 'Shortlist' }))).toBe('not-allowed')
  expect(await cursor(page.getByRole('button', { name: 'Previous' }))).toBe('not-allowed')
})
