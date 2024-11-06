import * as Yup from 'yup'
export {}

export const clientSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address'),
  phone: Yup.string().max(20, 'Phone number must be 10 digits'),
  notes: Yup.string().max(20, 'Notes must be less than 500 characters'),
  address: Yup.string().max(500, 'Address must be less than 500 characters')
})

export interface Client {
  id: number
  name: string
  email: string
  phone: string
}

export type ClientFormType = Client & {
  address: string
  appointment_date: Date | null
  notes: string
  confirmed: boolean
}
