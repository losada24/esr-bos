import { type CompanyContact, type Client } from '@/types'
import * as Yup from 'yup'

export const orderSchema = Yup.object({
  loss_reason_frontdesk: Yup.string().required('Loss Reason is required')
  // status: Yup.string().required('Status is required'),
  // notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})

export const requestSchema = Yup.object({
  client_name: Yup.string().required('Request Name is required')
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
  loss_reason_frontdesk: string
  status: string
  notes: string
}

export interface Order {
  id: number
  client?: Client
  name: string
  client_id: number
  project_amount: number
  status: string
  notes: string
}

export type OrderFormValues = Order & {
  client_name: string
  phone: string
  source: string
  email: string
  other_phone: string
  secondary_email: string
  vip_clients: boolean
  vip_notes?: string
  // refer_name?: string
  // refer_phone?: string
  // referral_id?: number
  company_contact_id?: number
  company_contact?: CompanyContact[]
}

export const orderFormObj: OrderFormValues = {
  id: 0,
  name: '',
  client_name: '',
  phone: '',
  client_id: 0,
  project_amount: 0,
  status: '',
  source: '',
  notes: '',
  email: '',
  other_phone: '',
  secondary_email: '',
  vip_clients: false,
  vip_notes: '',
  company_contact_id: 0,
  company_contact: []
}

export const loadOrderFormObj = (order: Order): OrderFormValues => {
  return {
    id: order.id,
    client_name: order.client?.name ?? '',
    phone: order.client?.phone ?? '',
    name: order.name,
    source: order.client?.source ?? '',
    client_id: order.client_id,
    project_amount: order.project_amount,
    status: order.status,
    notes: order.notes ?? '',
    email: order.client?.email ?? '',
    other_phone: order.client?.other_phone ?? '',
    secondary_email: order.client?.secondary_email ?? '',
    vip_clients: order.client?.vip_clients ?? false,
    vip_notes: order.client?.vip_notes ?? '',
    company_contact_id: order.client?.company_contact_id ?? 0,
    company_contact: order.client?.company_contact ?? []
  }
}
