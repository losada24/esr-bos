import { type Role } from '@/types/interfaces/role'

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string
  roles: Role[]
  has_frontdesk_admin_role?: boolean
  featured_image?: string
  phone: string
  status?: string
}
