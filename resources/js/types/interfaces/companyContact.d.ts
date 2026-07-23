import { type Client } from '@/Pages/Client/ClientCommon'

export interface CompanyContact {
  id: number
  name: string
  email: string
  phone: string
  phone_ext?: string | null
  website: string
  billing_street: string
  billing_state: string
  billing_city: string
  billing_code: string
  bid_due_date: Date | null
  clients?: Client[]
}
