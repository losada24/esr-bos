import { type User } from '@/types/interfaces/user'
import { type CompanyContact } from '@/types/interfaces/companyContact'

export interface Client {
  id: number
  name: string
  last_name: string
  email: string
  phone?: string
  city?: string
  state?: string
  zip?: string
  address?: string
  user_id?: number
  user?: User
  client_address: ClientAddress[]
  updated_at: Date
  vip_clients: boolean
  vip_notes: string
  contact_type: string
  source: string
  other_phone?: string
  secondary_email?: string
  refer_name?: string
  refer_phone?: string
  referral_id?: number
  company_contact_id?: number
  company_contact?: CompanyContact
  is_contact?: boolean
}

export interface ClientAddress {
  id: number
  client_id: number
  address: string
  notes: string
  appointment_date: Date
}
