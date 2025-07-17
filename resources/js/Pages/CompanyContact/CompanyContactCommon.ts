import * as Yup from 'yup'
export {}

export const companyContactSchema = Yup.object({
  name: Yup.string().required('Name is required')
})

export const clientSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  phone: Yup.string().required('Phone is required').max(20, 'Phone number must be 20 characters or less'),
  email: Yup.string().email('Invalid email address').nullable(),
  source: Yup.string().required('Source is required')
})

export interface CompanyContact {
  id: number
  name: string
  email: string
  phone: string
  website: string
  billing_street: string
  billing_state: string
  billing_city: string
  billing_code: string
  bid_due_date: Date | null
}

/* export type ClientFormType = CompanyContact & {
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
} */
