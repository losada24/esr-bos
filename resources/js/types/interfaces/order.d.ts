import { type User } from '@/types/interfaces/user'
import { type Client } from '@/types/interfaces/client'
import exp from 'constants'

export interface Order {
  id: number
  name: string
  order_number: number
  job_address?: string
  job_city?: string
  job_state?: string
  job_zip?: string
  city_permits: boolean
  association_permits: boolean
  equipment_rental: boolean
  notes?: string
  work_team_notes?: string
  client_id: number
  user_id?: number
  created_at?: Date
  updated_at?: Date
  entry_date?: Date
  installation_date?: Date
  inspection_date?: Date
  finish_date?: Date
  service_date?: Date
  final_inspection_date?: Date
  complete_date?: Date
  user?: User
  client?: Client
  additional_travel_costs?: number
  type_of_work_id: number
  typeOfWork?: TypeOfWork // TODO: Remove this line
  type_of_work?: TypeOfWork
  type_of_housing_id: number
  type_of_housing?: TypeOfHousing
  installation_teams: InstallationTeam[]
  supervisor_id: number
  supervisor?: User
  travel_cost_id: number
  travel_cost?: TravelCost
  duration_of_work_id: number
  duration_of_work?: DurationOfWork
  method_of_payment: string
  service: string
  contract_signing_date: Date
  payment_factory_date: Date
  delivery_date: Date
  owners: User[]
  order_products?: OrderProduct[]
  attachments?: Attachment[]
  eta_date?: Date
  installation_end_date?: Date
  frame_color: string []
  status?: string
  cost_delivery?: number
  cost_city_fee?: number
  project_amount?: number
  down_payment?: number
  city?: string
  job_state?: string
  job_zip?: string
  type_of_financing?: string
  payment_definition?: boolean
  initial_payment_percentage?: number
  hide_on_weekends?: boolean
  do_not_send_email?: boolean
  pending_collect?: Date
  payment_extra_fields?: PaymentExtraFields
  installation_payment?: InstallationPayment[]
  payment_schedule?: PaymentSchedule | null
  pre_inspection?: boolean
  inspection?: boolean
  walk_trough?: boolean
  partial_payment_installation?: boolean
  final_payment_installation?: boolean
  pre_inspection_attach?: Attachment
  inspection_attach?: Attachment
  walk_trough_attach?: Attachment
  is_send_email?: boolean
  is_new_travel_cost?: boolean
  new_travel_cost?: number
  material_received_date?: Date | null
  order_colors?: OrderColor[] | []
  description?: string
  name_check?: boolean
  address_check?: boolean
  amount_check?: boolean
  email_check?: boolean
}

export interface TypeOfWork {
  id: number
  name: string
  notes: string
  productCosts: ProductCost[]
  orders: Order[]
  orderProducts?: OrderProduct[]
}

export interface OrderColor {
  id: number
  name: string
  order_id: number
  order?: Order
}

export interface ProductCost {
  id: number
  price: number
  difficult_hight_price: number
  notes: string
  type_of_work_id: number
  typeOfWork: TypeOfWork
  product_config_id: number
  productConfig?: ProductConfig
}

export interface ProductConfig {
  id: number
  name: string
  notes: string
  product_categories_id: number
  productCategory: ProductCategory
  productCosts: ProductCost[]
  orderProducts?: OrderProduct[]
}

export interface ProductCategory {
  id: number
  name: string
  notes: string
  productConfigs: ProductConfig[]
  type_of_products_id: number
  typeOfProduct?: TypeOfProduct
}

export interface TypeOfProduct {
  id: number
  name: string
  notes: string
  productCategories: ProductCategory[]
  extra_works: ExtraWorks[]
}

export interface OrderProduct {
  id: number
  order_id: number
  qty: number
  height: number
  width: number
  unit_price: number
  unit_price_with_extraworks: number
  total_price: number
  total_price_with_extraworks: number
  extra_work_price: number
  notes: string
  order?: Order
  product_config_id: number
  productConfig?: ProductConfig
  type_of_work_id: number
  typeOfWork?: TypeOfWork
  storefront_area: number
  installation_other_level: boolean
  extra_works?: OrderProductsExtraWorks[]
  product_category_id: number
  productCategory?: ProductCategory
  type_of_product_id: number
  typeOfProduct?: TypeOfProduct
  pivot_cost?: number
  new_price_storefront?: number
}

export interface OrderProductsExtraWorks {
  order_product_id: number
  extra_work_id: number
  amount: number
  price: number
}

export interface ExtraWorks {
  id: number
  name: string
  price: number
  unit: string
  notes: string
  orderProducts?: OrderProduct[]
  planned: boolean
}

export interface TypeOfHousing {
  id: number
  name: string
  notes: string
  orders: Order[]
  installationTeamTypeHousings: InstallationTeam[]
}

export interface InstallationTeam {
  id: number
  number_of_member: number
  worker_compensation_expiration_date: Date | null
  liability_expiration_date: Date | null
  annual_w9_expiration_date: Date | null
  notes?: string
  user_id: number
  user?: User
  type_housing?: TypeOfHousing[]
  attachments: Attachment[]
  orders?: Order[]
  company_name?: string
  phone?: string
  travel_costs?: TravelCost[]
}

export interface Attachment {
  id: number
  filename: string
  file_path: string
  file_type: string
  created_at?: string
  uploaded_by?: string | null
  user_id?: number
}

export interface TravelCost {
  id: number
  name: string
  price: number
  notes?: string
  orders?: Order[]
}

export interface DurationOfWork {
  id: number
  name: string
  price: number
  number_of_day: number
  notes: string
  orders?: Order[]
}
export interface OrderStatus {
  id: number
  status: string
  order_id: number
  user_id: number
  start_date: Date
  end_date: Date
  user?: User
  created_at_formatted: string
  updated_at: Date
  pickup_date: Date
  inspection_date: Date
  finish_date: Date
  service_date: Date
  final_inspection_date: Date
  complete_date: Date
  material_received_date?: Date
}

export interface PaymentExtraFields {
  id: number
  order_id: number
  installer_payment_status?: string
  order?: Order
  installation_team_id: number
}

export interface InstallationPayment {
  id: number
  order_id: number
  installer_payment: number
  percentage_payment: number
  payment_date: Date | null
  order?: Order
  installation_team_id: number
  extra_work: number
  extra_discount: number
  other_cost_installer: number
  payment_status: string
  biweekly_id: number
  responsible_extra_work: string
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

export interface BiweeklyInstaller {
  id: number
  start_biweekly_period: Date | null
  end_biweekly_period: Date | null
  period: string[]
}

export interface Source {
  id: number
  name: string
  description: string
}
