import * as Yup from 'yup'

export const externalProductSchema = Yup.object({
  external_product: Yup.string().required('External Product is required'),
  width: Yup.number().required('Width is required').min(1, 'Width must be greater than 0'),
  height: Yup.number().required('Height is required').min(1, 'Height must be greater than 0'),
  price: Yup.number().required('Price is required'),
  extras: Yup.string().nullable(),
  notes: Yup.string().nullable()
})
