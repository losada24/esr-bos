import { type Role } from '@/types/interfaces/role'

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string
  roles: Role[]
  featured_image?: string
}
