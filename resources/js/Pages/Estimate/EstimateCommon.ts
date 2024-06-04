import * as Yup from 'yup'
import { PAYMENT_METHODS, ADDRESS_REQUIRED_AFTER_AMOUNT } from '@/Utils/constants'
import { isValidFileType, isValidFileSize } from '../RawMaterial/RawMaterialCommon'

export const estimateSchema = Yup.object({
  id: Yup.number(),
  name: Yup.string().required('Name is required').max(255, 'Max 255 characters'),
  project_name: Yup.string().max(255, 'Max 255 characters'),
  external_purchase_id: Yup.string().max(255, 'Max 255 characters'),
  client_id: Yup.number().required('Client is required'),
  frame_color: Yup.string().required('Frame color is required'),
  glass_color: Yup.string().required('Glass color is required'),
  glass_type: Yup.string().required('Glass type is required'),
  markup: Yup.number().required('Markup is required'),
  installation: Yup.number(),
  permit: Yup.number(),
  other: Yup.number(),
  rg_other_price: Yup.number(),
  order_promotion: Yup.number(),
  subdealer_other: Yup.number(),
  notes: Yup.string().nullable()
})

export const paymentInfoSchema = Yup.object({
  id: Yup.number(),
  method: Yup.string()
    .equals([PAYMENT_METHODS.CASH, PAYMENT_METHODS.CHECK, PAYMENT_METHODS.BANK_TRANSFER, PAYMENT_METHODS.CREDIT], 'Payment Method not allowed')
    .required('Payment Method Required'),
  terms_and_conditions_agreed: Yup.boolean().equals([true], 'Terms and Conditions must be accepted'),
  amount: Yup.number(),
  street_address: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().required('Street address is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
  city: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().required('City is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
  state: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().required('State is required').max(100, 'Max 100 characters'),
      otherwise: Yup.string().nullable().max(100, 'Max 100 characters')
    }),
  zip_code: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string()
        .required('Zip Code is required')
        .matches(/^[0-9]+$/, 'Must be only digits')
        .min(5, 'Must be exactly 5 digits')
        .max(5, 'Must be exactly 5 digits'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
  notes: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().nullable().max(500, 'Max 500 digits'),
      otherwise: Yup.string().nullable().max(500, 'Max 500 characters')
    })
})

export const estimateCommentsSchema = Yup.object({
  id: Yup.number(),
  attachment: Yup.mixed()
    .test('is-valid-type', 'Not a valid image type', value => isValidFileType(value?.name, 'image'))
    .test('is-valid-size', 'Max allowed size is 500KB', value => isValidFileSize(value?.size ?? 0)),
  comments: Yup.string().max(1000, 'Notes must be less than 1000 characters')
})

export interface EstimateCommentsUpdate {
  id: number
  attachment: string
  comments: string
}
