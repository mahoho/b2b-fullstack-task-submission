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
  const notFound = ref(false)
  const minScore = ref(0)
  const status = ref<ApplicantStatus | ''>('')
  const search = ref('')
  const page = ref(1)
  const pendingIds = ref(new Set<string>())
  const actionErrors = ref(new Map<string, string>())

  // NuxtLoadingIndicator only follows route changes by itself, so data loads drive it by hand.
  const indicator = useLoadingIndicator()

  // Only the newest request may write to state, so a slow early response cannot replace a newer result.
  let latestRequest = 0

  async function load(): Promise<void> {
    const request = ++latestRequest
    loading.value = true
    loadError.value = false
    notFound.value = false
    indicator.start()
    let failed = false

    try {
      const term = search.value.trim()
      const document = await $fetch<ApplicantSummaryDocumentData>(
        `/api/v1/campaigns/${encodeURIComponent(campaignId)}/applicant-summary`,
        {
          query: {
            // At 0 the filter is off. Sending min_score=0 would hide applicants the AI has not scored yet.
            ...(minScore.value > 0 ? { min_score: minScore.value } : {}),
            ...(status.value !== '' ? { status: status.value } : {}),
            ...(term !== '' ? { search: term } : {}),
            'page[number]': page.value,
            'page[size]': PAGE_SIZE,
          },
        },
      )
      if (request !== latestRequest) return
      applicants.value = document.data
      pageMeta.value = document.meta.page
    }
    catch (error) {
      if (request !== latestRequest) return
      failed = true
      // An unknown campaign, or another partner's, cannot succeed on retry, so it gets its own state.
      notFound.value = (error as { statusCode?: number }).statusCode === 404
      loadError.value = !notFound.value
    }
    finally {
      if (request === latestRequest) {
        loading.value = false
        indicator.finish({ error: failed })
      }
    }
  }

  let debounce: ReturnType<typeof setTimeout> | undefined

  watch([minScore, status, search], () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
      if (page.value === 1) load()
      else page.value = 1
    }, FILTER_DEBOUNCE_MS)
  })

  watch(page, load)

  onScopeDispose(() => clearTimeout(debounce))

  async function setStatus(id: string, newStatus: ApplicantStatus): Promise<void> {
    const applicant = applicants.value.find(candidate => candidate.id === id)
    if (!applicant || pendingIds.value.has(id)) return

    const previous = applicant.attributes.status
    applicant.attributes.status = newStatus
    pendingIds.value.add(id)
    actionErrors.value.delete(id)

    // A filter or page change can replace the list while the PATCH is in flight, so the outcome is
    // written to whichever row is on screen now, not to the object that was clicked.
    const current = () => applicants.value.find(candidate => candidate.id === id)

    try {
      const confirmed = await $fetch<ApplicantDocumentData>(`/api/v1/applicants/${encodeURIComponent(id)}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/vnd.api+json' },
        body: { data: { type: 'applicants', id, attributes: { status: newStatus } } },
      })
      const row = current()
      if (row) row.attributes.status = confirmed.data.attributes.status
    }
    catch {
      const row = current()
      if (row) row.attributes.status = previous
      actionErrors.value.set(id, ACTION_ERROR)
    }
    finally {
      pendingIds.value.delete(id)
    }
  }

  return { applicants, pageMeta, loading, loadError, notFound, minScore, status, search, page, pendingIds, actionErrors, load, setStatus }
}
