<script setup lang="ts">
const route = useRoute()
const campaignId = computed(() => String(route.params.id))

useHead({ title: 'Applicant review' })
</script>

<template>
  <main class="min-h-screen bg-gray-50">
    <ApplicantReviewBoard
      :key="campaignId"
      :campaign-id="campaignId"
    />
  </main>
</template>
