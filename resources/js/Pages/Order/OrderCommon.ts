import { ExtraWorks, type InstallationTeam, type Order } from '@/types'
import type { Attachment, PaymentSchedule } from '@/types/interfaces/order'
import { type OrderProduct, type OrderProductsExtraWorks } from '@/types/interfaces/order'
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

export const orderProductSchema = Yup.object({
  id: Yup.number(),
  type_of_product_id: Yup.number().moreThan(0, 'Type of product is required'),
  product_category_id: Yup.number().moreThan(0, 'Product category is required'),
  product_config_id: Yup.number().moreThan(0, 'Product config is required'),
  width: Yup.number().nullable(),
  height: Yup.number().nullable(),
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

interface DropdownOption {
  label: string
  value: string
}

export type OrderFormValues = Omit<Order, 'installation_date' | 'delivery_date' | 'payment_factory_date' | 'contract_signing_date' | 'eta_date' | 'installation_end_date' | 'entry_date' | 'frame_color' | 'attachments'> & {
  client_name: string
  last_name: string
  phone: string
  email: string
  vip_clients: boolean
  vip_notes: string
  attachments: Array<Attachment | File>
  installation_date: Date | null
  delivery_date: Date | null
  payment_factory_date: Date | null
  contract_signing_date: Date | null
  installation_teams: InstallationTeam[]
  eta_date: Date | null
  entry_date: Date | null
  installation_end_date: Date | null
  frame_color: string[] | DropdownOption[]
  contact_type: string
  order_type?: string
  product_line?: string
  is_supply?: boolean
  has_contract_signed?: boolean
  client_company_name?: string
  client_email_selection?: string
  down_payment?: number | null
  payment_schedule?: PaymentSchedule | null
  payment_schedule_type?: string
  custom_schedule?: Array<{ label: string, amount: string }>
  change_order_enabled?: boolean
  change_order_amount?: number | null
  change_order_note?: string
  attachment_role_targets?: Record<string, number[]>
  parent_order_id?: number | null
}

export const orderFormObj: OrderFormValues = {
  id: 0,
  parent_order_id: null,
  last_name: '',
  client_name: '',
  phone: '',
  email: '',
  contact_type: 'RESIDENTIAL CONTACT',
  vip_clients: false,
  vip_notes: '',
  name: '',
  order_number: 0,
  invoice_number: '',
  job_address: '',
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
  frame_color: [],
  initial_payment_percentage: 0,
  payment_definition: false,
  hide_on_weekends: false,
  is_send_email: false,
  is_new_travel_cost: false,
  new_travel_cost: 0,
  material_received_date: null,
  area: 0,
  order_weight: 0,
  order_type: '',
  product_line: '',
  is_supply: false,
  has_contract_signed: false,
  down_payment: null,
  payment_schedule: null,
  payment_schedule_type: '',
  custom_schedule: [],
  change_order_enabled: false,
  change_order_amount: null,
  change_order_note: '',
  attachment_role_targets: {},
  client_email_selection: '__NONE__'
}

export interface OrderProductExtraWorksFormValues {
  extra_work_id: number
  extra_work_name: string
  extra_work_unit: string
  amount: number
  price: number
  checked: boolean
}

export const loadOrderFormObj = (order: Order): OrderFormValues => {
  const scheduleType = order.payment_schedule?.schedule_type ?? ''
  const clientEmailSelection = order.do_not_send_email
    ? '__NONE__'
    : (order.client_email_selection ?? order.client_email_override ?? '__NONE__')
  const attachmentRoleTargetsByRole = order.attachment_role_targets_by_role ?? {}
  const getAttachmentRoleTargetIds = (role: string): number[] => {
    const ids = attachmentRoleTargetsByRole[role]
    return Array.isArray(ids) ? ids : []
  }
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

  return {
    id: order.id,
    parent_order_id: order.parent_order_id ?? null,
    client: order.client,
    last_name: order.client?.last_name ?? '',
    client_name: order.client?.name ?? '',
    phone: order.client?.phone ?? '',
    email: order.client?.email ?? '',
    contact_type: order.contact_type ?? 'RESIDENTIAL CONTACT',
    vip_clients: order.client?.vip_clients ?? false,
    vip_notes: order.client?.vip_notes ?? '',
    name: order.name,
    order_type: order.order_type ?? '',
    product_line: order.product_line ?? '',
    is_supply: order.is_supply ?? false,
    has_contract_signed: order.has_contract_signed ?? false,
    client_company_name: order.client?.company_contact?.name ?? '',
    order_number: order.order_number,
    invoice_number: order.invoice_number ?? '',
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
    down_payment: order.down_payment ?? null,
    payment_schedule: order.payment_schedule ?? null,
    payment_schedule_type: scheduleType,
    custom_schedule: customSchedule,
    change_order_enabled: Boolean(order.change_order_payment),
    change_order_amount: order.change_order_payment?.amount != null ? Number(order.change_order_payment.amount) : null,
    change_order_note: order.change_order_payment?.note ?? '',
    attachment_role_targets: {
      ADMIN: getAttachmentRoleTargetIds('ADMIN'),
      OWNER: getAttachmentRoleTargetIds('OWNER'),
      SUPERVISOR: getAttachmentRoleTargetIds('SUPERVISOR'),
      ACCOUNTING: getAttachmentRoleTargetIds('ACCOUNTING')
    },
    service: order.service,
    contract_signing_date: order.contract_signing_date ?? null,
    payment_factory_date: order.payment_factory_date ?? null,
    eta_date: order.eta_date ?? null,
    delivery_date: order.delivery_date ?? null,
    owners: order.owners,
    order_products: order.order_products,
    attachments: [],
    installation_end_date: order.installation_end_date ?? null,
    frame_color: order.order_colors?.map((color) => ({
      label: color.name,
      value: color.name
    })) ?? [],
    status: order.status,
    payment_definition: order.payment_definition,
    initial_payment_percentage: order.initial_payment_percentage,
    hide_on_weekends: order.hide_on_weekends ?? false,
    do_not_send_email: order.do_not_send_email ?? false,
    client_email_selection: clientEmailSelection,
    is_send_email: order.is_send_email ?? false,
    is_new_travel_cost: !!order.is_new_travel_cost,
    new_travel_cost: order.new_travel_cost,
    material_received_date: order.material_received_date ?? null,
    order_colors: order.order_colors ?? [],
    area: order.area ?? 0,
    order_weight: order.order_weight ?? 0
  }
}

interface OrderProductEstraWorkPivot {
  name: string
  pivot: OrderProductsExtraWorks
}

type OrderProductFormValues = OrderProduct & {
  order_product_extra_works?: OrderProductEstraWorkPivot[]
}

export const getOrderProducts = (orderProduct: OrderProductFormValues) => {
  return {
    id: orderProduct.id,
    order_id: Number(orderProduct.order_id),
    qty: Number(orderProduct.qty),
    height: Number(orderProduct.height),
    width: Number(orderProduct.width),
    unit_price: Number(orderProduct.unit_price),
    total_price: Number(orderProduct.total_price),
    total_price_with_extraworks: Number(orderProduct.total_price_with_extraworks),
    unit_price_with_extraworks: Number(orderProduct.unit_price_with_extraworks),
    extra_work_price: Number(orderProduct.extra_work_price),
    notes: orderProduct.notes,
    product_config_id: Number(orderProduct.product_config_id),
    type_of_work_id: orderProduct.type_of_work_id == null ? null : Number(orderProduct.type_of_work_id),
    storefront_area: Number(orderProduct.storefront_area),
    new_price_storefront: Number(orderProduct.new_price_storefront),
    installation_other_level: Boolean(orderProduct.installation_other_level),
    product_category_id: Number(orderProduct.product_category_id),
    type_of_product_id: Number(orderProduct.type_of_product_id),
    pivot_cost: Number(orderProduct.pivot_cost ?? 0),
    extra_works: orderProduct.order_product_extra_works?.map((extra_work: OrderProductEstraWorkPivot) => {
      return {
        order_product_id: Number(extra_work.pivot.order_product_id),
        extra_work_id: Number(extra_work.pivot.extra_work_id),
        amount: Number(extra_work.pivot.amount),
        extra_work_name: extra_work.name,
        price: Number(extra_work.pivot.price)
      }
    })
  }
}
