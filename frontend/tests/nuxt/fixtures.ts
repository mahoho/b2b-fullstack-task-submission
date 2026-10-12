import type { ApplicantAttributesData, ApplicantResourceData, ApplicantSummaryDocumentData } from '../../types/api'

let sequence = 0

export function makeApplicant(attributes: Partial<ApplicantAttributesData> = {}, id?: string): ApplicantResourceData {
  sequence += 1

  return {
    type: 'applicants',
    id: id ?? `00000000-0000-4000-8000-${String(sequence).padStart(12, '0')}`,
    attributes: {
      name: `Applicant ${sequence}`,
      email: `applicant${sequence}@example.com`,
      aiScore: 80,
      tier: 'eligible',
      status: 'pending',
      appliedAt: '2026-10-01T09:00:00+00:00',
      highlights: ['Strong maths portfolio'],
      ...attributes,
    },
  }
}

export function makeDocument(
  data: ApplicantResourceData[],
  page: { currentPage?: number, lastPage?: number } = {},
): ApplicantSummaryDocumentData {
  const currentPage = page.currentPage ?? 1
  const lastPage = page.lastPage ?? 1

  return {
    data,
    meta: {
      summary: { totalApplicants: data.length, eligibleApplicants: data.length, averageAiScore: 80, topTier: [] },
      page: { currentPage, perPage: 10, total: data.length, lastPage },
    },
    links: { self: '', first: '', prev: null, next: null, last: '' },
  }
}
