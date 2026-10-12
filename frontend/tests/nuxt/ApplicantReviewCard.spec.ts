import { describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import type { VueWrapper } from '@vue/test-utils'
import ApplicantReviewCard from '~/components/ApplicantReviewCard.vue'
import type { ApplicantAttributesData } from '~/types/api'
import { makeApplicant } from './fixtures'

function mountCard(attributes: Partial<ApplicantAttributesData> = {}, props: { pending?: boolean, error?: string | null } = {}) {
  return mountSuspended(ApplicantReviewCard, {
    props: { applicant: makeApplicant(attributes), pending: props.pending ?? false, error: props.error ?? null },
  })
}

function button(wrapper: VueWrapper, name: string) {
  const match = wrapper.findAll('button').find(candidate => candidate.text() === name)
  if (!match) throw new Error(`No button named "${name}"`)
  return match
}

describe('ApplicantReviewCard', () => {
  it('renders the name, score, date and first three highlights', async () => {
    const wrapper = await mountCard({
      name: 'Ada Lovelace',
      aiScore: 94,
      tier: 'top',
      appliedAt: '2026-10-01T09:30:00+00:00',
      highlights: ['One', 'Two', 'Three', 'Four'],
    })

    expect(wrapper.get('h3').text()).toBe('Ada Lovelace')
    expect(wrapper.get('[data-testid="score-badge"]').text()).toContain('94')
    expect(wrapper.text()).toContain('Oct 1, 2026')
    expect(wrapper.findAll('li').map(item => item.text())).toEqual(['One', 'Two', 'Three'])
    expect(wrapper.get('[data-testid="status"]').text()).toBe('Pending review')
  })

  it('shows the UTC date for an application late in the day', async () => {
    const wrapper = await mountCard({ appliedAt: '2026-10-01T23:30:00+00:00' })

    expect(wrapper.text()).toContain('Oct 1, 2026')
  })

  it.each([
    ['top', 94, 'bg-emerald-100', 'AI match score 94 out of 100, top tier'],
    ['eligible', 75, 'bg-blue-100', 'AI match score 75 out of 100, eligible'],
    ['below', 40, 'bg-amber-100', 'AI match score 40 out of 100, below threshold'],
  ] as const)('colours the badge for the %s tier', async (tier, aiScore, colour, label) => {
    const badge = (await mountCard({ tier, aiScore })).get('[data-testid="score-badge"]')

    expect(badge.classes()).toContain(colour)
    expect(badge.text()).toContain(label)
  })

  it('shows "Not scored" for an applicant without a score', async () => {
    const badge = (await mountCard({ tier: 'unscored', aiScore: null })).get('[data-testid="score-badge"]')

    expect(badge.classes()).toContain('bg-gray-100')
    expect(badge.text()).toContain('Not scored')
    expect(badge.text()).toContain('AI match score not available yet')
  })

  it('emits the new status when Shortlist or Reject is clicked', async () => {
    const wrapper = await mountCard()

    await button(wrapper, 'Shortlist').trigger('click')
    await button(wrapper, 'Reject').trigger('click')

    expect(wrapper.emitted('update-status')).toEqual([['shortlisted'], ['rejected']])
  })

  it('disables both actions while a change is pending', async () => {
    const wrapper = await mountCard({}, { pending: true })

    expect(button(wrapper, 'Shortlist').attributes('disabled')).toBeDefined()
    expect(button(wrapper, 'Reject').attributes('disabled')).toBeDefined()
    await button(wrapper, 'Shortlist').trigger('click')
    expect(wrapper.emitted('update-status')).toBeUndefined()
  })

  it('marks the current status as pressed and disables that button', async () => {
    const wrapper = await mountCard({ status: 'shortlisted' })

    expect(button(wrapper, 'Shortlist').attributes('aria-pressed')).toBe('true')
    expect(button(wrapper, 'Shortlist').attributes('disabled')).toBeDefined()
    expect(button(wrapper, 'Reject').attributes('aria-pressed')).toBe('false')
    expect(button(wrapper, 'Reject').attributes('disabled')).toBeUndefined()
    expect(wrapper.get('[data-testid="status"]').text()).toBe('Shortlisted')
  })

  it('toggles details with the email and every highlight', async () => {
    const wrapper = await mountCard({ email: 'ada@example.com', highlights: ['One', 'Two', 'Three', 'Four'] })
    const toggle = button(wrapper, 'View details')

    expect(toggle.attributes('aria-expanded')).toBe('false')
    expect(wrapper.text()).not.toContain('ada@example.com')

    await toggle.trigger('click')

    expect(button(wrapper, 'Hide details').attributes('aria-expanded')).toBe('true')
    expect(wrapper.text()).toContain('ada@example.com')
    expect(wrapper.findAll('li')).toHaveLength(4)
  })

  it.each([
    ['pending', 'Pending review', 'bg-gray-100'],
    ['shortlisted', 'Shortlisted', 'bg-emerald-100'],
    ['rejected', 'Rejected', 'bg-red-100'],
  ] as const)('shows the %s status as a coloured pill', async (statusValue, label, colour) => {
    const pill = (await mountCard({ status: statusValue })).get('[data-testid="status"]')

    expect(pill.text()).toBe(label)
    expect(pill.classes()).toEqual(expect.arrayContaining(['rounded-full', colour]))
  })

  it('announces an action error in the live region', async () => {
    const wrapper = await mountCard({}, { error: 'Could not update the status. Try again.' })

    expect(wrapper.get('[aria-live="polite"]').text()).toContain('Could not update the status. Try again.')
  })
})
