import { type Order } from '@/types'
import * as Yup from 'yup'

export const orderSchema = Yup.object({
  id: Yup.number(),
  //status: Yup.string().required('Status is required'),
  //notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})

export const orderProductSchema = Yup.object({
  id: Yup.number(),
  type_of_product_id: Yup.number().moreThan(0, 'Type of product is required'),
  product_category_id: Yup.number().moreThan(0, 'Product category is required'),
  product_config_id: Yup.number().moreThan(0, 'Product config is required'),
  width: Yup.number().min(1),
  height: Yup.number().min(1),
  qty: Yup.number().min(1)
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

export type OrderFormValues = Order & {
  client_name: string
  last_name: string
  phone: string
  email: string
  attachments: any[]
}

export const orderFormObj: OrderFormValues = {
  id: 0,
  name: '',
  order_number: 0,
  job_address: '',
  city_permits: false,
  association_permits: false,
  equipment_rental: false,
  notes: '',
  client_id: 0,
  entry_date: new Date(),
  installation_date: new Date(),
  additional_travel_costs: 0,
  type_of_work_id: 0,
  type_of_housing_id: 0,
  installation_teams: [],
  supervisor_id: 0,
  travel_cost_id: 0,
  duration_of_work_id: 0,
  method_of_payment: '',
  service: '',
  contract_signing_date: new Date(),
  payment_factory_date: new Date(),
  delivery_date: new Date(),
  owners: [],
  orderProducts: [],
  client_name: '',
  last_name: '',
  phone: '',
  email: '',
  attachments: []
}

export interface OrderProductExtraWorksFormValues {
  extra_work_id: number
  extra_work_name: string
  extra_work_unit: string
  number_of_sides: number
  price: number
  checked: boolean
}
