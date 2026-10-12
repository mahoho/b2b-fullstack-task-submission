<script setup lang="ts">
const { public: { demoCampaignId } } = useRuntimeConfig()

await navigateTo(`/campaigns/${demoCampaignId}`, { redirectCode: 302 })
</script>

<template>
  <div />
</template>
