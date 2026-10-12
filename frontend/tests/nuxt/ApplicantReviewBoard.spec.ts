import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mountSuspended, registerEndpoint } from '@nuxt/test-utils/runtime'
import { flushPromises, type DOMWrapper, type VueWrapper } from '@vue/test-utils'
import { createError, getQuery, readBody } from 'h3'
import ApplicantReviewBoard from '~/components/ApplicantReviewBoard.vue'
import type { ApplicantResourceData } from '~/types/api'
import { makeApplicant, makeDocument } from './fixtures'

const CAMPAIGN = '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81'
const MISSING_CAMPAIGN = '33333333-3333-4333-8333-333333333333'
const ada = makeApplicant({ name: 'Ada Lovelace', aiScore: 94, tier: 'top' }, '11111111-1111-4111-8111-111111111111')
const charles = makeApplicant({ name: 'Charles Babbage', aiScore: 62, tier: 'below' }, '22222222-2222-4222-8222-222222222222')

let applicants: ApplicantResourceData[] = []
let lastPage = 1
let summaryQueries: Record<string, unknown>[] = []
let summaryFails = false
let unfilteredGate: Promise<void> | null = null
let patchBodies: unknown[] = []
let patchGate: Promise<void> = Promise.resolve()
let patchFails = false

registerEndpoint(`/api/v1/campaigns/${CAMPAIGN}/applicant-summary`, async (event) => {
  const query = getQuery(event)
  summaryQueries.push(query)
  if (!query.min_score && unfilteredGate) await unfilteredGate
  if (summaryFails) throw createError({ statusCode: 500, statusMessage: 'Server Error' })
  const min = Number(query.min_score ?? 0)
  const matching = applicants.filter(applicant => (applicant.attributes.aiScore ?? -1) >= min)
  return structuredClone(makeDocument(matching, { currentPage: Number(query['page[number]'] ?? 1), lastPage }))
})

registerEndpoint(`/api/v1/campaigns/${MISSING_CAMPAIGN}/applicant-summary`, () => {
  throw createError({ statusCode: 404, statusMessage: 'Not Found' })
})

for (const applicant of [ada, charles]) {
  registerEndpoint(`/api/v1/applicants/${applicant.id}`, {
    method: 'PATCH',
    handler: async (event) => {
      const body = await readBody(event)
      patchBodies.push(body)
      await patchGate
      if (patchFails) throw createError({ statusCode: 500, statusMessage: 'Server Error' })
      return { data: { ...applicant, attributes: { ...applicant.attributes, status: body.data.attributes.status } } }
    },
  })
}

function button(wrapper: VueWrapper | DOMWrapper<Element>, name: string) {
  const match = wrapper.findAll('button').find(candidate => candidate.text() === name)
  if (!match) throw new Error(`No button named "${name}"`)
  return match
}

async function mountBoard() {
  const wrapper = await mountSuspended(ApplicantReviewBoard, { props: { campaignId: CAMPAIGN } })
  await vi.waitFor(() => expect(wrapper.find('[role="status"]').exists()).toBe(false))
  return wrapper
}

const cards = (wrapper: VueWrapper) => wrapper.findAll('article')
const status = (card: DOMWrapper<Element>) => card.get('[data-testid="status"]').text()

beforeEach(() => {
  applicants = [ada, charles]
  lastPage = 1
  summaryQueries = []
  summaryFails = false
  unfilteredGate = null
  patchBodies = []
  patchGate = Promise.resolve()
  patchFails = false
})

