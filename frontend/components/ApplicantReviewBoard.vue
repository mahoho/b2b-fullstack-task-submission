<script setup lang="ts">
const props = defineProps<{ campaignId: string }>()

const {
  applicants,
  pageMeta,
  loading,
  loadError,
  notFound,
  minScore,
  page,
  pendingIds,
  actionErrors,
  load,
  setStatus,
} = useApplicantReview(props.campaignId)

onMounted(load)

// Skeletons only for the very first load. Later reloads keep the list in place, so nothing jumps.
const initialLoading = computed(() => loading.value && !pageMeta.value)

const emptyMessage = computed(() => minScore.value > 0
  ? `No applicants scored ${minScore.value} or higher.`
  : 'No applicants yet.')
</script>

<template>
  <section class="mx-auto max-w-3xl space-y-6 p-6">
    <div class="flex items-center gap-4">
      <label
        for="min-score"
        class="text-sm font-medium text-gray-700"
      >Minimum AI score</label>
      <input
        id="min-score"
        v-model.number="minScore"
        type="range"
        min="0"
        max="100"
        step="5"
        class="w-64 accent-blue-700"
      >
      <output
        for="min-score"
        class="w-8 text-sm text-gray-900 tabular-nums"
      >{{ minScore }}</output>
    </div>

    <div
      v-if="initialLoading"
      role="status"
      aria-busy="true"
      class="space-y-4"
    >
      <span class="sr-only">Loading applicants</span>
      <div
        v-for="n in 3"
        :key="n"
        data-testid="skeleton"
        class="h-40 animate-pulse rounded-xl bg-gray-200"
      />
    </div>

    <p
      v-else-if="notFound"
      role="alert"
      class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"
    >
      Campaign not found.
    </p>

    <div
      v-else-if="loadError"
      role="alert"
      class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"
    >
      <p>Couldn't load applicants.</p>
      <button
        type="button"
        class="mt-3 rounded-lg bg-white px-3 py-1.5 font-medium text-red-800 ring-1 ring-red-300 ring-inset hover:bg-red-100"
        @click="load"
      >
        Retry
      </button>
    </div>

    <p
      v-else-if="applicants.length === 0"
      class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-600"
    >
      {{ emptyMessage }}
    </p>

    <ul
      v-else
      class="space-y-4"
      :aria-busy="loading"
    >
      <li
        v-for="applicant in applicants"
        :key="applicant.id"
      >
        <ApplicantReviewCard
          :applicant="applicant"
          :pending="pendingIds.has(applicant.id)"
          :error="actionErrors[applicant.id] ?? null"
          @update-status="status => setStatus(applicant.id, status)"
        />
      </li>
    </ul>

    <nav
      v-if="pageMeta && pageMeta.lastPage > 1"
      aria-label="Pagination"
      class="flex items-center justify-between text-sm"
    >
      <button
        type="button"
        class="rounded-lg px-3 py-1.5 font-medium ring-1 ring-gray-300 ring-inset disabled:opacity-50"
        :disabled="page <= 1 || loading"
        @click="page--"
      >
        Previous
      </button>
      <span class="text-gray-700">Page {{ pageMeta.currentPage }} of {{ pageMeta.lastPage }}</span>
      <button
        type="button"
        class="rounded-lg px-3 py-1.5 font-medium ring-1 ring-gray-300 ring-inset disabled:opacity-50"
        :disabled="page >= pageMeta.lastPage || loading"
        @click="page++"
      >
        Next
      </button>
    </nav>
  </section>
</template>
