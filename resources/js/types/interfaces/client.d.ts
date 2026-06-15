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
  created_by_user_id?: number | null
  created_by_user?: User | null
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
  refer_email?: string
  referral_id?: number | null
  referrer_client_id?: number | null
  referrer_user_id?: number | null
  referral?: {
    id: number
    name?: string
    phone?: string
    email?: string
    client_id?: number | null
    user_id?: number | null
    referrerClient?: {
      id: number
      name: string
      phone?: string
      email?: string
    }
    referrerUser?: {
      id: number
      name: string
      phone?: string
      email?: string
      status?: string
    }
  }
  company_contact_id?: number
  company_contact_ids?: number[]
  company_contact?: CompanyContact
  company_contacts?: CompanyContact[]
  is_contact?: boolean
}

export interface ClientAddress {
  id: number
  client_id: number
  address: string
  notes: string
  appointment_date: Date
}