describe('ApplicantReviewBoard', () => {
  it('shows skeletons first, then the applicants from the API', async () => {
    let release!: () => void
    unfilteredGate = new Promise((resolve) => { release = resolve })

    const wrapper = await mountSuspended(ApplicantReviewBoard, { props: { campaignId: CAMPAIGN } })

    expect(wrapper.findAll('[data-testid="skeleton"]')).toHaveLength(3)
    release()
    await vi.waitFor(() => expect(cards(wrapper)).toHaveLength(2))
    expect(wrapper.text()).toContain('Ada Lovelace')
    expect(wrapper.text()).toContain('Charles Babbage')
    expect(wrapper.find('nav[aria-label="Pagination"]').exists()).toBe(false)
  })

  it('pages with Next and goes back to page 1 when the filter changes', async () => {
    lastPage = 2
    const wrapper = await mountBoard()
    const nav = () => wrapper.get('nav[aria-label="Pagination"]')

    expect(nav().text()).toContain('Page 1 of 2')
    await button(wrapper, 'Next').trigger('click')
    await vi.waitFor(() => expect(nav().text()).toContain('Page 2 of 2'))
    expect(summaryQueries.at(-1)).toMatchObject({ 'page[number]': '2' })

    await wrapper.get('input[type="range"]').setValue('80')
    await vi.waitFor(() => expect(summaryQueries.at(-1)).toMatchObject({ 'min_score': '80', 'page[number]': '1' }))
  })

  it('keeps the list in place while the next page loads', async () => {
    lastPage = 2
    const wrapper = await mountBoard()
    let release!: () => void
    unfilteredGate = new Promise((resolve) => { release = resolve })

    await button(wrapper, 'Next').trigger('click')
    await vi.waitFor(() => expect(summaryQueries).toHaveLength(2))
    await flushPromises()

    expect(wrapper.findAll('[data-testid="skeleton"]')).toHaveLength(0)
    expect(cards(wrapper)).toHaveLength(2)
    release()
    await vi.waitFor(() => expect(wrapper.get('nav[aria-label="Pagination"]').text()).toContain('Page 2 of 2'))
  })

  it('says the campaign was not found instead of offering a retry', async () => {
    const wrapper = await mountSuspended(ApplicantReviewBoard, { props: { campaignId: MISSING_CAMPAIGN } })

    await vi.waitFor(() => expect(wrapper.text()).toContain('Campaign not found.'))
    expect(wrapper.findAll('button').map(candidate => candidate.text())).not.toContain('Retry')
  })

  it('omits min_score at 0 and asks for the first page of 10', async () => {
    await mountBoard()

    expect(summaryQueries[0]).toEqual({ 'page[number]': '1', 'page[size]': '10' })
  })

  it('filters by minimum score after the slider moves', async () => {
    const wrapper = await mountBoard()

    await wrapper.get('input[type="range"]').setValue('80')

    await vi.waitFor(() => expect(cards(wrapper)).toHaveLength(1))
    expect(wrapper.text()).toContain('Ada Lovelace')
    expect(wrapper.get('output').text()).toBe('80')
    expect(summaryQueries.at(-1)).toEqual({ 'min_score': '80', 'page[number]': '1', 'page[size]': '10' })
  })

  it('sends one request for a burst of slider moves', async () => {
    const wrapper = await mountBoard()
    const before = summaryQueries.length

    for (const value of ['20', '40', '60', '80']) {
      await wrapper.get('input[type="range"]').setValue(value)
    }

    await vi.waitFor(() => expect(cards(wrapper)).toHaveLength(1))
    expect(summaryQueries.length - before).toBe(1)
    expect(summaryQueries.at(-1)).toMatchObject({ min_score: '80' })
  })

  it('ignores a slow response from an older request', async () => {
    let release!: () => void
    unfilteredGate = new Promise((resolve) => { release = resolve })

    const wrapper = await mountSuspended(ApplicantReviewBoard, { props: { campaignId: CAMPAIGN } })
    await wrapper.get('input[type="range"]').setValue('80')
    await vi.waitFor(() => expect(cards(wrapper)).toHaveLength(1))

    release()
    await flushPromises()
    await new Promise(resolve => setTimeout(resolve, 50))

    expect(cards(wrapper)).toHaveLength(1)
    expect(wrapper.text()).not.toContain('Charles Babbage')
  })

  it('shows the new status before the PATCH resolves', async () => {
    let release!: () => void
    patchGate = new Promise((resolve) => { release = resolve })
    const wrapper = await mountBoard()

    await button(cards(wrapper)[0]!, 'Shortlist').trigger('click')

    expect(status(cards(wrapper)[0]!)).toBe('Shortlisted')
    expect(button(cards(wrapper)[0]!, 'Reject').attributes('disabled')).toBeDefined()

    release()
    await vi.waitFor(() => expect(button(cards(wrapper)[0]!, 'Reject').attributes('disabled')).toBeUndefined())
    expect(status(cards(wrapper)[0]!)).toBe('Shortlisted')
    expect(patchBodies).toEqual([{ data: { type: 'applicants', id: ada.id, attributes: { status: 'shortlisted' } } }])
  })

  it('keeps the confirmed status when the list reloads during the PATCH', async () => {
    let release!: () => void
    patchGate = new Promise((resolve) => { release = resolve })
    const wrapper = await mountBoard()

    await button(cards(wrapper)[0]!, 'Shortlist').trigger('click')
    await wrapper.get('input[type="range"]').setValue('80')
    await vi.waitFor(() => expect(summaryQueries.at(-1)).toMatchObject({ min_score: '80' }))
    await vi.waitFor(() => expect(cards(wrapper)).toHaveLength(1))

    release()

    await vi.waitFor(() => expect(status(cards(wrapper)[0]!)).toBe('Shortlisted'))
  })

  it('rolls back and shows an error when the PATCH fails', async () => {
    patchFails = true
    const wrapper = await mountBoard()

    await button(cards(wrapper)[0]!, 'Shortlist').trigger('click')

    await vi.waitFor(() => expect(cards(wrapper)[0]!.text()).toContain('Could not update the status. Try again.'))
    expect(status(cards(wrapper)[0]!)).toBe('Pending review')
  })

  it('shows the empty states', async () => {
    applicants = []
    const empty = await mountBoard()
    expect(empty.text()).toContain('No applicants yet.')

    applicants = [charles]
    const filtered = await mountBoard()
    await filtered.get('input[type="range"]').setValue('80')
    await vi.waitFor(() => expect(filtered.text()).toContain('No applicants scored 80 or higher.'))
  })

  it('shows an error with a working Retry when loading fails', async () => {
    summaryFails = true
    const wrapper = await mountBoard()

    expect(wrapper.get('[role="alert"]').text()).toContain('Couldn\'t load applicants.')

    summaryFails = false
    await button(wrapper, 'Retry').trigger('click')
    await vi.waitFor(() => expect(cards(wrapper)).toHaveLength(2))
  })
})
