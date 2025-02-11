import { type InstallationTeam, type Order } from '@/types'
import * as Yup from 'yup'

export const orderSchema = Yup.object({
  id: Yup.number()
  // status: Yup.string().required('Status is required'),
  // notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})

export const getValueIdNotNull = (formField: any) => {
  let value: string | number = ''
  if ((formField !== undefined && formField !== null) && Object.prototype.hasOwnProperty.call(formField, 'value')) {
    value = formField.value
  } else if (typeof formField === 'number' && formField !== 0) {
    value = formField
  }

  return value
}
export interface OrderStatusUpdate {
  id: number
  status: string
  notes: string
}
export type OrderFormValues = Omit<Order, 'installation_date' | 'delivery_date' | 'payment_factory_date' | 'contract_signing_date' | 'eta_date' | 'installation_end_date' | 'entry_date'> & {
  client_name: string
  last_name: string
  phone: string
  email: string
  vip_clients: boolean
  vip_notes: string
  attachments: any[]
  installation_date: Date | null
  delivery_date: Date | null
  payment_factory_date: Date | null
  contract_signing_date: Date | null
  installation_teams: InstallationTeam[]
  eta_date: Date | null
  entry_date: Date | null
  installation_end_date: Date | null
}

export const orderFormObj: OrderFormValues = {
  id: 0,
  last_name: '',
  client_name: '',
  phone: '',
  email: '',
  vip_clients: false,
  vip_notes: '',
  name: '',
  order_number: 0,
  job_address: '',
  job_city: '',
  job_state: '',
  job_zip: '',
  city_permits: false,
  city: '',
  association_permits: false,
  equipment_rental: false,
  notes: '',
  work_team_notes: '',
  client_id: 0,
  entry_date: null,
  installation_date: null,
  additional_travel_costs: 0,
  type_of_work_id: 0,
  type_of_housing_id: 0,
  installation_teams: [],
  supervisor_id: 0,
  travel_cost_id: 0,
  duration_of_work_id: 0,
  method_of_payment: '',
  type_of_financing: '',
  cost_delivery: 0,
  cost_city_fee: 0,
  project_amount: 0,
  service: '',
  contract_signing_date: null,
  payment_factory_date: null,
  eta_date: null,
  installation_end_date: null,
  delivery_date: null,
  owners: [],
  order_products: [],
  attachments: [],
  frame_color: '',
  initial_payment_percentage: 0,
  payment_definition: false,
  hide_on_weekends: false
}

export const loadFormObj = (order: Order): OrderFormValues => {
  return {
    id: order.id,
    last_name: order.client?.last_name ?? '',
    client_name: order.client?.name ?? '',
    phone: order.client?.phone ?? '',
    email: order.client?.email ?? '',
    vip_clients: order.client?.vip_clients ?? false,
    vip_notes: order.client?.vip_notes ?? '',
    name: order.name,
    order_number: order.order_number,
    job_address: order.job_address,
    job_state: order.job_state,
    job_zip: order.job_zip,
    city_permits: !!order.city_permits,
    association_permits: order.association_permits,
    equipment_rental: order.equipment_rental,
    notes: order.notes ?? '',
    work_team_notes: order.work_team_notes ?? '',
    client_id: order.client_id,
    entry_date: order.entry_date ?? null,
    installation_date: order.installation_date ?? null,
    additional_travel_costs: order.additional_travel_costs,
    type_of_work_id: order.type_of_work_id,
    type_of_housing_id: order.type_of_housing_id,
    installation_teams: order.installation_teams,
    supervisor_id: order.supervisor_id,
    travel_cost_id: order.travel_cost_id,
    cost_delivery: order.cost_delivery,
    cost_city_fee: order.cost_city_fee,
    project_amount: order.project_amount,
    city: order.city,
    duration_of_work_id: order.duration_of_work_id,
    method_of_payment: order.method_of_payment,
    type_of_financing: order.type_of_financing,
    service: order.service,
    contract_signing_date: order.contract_signing_date ?? null,
    payment_factory_date: order.payment_factory_date ?? null,
    eta_date: order.eta_date ?? null,
    delivery_date: order.delivery_date ?? null,
    owners: order.owners,
    order_products: order.order_products,
    attachments: [],
    installation_end_date: order.installation_end_date ?? null,
    frame_color: order.frame_color,
    status: order.status,
    payment_definition: order.payment_definition,
    initial_payment_percentage: order.initial_payment_percentage,
    hide_on_weekends: order.hide_on_weekends ?? false,
    do_not_send_email: order.do_not_send_email ?? false
  }
}
