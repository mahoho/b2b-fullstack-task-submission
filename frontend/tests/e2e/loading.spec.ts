import { expect, test } from '@playwright/test'
import { makeApplicant, makeDocument } from '../nuxt/fixtures'

const CAMPAIGN = '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81'

test('loading the next page shows the loading bar and keeps the list in place', async ({ page }) => {
  const applicants = [makeApplicant({ name: 'Ada Lovelace' }), makeApplicant({ name: 'Charles Babbage' })]
  let release = () => {}
  let requests = 0

  // The first page answers at once; the next one waits until the test releases it.
  await page.route('**/api/v1/campaigns/*/applicant-summary**', async (route) => {
    requests += 1
    if (requests > 1) {
      await new Promise<void>((resolve) => {
        release = resolve
      })
    }
    await route.fulfill({ json: makeDocument(applicants, { lastPage: 2 }) })
  })

  await page.goto(`/campaigns/${CAMPAIGN}`)

  const bar = page.locator('.nuxt-loading-indicator')
  const first = page.getByRole('article').first()
  await expect(first).toBeVisible()
  await expect(bar).toBeHidden()
  const before = await first.boundingBox()

  await page.getByRole('button', { name: 'Next' }).click()

  await expect(bar).toBeVisible()
  await expect(page.getByTestId('skeleton')).toHaveCount(0)
  expect(await first.boundingBox()).toEqual(before)
  // The bar is the only loading signal: the list is not dimmed, so a fast reload does not flicker.
  await expect(page.getByRole('list').first()).toHaveCSS('opacity', '1')
  const sliderBlue = await page.getByLabel('Minimum AI score').evaluate(element => getComputedStyle(element).accentColor)
  await expect(bar).toHaveCSS('background-color', sliderBlue)

  release()
  await expect(bar).toBeHidden()
})
