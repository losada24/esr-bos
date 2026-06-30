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
  order_type: Yup.string().required('Order Type is required'),
  language: Yup.string().required('Language is required')
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
  language: Yup.string().required('Language is required'),
  change_order_enabled: Yup.boolean().optional(),
  change_order_amount: Yup.number()
    .nullable()
    .when('change_order_enabled', {
      is: true,
      then: (schema) => schema.required('Change Order Price is required')
    }),
  change_order_note: Yup.string().nullable().max(2000, 'Change Order Note must be less than 2000 characters'),
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
    }),
  associate_source_id_3: Yup.number()
    .nullable()
    .when('associate_company_contact_id_3', {
      is: (value: number | null) => Boolean(value),
      then: (schema) => schema.required('Source is required')
    }),
  associate_source_id_4: Yup.number()
    .nullable()
    .when('associate_company_contact_id_4', {
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
  paid_amount?: number
  balance?: number
  credit?: number
  due_date?: string | null
  status: string
  paid_at?: string | null
  paid_by?: { id: number, name: string } | null
  position?: number | null
  movements?: PaymentInstallmentMovement[]
}

export interface PaymentInstallmentMovement {
  id: number
  amount: number
  paid_at?: string | null
  method?: string | null
  note?: string | null
  paid_by?: { id: number, name: string } | null
  created_at?: string | null
  updated_at?: string | null
}

export interface PaymentSchedule {
  id: number
  schedule_type: string
  total_amount: number
  paid_amount?: number
  remaining_amount?: number
  credit_amount?: number
  installments?: PaymentInstallment[]
}

export interface OrderPayment {
  id: number
  order_id: number
  type: string
  amount: number
  note?: string | null
  status?: string | null
  paid_at?: string | null
  paid_by?: { id: number, name: string } | null
}

export interface OrderFinancialEvent {
  id: number
  event_type: string
  summary: string
  details?: Record<string, any> | null
  created_at?: string | null
  user?: { id?: number, name?: string } | null
}

export interface Order {
  id: number
  order_number?: string
  client?: Client
  name: string
  client_id: number
  associate_client_id_1: number | null
  associate_client_id_2: number | null
  associate_client_id_3: number | null
  associate_client_id_4: number | null
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
  product_line?: string
  service?: string | null
  esr_design?: boolean
  esr_express?: boolean
  esr_reylos_glass?: boolean
  esr_service?: boolean
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
  client_email_selection?: string | null
  client_email_override?: string | null
  do_not_send_email?: boolean
  client_email_options?: Array<{
    value: string
    label: string
    is_primary?: boolean
  }>
  loss_reason_frontdesk?: string | null
  attachments?: Array<Attachment | File>
  method_of_payment?: string | null
  name_check?: boolean
  address_check?: boolean
  amount_check?: boolean
  email_check?: boolean
  city_permits?: boolean
  association_permits?: boolean
  payment_schedule?: PaymentSchedule | null
  change_order_payment?: OrderPayment | null
  financial_events?: OrderFinancialEvent[]
  has_contract_signed?: boolean
  invoice_number?: string | null
  order_company_contacts?: any[]
}

export type OrderFormValues = Order & {
  client_name: string
  phone: string
  source: string
  refer_name?: string
  refer_phone?: string
  refer_email?: string
  referral_id?: number | null
  referrer_client_id?: number | null
  referrer_user_id?: number | null
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
  associate_company_contact_id_3: number | null
  associate_company_contact_id_4: number | null
  associate_source_id_1: number | null
  associate_source_id_2: number | null
  associate_source_id_3: number | null
  associate_source_id_4: number | null
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
  change_order_enabled?: boolean
  change_order_amount?: number | null
  change_order_note?: string
  payment_schedule_type?: string
  custom_schedule?: Array<{ label: string, amount: string }>
}

export const orderFormObj: OrderFormValues = {
  id: 0,
  order_number: '',
  name: '',
  invoice_number: '',
  client_name: '',
  phone: '',
  client_id: 0,
  associate_client_id_1: null,
  associate_client_id_2: null,
  associate_client_id_3: null,
  associate_client_id_4: null,
  project_amount: 0,
  status: '',
  source: '',
  refer_name: '',
  refer_phone: '',
  refer_email: '',
  referral_id: null,
  referrer_client_id: null,
  referrer_user_id: null,
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
  associate_company_contact_id_3: null,
  associate_company_contact_id_4: null,
  associate_source_id_1: null,
  associate_source_id_2: null,
  associate_source_id_3: null,
  associate_source_id_4: null,
  company_contact: [],
  job_address: '',
  job_city: '',
  job_state: '',
  job_zip: '',
  city: '',
  description: '',
  order_type: '',
  product_line: '',
  service: '',
  esr_design: false,
  esr_express: false,
  esr_reylos_glass: false,
  esr_service: false,
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
  change_order_enabled: false,
  change_order_amount: null,
  change_order_note: '',
  schedule_appointment: null,
  schedule_appointment_iso: null,
  owner_ids: [],
  down_payment: null,
  type_of_financing: null,
  method_of_payment: '',
  payment_schedule: null,
  payment_schedule_type: '',
  custom_schedule: Array.from({ length: 6 }, () => ({ label: '', amount: '' })),
  contact_email: '',
  client_email_selection: '__NONE__',
  loss_reason_frontdesk: '',
  name_check: false,
  address_check: false,
  amount_check: false,
  email_check: false,
  has_contract_signed: false,
  financial_events: []
}

export const loadOrderFormObj = (order: Order): OrderFormValues => {
  const scheduleType = order.payment_schedule?.schedule_type ?? ''
  const customScheduleFromOrder = scheduleType === 'CUSTOMIZED'
    ? (order.payment_schedule?.installments ?? [])
      .map((item) => ({
        label: String(item.label ?? '').trim(),
        amount: item.amount != null ? String(item.amount) : ''
      }))
      .filter((item) => item.label !== '' || item.amount !== '')
    : []
  const customSchedule = [
    ...customScheduleFromOrder.slice(0, 6),
    ...Array.from({ length: Math.max(0, 6 - customScheduleFromOrder.length) }, () => ({ label: '', amount: '' }))
  ]

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
  const [primaryCompany, assocCompany1, assocCompany2, assocCompany3, assocCompany4] = sortedCompanyContacts
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
    invoice_number: order.invoice_number ?? '',
    source: order.client?.source ?? '',
    client_id: getClientId(primaryCompany) ?? order.client_id,
    associate_client_id_1: getClientId(assocCompany1) ?? order.associate_client_id_1 ?? null,
    associate_client_id_2: getClientId(assocCompany2) ?? order.associate_client_id_2 ?? null,
    associate_client_id_3: getClientId(assocCompany3) ?? order.associate_client_id_3 ?? null,
    associate_client_id_4: getClientId(assocCompany4) ?? order.associate_client_id_4 ?? null,
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
    associate_company_contact_id_3: getCompanyId(assocCompany3),
    associate_company_contact_id_4: getCompanyId(assocCompany4),
    associate_source_id_1: getSourceId(assocCompany1),
    associate_source_id_2: getSourceId(assocCompany2),
    associate_source_id_3: getSourceId(assocCompany3),
    associate_source_id_4: getSourceId(assocCompany4),
    company_contact: companyContacts,
    job_address: order.job_address ?? '',
    job_city: order.job_city ?? '',
    job_state: order.job_state ?? '',
    job_zip: order.job_zip ?? '',
    city: order.city ?? '',
    description: order.description ?? '',
    order_type: order.order_type ?? '',
    product_line: order.product_line ?? '',
    service: order.service ?? '',
    esr_design: order.esr_design ?? false,
    esr_express: order.esr_express ?? false,
    esr_reylos_glass: order.esr_reylos_glass ?? false,
    esr_service: order.esr_service ?? false,
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
    change_order_enabled: Boolean(order.change_order_payment),
    change_order_amount: order.change_order_payment?.amount != null ? Number(order.change_order_payment.amount) : null,
    change_order_note: order.change_order_payment?.note ?? '',
    schedule_appointment: order.schedule_appointment ?? null,
    schedule_appointment_iso: order.schedule_appointment_iso ?? null,
    owner_ids: Array.isArray(order.owner_ids) && order.owner_ids.length > 0
      ? order.owner_ids
      : (Array.isArray(order.owners) ? order.owners.map(owner => owner?.id).filter((id): id is number => typeof id === 'number') : []),
    down_payment: order.down_payment ?? null,
    type_of_financing: order.type_of_financing ?? null,
    method_of_payment: order.method_of_payment ?? '',
    payment_schedule: order.payment_schedule ?? null,
    payment_schedule_type: scheduleType,
    custom_schedule: customSchedule,
    contact_email: order.contact_email ?? order.client?.email ?? '',
    client_email_selection: order.client_email_selection ?? '__NONE__',
    loss_reason_frontdesk: order.loss_reason_frontdesk ?? '',
    name_check: order.name_check ?? false,
    address_check: order.address_check ?? false,
    amount_check: order.amount_check ?? false,
    email_check: order.email_check ?? false,
    has_contract_signed: order.has_contract_signed ?? false,
    financial_events: order.financial_events ?? []
  }
}
