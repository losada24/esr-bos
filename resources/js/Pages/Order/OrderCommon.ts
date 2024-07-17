import { InstallationTeam, type Order } from '@/types'
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

export type OrderFormValues = Omit<Order, 'installation_date' | 'delivery_date' | 'payment_factory_date' | 'contract_signing_date'> & {
  client_name: string
  last_name: string
  phone: string
  email: string
  attachments: any[]
  installation_date: Date | null
  delivery_date: Date | null
  payment_factory_date: Date | null
  contract_signing_date: Date | null
  installation_teams: InstallationTeam[]
}

export const orderFormObj: OrderFormValues = {
  id: 0,
  last_name: '',
  client_name: '',
  phone: '',
  email: '',
  name: '',
  order_number: 0,
  job_address: '',
  city_permits: false,
  association_permits: false,
  equipment_rental: false,
  notes: '',
  client_id: 0,
  entry_date: new Date(),
  installation_date: null,
  additional_travel_costs: 0,
  type_of_work_id: 0,
  type_of_housing_id: 0,
  installation_teams: [],
  supervisor_id: 0,
  travel_cost_id: 0,
  duration_of_work_id: 0,
  method_of_payment: '',
  service: '',
  contract_signing_date: null,
  payment_factory_date: null,
  delivery_date: null,
  owners: [],
  order_products: [],
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

export const loadOrderFormObj = (order: Order): OrderFormValues => {
  return {
    id: order.id,
    last_name: order.client?.last_name ?? '',
    client_name: order.client?.name ?? '',
    phone: order.client?.phone ?? '',
    email: order.client?.email ?? '',
    name: order.name,
    order_number: order.order_number,
    job_address: order.job_address,
    city_permits: order.city_permits,
    association_permits: order.association_permits,
    equipment_rental: order.equipment_rental,
    notes: order.notes,
    client_id: order.client_id,
    entry_date: order.entry_date,
    installation_date: order.installation_date ?? null,
    additional_travel_costs: order.additional_travel_costs,
    type_of_work_id: order.type_of_work_id,
    type_of_housing_id: order.type_of_housing_id,
    installation_teams: order.installation_teams,
    supervisor_id: order.supervisor_id,
    travel_cost_id: order.travel_cost_id,
    duration_of_work_id: order.duration_of_work_id,
    method_of_payment: order.method_of_payment,
    service: order.service,
    contract_signing_date: order.contract_signing_date ?? null,
    payment_factory_date: order.payment_factory_date ?? null,
    delivery_date: order.delivery_date ?? null,
    owners: order.owners,
    order_products: order.order_products,
    attachments: order.attachments ?? []
  }
}
