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
  roles: Role[]
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

export type PageProps<
  T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
  auth: {
    user: User
  }
  flash: Flash
}
