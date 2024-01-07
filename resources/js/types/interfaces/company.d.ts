import { type User } from '@/types/interfaces/user'

export interface Company {
  id: number
  name: string
  phone_number: string
  email: string
  city: string
  state: string
  zip: string
  address: string
  user_id: number
  user: User
  featured_image: string
  allow_credit_payment: boolean
}
