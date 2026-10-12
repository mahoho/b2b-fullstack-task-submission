import { expect, test } from '@playwright/test'
import { makeApplicant, makeDocument } from '../nuxt/fixtures'

const CAMPAIGN = '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81'

test('a partner reviews and shortlists an applicant', async ({ page }) => {
  const ada = makeApplicant({ name: 'Ada Lovelace', aiScore: 94, tier: 'top' })
  const charles = makeApplicant({ name: 'Charles Babbage', aiScore: 62, tier: 'below' })
  const patches: unknown[] = []

  await page.route('**/api/v1/campaigns/*/applicant-summary**', route => route.fulfill({ json: makeDocument([ada, charles]) }))
  await page.route(`**/api/v1/applicants/${ada.id}`, async (route) => {
    patches.push(route.request().postDataJSON())
    await route.fulfill({ json: { data: { ...ada, attributes: { ...ada.attributes, status: 'shortlisted' } } } })
  })

  await page.goto(`/campaigns/${CAMPAIGN}`)

  const card = page.getByRole('article', { name: 'Ada Lovelace' })
  await expect(card).toBeVisible()
  await expect(page.getByRole('article')).toHaveCount(2)

  await card.getByRole('button', { name: 'Shortlist' }).click()

  await expect(card.getByTestId('status')).toHaveText('Shortlisted')
  expect(patches).toEqual([{ data: { type: 'applicants', id: ada.id, attributes: { status: 'shortlisted' } } }])
})
