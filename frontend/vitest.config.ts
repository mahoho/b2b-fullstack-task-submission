import { defineConfig } from 'vitest/config'
import { defineVitestProject } from '@nuxt/test-utils/config'

export default defineConfig({
  test: {
    projects: [
      await defineVitestProject({
        test: {
          name: 'nuxt',
          include: ['tests/nuxt/**/*.spec.ts'],
          environment: 'nuxt',
          // Vitest 5 caches transformed modules on disk by default, and the cached copy of the
          // @nuxt/test-utils app entry stub fails to resolve its imports on the next run.
          fsModuleCache: false,
        },
      }),
    ],
  },
})
