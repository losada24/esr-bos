import * as Yup from 'yup'

export const clientSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  phone: Yup.string().required('Phone is required').max(20, 'Phone number must be 10 digits'),
  address: Yup.string().required('Address is required').max(500, 'Address must be less than 500 characters'),
  city: Yup.string().required('City is required').max(100, 'City must be less than 100 characters'),
  state: Yup.string().required('State is required').max(100, 'State must be less than 100 characters'),
  zip: Yup.string().required('Zip is required')
    .matches(/^[0-9]+$/, 'Must be only digits')
    .min(5, 'Must be exactly 5 digits')
    .max(5, 'Must be exactly 5 digits')
})

export interface Client {
  id: number
  name: string
  email: string
  phone: string
  city: string
  state: string
  zip: string
  address: string
}
