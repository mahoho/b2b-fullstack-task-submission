// composables/useApplicantReview.ts
import type {
  ApplicantResourceData,
  ApplicantStatus,
  ApplicantSummaryDocumentData,
  PageMetaData,
} from '~/types/api' // adjust to wherever the generated types live

const PAGE_SIZE = 10

export function useApplicantReview(campaignId: string) {
  const applicants = ref<ApplicantResourceData[]>([])
  const pageMeta = ref<PageMetaData | null>(null)
  const loading = ref(false)
  const loadError = ref(false)
  const minScore = ref(0)
  const page = ref(1)
  const pendingIds = ref<Set<string>>(new Set())
  const actionErrors = ref<Record<string, string>>({})

  // Only the latest request may write state, so a slow older response cannot overwrite a newer one.
  let loadSeq = 0

  async function load() {
    const seq = ++loadSeq
    loading.value = true
    loadError.value = false
    try {
      const res = await $fetch<ApplicantSummaryDocumentData>(
        `/api/v1/campaigns/${encodeURIComponent(campaignId)}/applicant-summary`,
        {
          query: {
            min_score: minScore.value,
            'page[number]': page.value,
            'page[size]': PAGE_SIZE,
          },
        },
      )
      if (seq !== loadSeq) return
      applicants.value = res.data
      pageMeta.value = res.meta.page
    } catch {
      if (seq !== loadSeq) return
      loadError.value = true
    } finally {
      if (seq === loadSeq) loading.value = false
    }
  }

  async function setStatus(id: string, status: ApplicantStatus) {
    const applicant = applicants.value.find(a => a.id === id)
    if (!applicant || pendingIds.value.has(id)) return

    const previous = applicant.attributes.status
    const { [id]: _cleared, ...rest } = actionErrors.value
    actionErrors.value = rest
    applicant.attributes.status = status
    pendingIds.value = new Set(pendingIds.value).add(id)

    try {
      await $fetch(`/api/v1/applicants/${encodeURIComponent(id)}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/vnd.api+json',
          Accept: 'application/vnd.api+json',
        },
        body: JSON.stringify({
          data: { type: 'applicants', id, attributes: { status } },
        }),
      })
    } catch {
      // Re-find the row: a reload may have replaced the object while the request was in flight.
      const current = applicants.value.find(a => a.id === id)
      if (current) current.attributes.status = previous
      actionErrors.value = {
        ...actionErrors.value,
        [id]: 'Could not update the status. Try again.',
      }
    } finally {
      const next = new Set(pendingIds.value)
      next.delete(id)
      pendingIds.value = next
    }
  }

  let debounceTimer: ReturnType<typeof setTimeout> | undefined

  watch(minScore, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      // If not on page 1, changing page triggers the page watcher's reload; otherwise reload here.
      if (page.value !== 1) page.value = 1
      else load()
    }, 250)
  })

  watch(page, () => load())

  onScopeDispose(() => clearTimeout(debounceTimer))

  return {
    applicants,
    pageMeta,
    loading,
    loadError,
    minScore,
    page,
    pendingIds,
    actionErrors,
    load,
    setStatus,
  }
}
