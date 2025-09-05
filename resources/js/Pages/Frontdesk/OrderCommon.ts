import { type CompanyContact, type Client, User } from '@/types'
import * as Yup from 'yup'

export const orderSchema = Yup.object({
  loss_reason_frontdesk: Yup.string().required('Loss Reason is required')
  // status: Yup.string().required('Status is required'),
  // notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})
export const orderQuantifiedSchema = Yup.object({
  order_type: Yup.string().required('Order Type is required')
  // status: Yup.string().required('Status is required'),
  // notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})

export const requestSchema = Yup.object({
  client_name: Yup.string().required('Request Name is required')
  // status: Yup.string().required('Status is required'),
  // notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})

export const orderQualifiedSchema = Yup.object({
  // name: Yup.string().required('Request Name is required'),
  // status: Yup.string().required('Status is required'),
  // client_id: Yup.number().required('Client is required'),
  // order_type: Yup.string().required('Order Type is required')

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
  associate_client_id_1: number | null
  associate_client_id_2: number | null
  project_amount: number
  status: string
  notes: string
  job_address?: string
  job_city?: string
  job_state?: string
  job_zip?: string
  city?: string
  description?: string
  order_type?: string
  bid_due_date?: Date | null
  user: User
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
  associate_company_contact_id_1: number | null
  associate_company_contact_id_2: number | null
  company_contact?: CompanyContact[]
}

export const orderFormObj: OrderFormValues = {
  id: 0,
  name: '',
  client_name: '',
  phone: '',
  client_id: 0,
  associate_client_id_1: null,
  associate_client_id_2: null,
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
  associate_company_contact_id_1: null,
  associate_company_contact_id_2: null,
  company_contact: [],
  job_address: '',
  job_city: '',
  job_state: '',
  job_zip: '',
  city: '',
  description: '',
  order_type: '',
  bid_due_date: null,
  user: null as unknown as User
}

export const loadOrderFormObj = (order: Order): OrderFormValues => {
  return {
    id: order.id,
    client_name: order.client?.name ?? '',
    phone: order.client?.phone ?? '',
    name: order.name,
    source: order.client?.source ?? '',
    client_id: order.client_id,
    associate_client_id_1: order.associate_client_id_1 ?? null,
    associate_client_id_2: order.associate_client_id_2 ?? null,
    project_amount: order.project_amount,
    status: order.status,
    notes: order.notes ?? '',
    email: order.client?.email ?? '',
    other_phone: order.client?.other_phone ?? '',
    secondary_email: order.client?.secondary_email ?? '',
    vip_clients: order.client?.vip_clients ?? false,
    vip_notes: order.client?.vip_notes ?? '',
    company_contact_id: order.client?.company_contact_id ?? 0,
    associate_company_contact_id_1: order.client?.company_contact_id ?? null,
    associate_company_contact_id_2: order.client?.company_contact_id ?? null,
    company_contact: order.client?.company_contact ?? [],
    job_address: order.job_address ?? '',
    job_city: order.job_city ?? '',
    job_state: order.job_state ?? '',
    job_zip: order.job_zip ?? '',
    city: order.city ?? '',
    description: order.description ?? '',
    order_type: order.order_type ?? '',
    bid_due_date: order.bid_due_date ?? null,
    user: order.user
  }
}
