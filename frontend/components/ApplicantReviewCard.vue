<script setup lang="ts">
import type { ApplicantResourceData, ApplicantStatus, ScoreTier } from '~/types/api'

const props = defineProps<{
  applicant: ApplicantResourceData
  pending: boolean
  error: string | null
}>()

const emit = defineEmits<{ 'update-status': [status: ApplicantStatus] }>()

const badgeClasses: Record<ScoreTier, string> = {
  top: 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
  eligible: 'bg-blue-100 text-blue-800 ring-blue-600/20',
  below: 'bg-amber-100 text-amber-900 ring-amber-600/20',
  unscored: 'bg-gray-100 text-gray-700 ring-gray-500/20',
}

const tierLabels: Record<ScoreTier, string> = {
  top: 'top tier',
  eligible: 'eligible',
  below: 'below threshold',
  unscored: 'not scored yet',
}

const statusClasses: Record<ApplicantStatus, string> = {
  pending: 'bg-gray-100 text-gray-800',
  shortlisted: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-red-100 text-red-800',
}

// Fixed locale and UTC so the date reads the same on every machine, including in tests.
const dateFormat = new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeZone: 'UTC' })

const expanded = ref(false)
const attributes = computed(() => props.applicant.attributes)
const nameId = computed(() => `applicant-${props.applicant.id}-name`)
const detailsId = computed(() => `applicant-${props.applicant.id}-details`)
const appliedOn = computed(() => dateFormat.format(new Date(attributes.value.appliedAt)))
const highlights = computed(() => expanded.value ? attributes.value.highlights : attributes.value.highlights.slice(0, 3))
const scoreLabel = computed(() => attributes.value.aiScore === null
  ? 'AI match score not available yet'
  : `AI match score ${attributes.value.aiScore} out of 100, ${tierLabels[attributes.value.tier]}`)

const actionClasses = 'rounded-lg px-3 py-1.5 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-50'
</script>

<template>
  <article
    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
    :aria-labelledby="nameId"
    :aria-busy="pending"
  >
    <header class="flex items-start justify-between gap-4">
      <div>
        <h3
          :id="nameId"
          class="text-base font-semibold text-gray-900"
        >
          {{ attributes.name }}
        </h3>
        <p class="text-sm text-gray-600">
          Applied <time :datetime="attributes.appliedAt">{{ appliedOn }}</time>
        </p>
      </div>
      <span
        data-testid="score-badge"
        class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-sm font-semibold ring-1 ring-inset"
        :class="badgeClasses[attributes.tier]"
      >
        <span aria-hidden="true">{{ attributes.aiScore ?? 'Not scored' }}</span>
        <span class="sr-only">{{ scoreLabel }}</span>
      </span>
    </header>

    <div
      :id="detailsId"
      class="mt-4 space-y-3 text-sm text-gray-700"
    >
      <ul class="list-disc space-y-1 pl-5">
        <li
          v-for="(highlight, index) in highlights"
          :key="index"
        >
          {{ highlight }}
        </li>
      </ul>
      <p v-if="expanded">
        <a
          :href="`mailto:${attributes.email}`"
          class="text-blue-700 underline"
        >{{ attributes.email }}</a>
      </p>
    </div>

    <p
      class="mt-4 text-sm"
      aria-live="polite"
    >
      <span
        data-testid="status"
        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
        :class="statusClasses[attributes.status]"
      >{{ statusLabels[attributes.status] }}</span>
      <span
        v-if="error"
        class="ml-2 text-red-700"
      >{{ error }}</span>
    </p>

    <footer class="mt-4 flex flex-wrap items-center gap-2">
      <button
        type="button"
        :class="[actionClasses, 'bg-emerald-700 text-white hover:bg-emerald-800 focus-visible:outline-emerald-700']"
        :aria-pressed="attributes.status === 'shortlisted'"
        :disabled="pending || attributes.status === 'shortlisted'"
        @click="emit('update-status', 'shortlisted')"
      >
        Shortlist
      </button>
      <button
        type="button"
        :class="[actionClasses, 'bg-white text-red-700 ring-1 ring-red-300 ring-inset hover:bg-red-50 focus-visible:outline-red-700']"
        :aria-pressed="attributes.status === 'rejected'"
        :disabled="pending || attributes.status === 'rejected'"
        @click="emit('update-status', 'rejected')"
      >
        Reject
      </button>
      <button
        type="button"
        class="ml-auto text-sm font-medium text-gray-700 underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-700"
        :aria-expanded="expanded"
        :aria-controls="detailsId"
        @click="expanded = !expanded"
      >
        {{ expanded ? 'Hide details' : 'View details' }}
      </button>
    </footer>
  </article>
</template>
