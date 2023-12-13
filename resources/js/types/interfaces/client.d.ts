import { type User } from '@/types/interfaces/user'
import { type Company } from '@/types/interfaces/company'

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
  company_id: number
  company: Company
}
