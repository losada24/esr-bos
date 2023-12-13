import { type Role } from '@/types/interfaces/role'
import { type Company } from '@/types/interfaces/company'

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string
  roles: Role[]
  company_id: number
  company?: Company
}
