import { type TagItem } from '@/Components/TagPicker'
import { ClientAddress, CompanyContact } from '@/types'
import * as Yup from 'yup'
export {}

export const clientSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string()
    .nullable()
    .transform((value, originalValue) => (originalValue == null ? '' : value))
    .email('Invalid email address'),
  order_type: Yup.string().optional(),
  phone: Yup.string()
    .max(20, 'Phone number must be 10 digits')
    .when('order_type', {
      is: (value: unknown) => value === 'COMMERCIAL',
      then: (schema) => schema.notRequired().nullable(),
      otherwise: (schema) => schema.required('Name is required')
    }),
  notes: Yup.string().max(20, 'Notes must be less than 500 characters'),
  address: Yup.string().max(500, 'Address must be less than 500 characters'),
  source: Yup.string()
    .required('Source is required')
})

export interface Client {
  id: number
  name: string
  email: string | null
  phone: string | null
  contact_type: string | null
  other_phone: string | null
  secondary_email: string | null
  source: string | null
  vip_clients: boolean
  vip_notes?: string
  refer_name?: string
  refer_phone?: string
  refer_email?: string
  referral_id?: number | null
  referrer_client_id?: number | null
  referrer_user_id?: number | null
  user_id?: number | null
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
  company_contact_id?: number | null
  company_contact_ids?: number[]
  company_contact?: CompanyContact
  company_contacts?: CompanyContact[]
  tags?: TagItem[]
  is_contact?: boolean
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
