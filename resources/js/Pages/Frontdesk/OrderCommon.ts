import { type CompanyContact, type Client, type User } from '@/types'
import { type Attachment } from '@/types/interfaces/order'
import { type SaleForm } from '@/types/interfaces/saleForm'
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
  client_name: Yup.string().required('Request Name is required'),
  name_check: Yup.boolean().optional(),
  address_check: Yup.boolean().optional(),
  amount_check: Yup.boolean().optional(),
  email_check: Yup.boolean().optional()
  // status: Yup.string().required('Status is required'),
  // notes: Yup.string().required().max(1000, 'Notes must be less than 255 characters')
})

export const orderQualifiedSchema = Yup.object({
  order_type: Yup.string().required('Order Type is required'),
  company_source_id: Yup.number()
    .nullable()
    .when('order_type', {
      is: (value: string) => value === 'COMMERCIAL',
      then: (schema) => schema.required('Source is required')
    }),
  associate_source_id_1: Yup.number()
    .nullable()
    .when('associate_company_contact_id_1', {
      is: (value: number | null) => Boolean(value),
      then: (schema) => schema.required('Source is required')
    }),
  associate_source_id_2: Yup.number()
    .nullable()
    .when('associate_company_contact_id_2', {
      is: (value: number | null) => Boolean(value),
      then: (schema) => schema.required('Source is required')
    })
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

export interface PaymentInstallment {
  id: number
  label: string
  percentage: number
  amount: number
  due_date?: string | null
  status: string
  paid_at?: string | null
  paid_by?: { id: number, name: string } | null
  position?: number | null
}

export interface PaymentSchedule {
  id: number
  schedule_type: string
  total_amount: number
  installments?: PaymentInstallment[]
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
  owners?: User[]
  owner_ids?: number[]
  is_supply: boolean
  sale_form?: SaleForm
  schedule_appointment?: Date | null
  schedule_appointment_iso?: string | null
  down_payment?: number | null
  type_of_financing?: string | null
  contact_email?: string | null
  loss_reason_frontdesk?: string | null
  attachments?: Attachment[]
  method_of_payment?: string | null
  name_check?: boolean
  address_check?: boolean
  amount_check?: boolean
  email_check?: boolean
  payment_schedule?: PaymentSchedule | null
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
  company_source_id: number | null
  associate_company_contact_id_1: number | null
  associate_company_contact_id_2: number | null
  associate_source_id_1: number | null
  associate_source_id_2: number | null
  company_contact?: CompanyContact[]
  sale: boolean
  installation: boolean
  permit: boolean
  replacement: boolean
  new_construction: boolean
  financing: boolean
  screen: boolean
  design: boolean
  mountin: boolean
  bar: boolean
  shutter_hole: boolean
  floor_cutting: boolean
  interior_finish: boolean
  hoa: boolean
  floor: string
  frame_color: string
  glass_color: string
  glass_type: string
  glass_coating: string
  language: string
  door_quantity: number
  window_quantity: number
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
  company_source_id: null,
  associate_company_contact_id_1: null,
  associate_company_contact_id_2: null,
  associate_source_id_1: null,
  associate_source_id_2: null,
  company_contact: [],
  job_address: '',
  job_city: '',
  job_state: '',
  job_zip: '',
  city: '',
  description: '',
  order_type: '',
  bid_due_date: null,
  user: null as unknown as User,
  is_supply: false,
  sale: false,
  installation: false,
  permit: false,
  replacement: false,
  new_construction: false,
  financing: false,
  screen: false,
  design: false,
  mountin: false,
  bar: false,
  shutter_hole: false,
  floor_cutting: false,
  interior_finish: false,
  hoa: false,
  floor: '',
  frame_color: '',
  glass_color: '',
  glass_type: '',
  glass_coating: '',
  language: '',
  door_quantity: 0,
  window_quantity: 0,
  schedule_appointment: null,
  schedule_appointment_iso: null,
  owner_ids: [],
  down_payment: null,
  type_of_financing: null,
  contact_email: '',
  loss_reason_frontdesk: '',
  name_check: false,
  address_check: false,
  amount_check: false,
  email_check: false
}

export const loadOrderFormObj = (order: Order): OrderFormValues => {
  const rawCompanyContact = order.client?.company_contact as CompanyContact[] | CompanyContact | undefined
  const companyContacts = rawCompanyContact
    ? (Array.isArray(rawCompanyContact) ? rawCompanyContact : [rawCompanyContact])
    : []
  const orderCompanyContacts = Array.isArray((order as any).order_company_contacts)
    ? (order as any).order_company_contacts
    : Array.isArray((order as any).orderCompanyContacts)
      ? (order as any).orderCompanyContacts
      : []
  const sortedCompanyContacts = [...orderCompanyContacts].sort((a: any, b: any) => {
    const selectedDiff = Number(Boolean(b?.is_selected)) - Number(Boolean(a?.is_selected))
    if (selectedDiff !== 0) return selectedDiff
    return Number(a?.id ?? 0) - Number(b?.id ?? 0)
  })
  const [primaryCompany, assocCompany1, assocCompany2] = sortedCompanyContacts
  const getCompanyId = (item?: any) =>
    item?.company_contact_id ?? item?.company_contact?.id ?? item?.companyContact?.id ?? null
  const getClientId = (item?: any) =>
    item?.client_id ?? item?.client?.id ?? null
  const getSourceId = (item?: any) =>
    item?.source_id ?? item?.source?.id ?? null

  return {
    id: order.id,
    client_name: order.client?.name ?? '',
    phone: order.client?.phone ?? '',
    name: order.name,
    source: order.client?.source ?? '',
    client_id: getClientId(primaryCompany) ?? order.client_id,
    associate_client_id_1: getClientId(assocCompany1) ?? order.associate_client_id_1 ?? null,
    associate_client_id_2: getClientId(assocCompany2) ?? order.associate_client_id_2 ?? null,
    project_amount: order.project_amount,
    status: order.status,
    notes: order.notes ?? '',
    email: order.client?.email ?? '',
    other_phone: order.client?.other_phone ?? '',
    secondary_email: order.client?.secondary_email ?? '',
    vip_clients: order.client?.vip_clients ?? false,
    vip_notes: order.client?.vip_notes ?? '',
    company_contact_id: getCompanyId(primaryCompany) ?? order.client?.company_contact_id ?? 0,
    company_source_id: getSourceId(primaryCompany),
    associate_company_contact_id_1: getCompanyId(assocCompany1),
    associate_company_contact_id_2: getCompanyId(assocCompany2),
    associate_source_id_1: getSourceId(assocCompany1),
    associate_source_id_2: getSourceId(assocCompany2),
    company_contact: companyContacts,
    job_address: order.job_address ?? '',
    job_city: order.job_city ?? '',
    job_state: order.job_state ?? '',
    job_zip: order.job_zip ?? '',
    city: order.city ?? '',
    description: order.description ?? '',
    order_type: order.order_type ?? '',
    bid_due_date: order.bid_due_date ?? null,
    user: order.user,
    is_supply: order.is_supply ?? false,
    sale: order.sale_form ? order.sale_form.sale : false,
    installation: order.sale_form ? order.sale_form.installation : false,
    permit: order.sale_form ? order.sale_form.permit : false,
    replacement: order.sale_form ? order.sale_form.replacement : false,
    new_construction: order.sale_form ? order.sale_form.new_construction : false,
    financing: order.sale_form ? order.sale_form.financing : false,
    screen: order.sale_form ? order.sale_form.screen : false,
    design: order.sale_form ? order.sale_form.design : false,
    mountin: order.sale_form ? order.sale_form.mountin : false,
    bar: order.sale_form ? order.sale_form.bar : false,
    shutter_hole: order.sale_form ? order.sale_form.shutter_hole : false,
    floor_cutting: order.sale_form ? order.sale_form.floor_cutting : false,
    interior_finish: order.sale_form ? order.sale_form.interior_finish : false,
    hoa: order.sale_form ? order.sale_form.hoa : false,
    floor: order.sale_form ? order.sale_form.floor : '',
    frame_color: order.sale_form ? order.sale_form.frame_color : '',
    glass_color: order.sale_form ? order.sale_form.glass_color : '',
    glass_type: order.sale_form ? order.sale_form.glass_type : '',
    glass_coating: order.sale_form ? order.sale_form.glass_coating : '',
    language: order.sale_form ? (order.sale_form.language ?? '') : '',
    door_quantity: order.sale_form ? order.sale_form.door_quantity : 0,
    window_quantity: order.sale_form ? order.sale_form.window_quantity : 0,
    schedule_appointment: order.schedule_appointment ?? null,
    schedule_appointment_iso: order.schedule_appointment_iso ?? null,
    owner_ids: Array.isArray(order.owner_ids) && order.owner_ids.length > 0
      ? order.owner_ids
      : (Array.isArray(order.owners) ? order.owners.map(owner => owner?.id).filter((id): id is number => typeof id === 'number') : []),
    down_payment: order.down_payment ?? null,
    type_of_financing: order.type_of_financing ?? null,
    contact_email: order.contact_email ?? order.client?.email ?? '',
    loss_reason_frontdesk: order.loss_reason_frontdesk ?? '',
    name_check: order.name_check ?? false,
    address_check: order.address_check ?? false,
    amount_check: order.amount_check ?? false,
    email_check: order.email_check ?? false
  }
}
