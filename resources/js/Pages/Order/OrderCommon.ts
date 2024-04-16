import * as Yup from 'yup'

export const orderStatusUpdateSchema = Yup.object({
  id: Yup.number(),
  status: Yup.string().required('Status is required'),
  notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})

export interface OrderStatusUpdate {
  id: number
  status: string
  notes: string
}

export interface ProductOrderFields {
  part: string
  rawMaterial: string
  qty?: number
  size?: number
  unit?: string
}
