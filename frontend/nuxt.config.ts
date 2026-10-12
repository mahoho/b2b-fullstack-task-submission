import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  compatibilityDate: '2026-10-01',
  srcDir: '.',
  devtools: { enabled: false },
  modules: ['@nuxt/test-utils/module'],
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },
  runtimeConfig: {
    apiBase: 'http://127.0.0.1:8000',
    apiToken: 'demo-partner-token',
    public: {
      demoCampaignId: '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81',
    },
  },
})
