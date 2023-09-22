export interface Role {
  id: number
  name: string
  guard_name: string
  created_at: Date
  updated_at: Date
}

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string
  reference_code: string
  referrals_count: number
  roles: Role[]
}

export interface ReferralsStatusUpdate {
  id: number
  referral_id: number
  status: string
  notes: string
  user: User
  created_at: Date
  updated_at: Date
}

export interface Referred {
  id: number
  user_id: number
  name: string
  email: string
  phone: string
  notes: string
  status: string
  created_at: Date
  updated_at: Date
  user: User
  referrals_status_update: ReferralsStatusUpdate[]
}

export interface PaginatorLink {
  active: boolean
  label: string
  url: string
}

export interface Flash {
  success: string
  error: string
}

export interface ReferralsByMonth {
  months: string[]
  counts: number[]
  year: number
}

export interface ReferralsByStatusCount {
  status: string
  count: number
}

export interface Auth {
  user: User
}

interface ListUsersItem {
  id: number
  name: string
}

export type PageProps<
  T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
  auth: {
    user: User
  }
  flash: Flash
}
