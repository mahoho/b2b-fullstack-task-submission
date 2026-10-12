<template>
  <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <header class="flex items-start justify-between gap-3">
      <h3 class="text-lg font-semibold">{{ attrs.name }}</h3>
      <span
        data-testid="score-badge"
        :class="['rounded-full px-2.5 py-0.5 text-sm font-medium', tierClass[attrs.tier]]"
      >
        {{ attrs.tier === 'unscored' || attrs.aiScore === null ? 'Not scored' : attrs.aiScore }}
      </span>
    </header>

    <p class="mt-1 text-sm text-gray-500">Applied {{ appliedDate }}</p>

    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
      <li v-for="(h, i) in attrs.highlights.slice(0, 3)" :key="i">{{ h }}</li>
    </ul>

    <p class="mt-3 text-sm">
      Status: <span data-testid="status" class="font-medium">{{ statusLabel[attrs.status] }}</span>
    </p>

    <p v-if="error" role="alert" class="mt-2 text-sm text-red-600">{{ error }}</p>

    <div class="mt-4 flex flex-wrap gap-2">
      <button
        type="button"
        class="rounded bg-emerald-600 px-3 py-1.5 text-sm text-white disabled:opacity-50"
        :disabled="pending"
        @click="emit('update-status', 'shortlisted')"
      >
        Shortlist
      </button>
      <button
        type="button"
        class="rounded bg-red-600 px-3 py-1.5 text-sm text-white disabled:opacity-50"
        :disabled="pending"
        @click="emit('update-status', 'rejected')"
      >
        Reject
      </button>
      <button
        type="button"
        class="rounded border border-gray-300 px-3 py-1.5 text-sm"
        :aria-expanded="open"
        @click="open = !open"
      >
        View details
      </button>
    </div>

    <div v-if="open" class="mt-4 border-t border-gray-200 pt-3 text-sm">
      <p>{{ attrs.email }}</p>
      <ul class="mt-2 list-disc space-y-1 pl-5">
        <li v-for="(h, i) in attrs.highlights" :key="i">{{ h }}</li>
      </ul>
    </div>
  </article>
</template>

<script setup lang="ts">
import type { ApplicantResourceData, ApplicantStatus, ScoreTier } from '~/types/api'

const props = defineProps<{
  applicant: ApplicantResourceData
  pending: boolean
  error: string | null
}>()

const emit = defineEmits<{
  'update-status': [status: ApplicantStatus]
}>()

const open = ref(false)

const attrs = computed(() => props.applicant.attributes)

const tierClass: Record<ScoreTier, string> = {
  top: 'bg-emerald-100 text-emerald-800',
  eligible: 'bg-blue-100 text-blue-800',
  below: 'bg-amber-100 text-amber-800',
  unscored: 'bg-gray-100 text-gray-700',
}

const statusLabel: Record<ApplicantStatus, string> = {
  pending: 'Pending review',
  shortlisted: 'Shortlisted',
  rejected: 'Rejected',
}

const appliedDate = computed(() =>
  new Date(attrs.value.appliedAt).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC',
  }),
)
</script>
