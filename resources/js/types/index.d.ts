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

export interface Client {
  id: number
  name: string
  email: string
  phone: string
  city: string
  state: string
  zip: string
  address: string
  user_id: number
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
