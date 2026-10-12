import type { ApplicantStatus } from '~/types/api'

// Shared by the cards and the status filter, so a new status gets one label and shows up in both.
export const statusLabels: Record<ApplicantStatus, string> = {
  pending: 'Pending review',
  shortlisted: 'Shortlisted',
  rejected: 'Rejected',
}
