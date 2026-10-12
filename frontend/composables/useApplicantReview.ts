import type {
  ApplicantDocumentData,
  ApplicantResourceData,
  ApplicantStatus,
  ApplicantSummaryDocumentData,
  PageMetaData,
} from '~/types/api'

const PAGE_SIZE = 10
const FILTER_DEBOUNCE_MS = 250
const ACTION_ERROR = 'Could not update the status. Try again.'

export function useApplicantReview(campaignId: string) {
  const applicants = ref<ApplicantResourceData[]>([])
  const pageMeta = ref<PageMetaData | null>(null)
  const loading = ref(true)
  const loadError = ref(false)
  const minScore = ref(0)
  const page = ref(1)
  const pendingIds = ref(new Set<string>())
  const actionErrors = ref<Record<string, string>>({})

  // Only the newest request may write to state, so a slow early response cannot replace a newer result.
  let latestRequest = 0

  async function load(): Promise<void> {
    const request = ++latestRequest
    loading.value = true
    loadError.value = false

    try {
      const document = await $fetch<ApplicantSummaryDocumentData>(
        `/api/v1/campaigns/${encodeURIComponent(campaignId)}/applicant-summary`,
        {
          query: {
            // At 0 the filter is off. Sending min_score=0 would hide applicants the AI has not scored yet.
            ...(minScore.value > 0 ? { min_score: minScore.value } : {}),
            'page[number]': page.value,
            'page[size]': PAGE_SIZE,
          },
        },
      )
      if (request !== latestRequest) return
      applicants.value = document.data
      pageMeta.value = document.meta.page
    }
    catch {
      if (request !== latestRequest) return
      loadError.value = true
    }
    finally {
      if (request === latestRequest) loading.value = false
    }
  }

  let debounce: ReturnType<typeof setTimeout> | undefined

  watch(minScore, () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
      if (page.value === 1) load()
      else page.value = 1
    }, FILTER_DEBOUNCE_MS)
  })

  watch(page, load)

  onScopeDispose(() => clearTimeout(debounce))

  async function setStatus(id: string, status: ApplicantStatus): Promise<void> {
    const applicant = applicants.value.find(candidate => candidate.id === id)
    if (!applicant || pendingIds.value.has(id)) return

    const previous = applicant.attributes.status
    applicant.attributes.status = status
    pendingIds.value.add(id)
    delete actionErrors.value[id]

    try {
      await $fetch<ApplicantDocumentData>(`/api/v1/applicants/${encodeURIComponent(id)}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/vnd.api+json' },
        body: { data: { type: 'applicants', id, attributes: { status } } },
      })
    }
    catch {
      applicant.attributes.status = previous
      actionErrors.value[id] = ACTION_ERROR
    }
    finally {
      pendingIds.value.delete(id)
    }
  }

  return { applicants, pageMeta, loading, loadError, minScore, page, pendingIds, actionErrors, load, setStatus }
}
