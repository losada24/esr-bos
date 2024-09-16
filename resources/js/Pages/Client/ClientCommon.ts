import * as Yup from 'yup'

export const clientSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address'),
  phone: Yup.string().max(20, 'Phone number must be 10 digits'),
  address: Yup.string().required('Address is required').max(500, 'Address must be less than 500 characters')
})

export interface Client {
  id: number
  name: string
  email: string
  phone: string
  /* city: string
  state: string
  zip: string */
  address: string
}

export type ClientFormType = Client & {
  appointment_date: Date
}
