import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  compatibilityDate: '2026-10-01',
  srcDir: '.',
  devtools: { enabled: false },
  modules: ['@nuxt/test-utils/module'],
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },
  runtimeConfig: {
    // Empty on purpose: filled from NUXT_API_BASE and NUXT_API_TOKEN (see .env.example).
    apiBase: '',
    apiToken: '',
    public: {
      demoCampaignId: '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81',
    },
  },
})
