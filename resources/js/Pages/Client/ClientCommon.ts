import { ClientAddress, CompanyContact } from '@/types'
import * as Yup from 'yup'
export {}

export const clientSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address'),
  phone: Yup.string().required('Name is required').max(20, 'Phone number must be 10 digits'),
  notes: Yup.string().max(20, 'Notes must be less than 500 characters'),
  address: Yup.string().max(500, 'Address must be less than 500 characters'),
  source: Yup.string()
    .required('Source is required')
})

export interface Client {
  id: number
  name: string
  email: string
  phone: string
  contact_type: string
  other_phone: string
  secondary_email: string
  source: string
  vip_clients: boolean
  vip_notes?: string
  refer_name?: string
  refer_phone?: string
  referral_id?: number
  company_contact_id?: number
  company_contact?: CompanyContact[]
}

export type ClientFormType = Client & {
  address: string
  appointment_date: Date | null
  notes: string
  confirmed: boolean
}

export type ClientEditFormType = any & {
  address: string
  appointment_date: Date | null
  notes: string
  confirmed: boolean
}
