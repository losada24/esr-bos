import * as Yup from 'yup'
import { isValidFileType, isValidFileSize } from '../RawMaterial/RawMaterialCommon'

export const companySchema = Yup.object({
  id: Yup.number(),
  name: Yup.string().required('Name is required'),
  phone_number: Yup.string().required('Phone is required').max(20, 'Phone number must be 20 digits'),
  email: Yup.string().email().required('Email is required').max(255, 'Email must be less than 255 characters'),
  address: Yup.string().required('Address is required').max(500, 'Address must be less than 500 characters'),
  city: Yup.string().required('City is required').max(100, 'City must be less than 100 characters'),
  state: Yup.string().required('State is required').max(100, 'State must be less than 100 characters'),
  markup: Yup.number().nullable().integer().min(0).max(100),
  promotion: Yup.number().nullable().integer().min(0).max(100),
  zip: Yup.string().required('Zip is required')
    .matches(/^[0-9]+$/, 'Must be only digits')
    .min(5, 'Must be exactly 5 digits')
    .max(5, 'Must be exactly 5 digits'),
  featured_image: Yup.mixed()
    .when('id', {
      is: (id: number) => id === 0,
      then: Yup.mixed().required('Featured image is required'),
      otherwise: Yup.mixed().nullable()
    })
    .test('is-valid-type', 'Not a valid image type', value => isValidFileType(value?.name, 'image'))
    .test('is-valid-size', 'Max allowed size is 500KB', value => isValidFileSize(value?.size ?? 0)),
  allow_credit_payment: Yup.boolean()
})

export interface Company {
  id: number
  name: string
  phone_number: string
  email: string
  city: string
  state: string
  zip: string
  address: string
  featured_image: string
  markup?: number
  promotion?: number
  external_products_markup?: number
  allow_credit_payment: boolean
}
