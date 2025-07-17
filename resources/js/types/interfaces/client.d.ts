import { type User } from '@/types/interfaces/user'

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
}

export interface ClientAddress {
  id: number
  client_id: number
  address: string
  notes: string
  appointment_date: Date
}
