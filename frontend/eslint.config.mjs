import { withNuxt } from './.nuxt/eslint.config.mjs'

// types/api.ts is generated from the backend DTOs (composer types), so it is not linted.
export default withNuxt({ ignores: ['types/api.ts'] })
