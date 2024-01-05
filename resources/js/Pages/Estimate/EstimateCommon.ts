import * as Yup from 'yup'
import { PAYMENT_METHODS, ADDRESS_REQUIRED_AFTER_AMOUNT } from '@/Utils/constants'

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
  notes: Yup.string().max(255, 'Max 255 characters')
})

export const paymentInfoSchema = Yup.object({
  id: Yup.number(),
  method: Yup.string()
    .equals([PAYMENT_METHODS.CASH, PAYMENT_METHODS.CHECK, PAYMENT_METHODS.BANK_TRANSFER, PAYMENT_METHODS.CREDIT], 'Payment Method not allowed')
    .required('Payment Method Required'),
  terms_and_conditions_agreed: Yup.boolean().equals([true], 'Terms and Conditions must be accepted'),
  amount: Yup.number(),
  first_name: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().required('First Name is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
  last_name: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().required('Last Name is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
  email: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().email().required('Email is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
  phone_number: Yup.string()
    .when(['method', 'amount'], {
      is: (method: string, amount: number) => PAYMENT_METHODS.CREDIT === method || amount >= ADDRESS_REQUIRED_AFTER_AMOUNT,
      then: Yup.string().email().required('Phone is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
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
