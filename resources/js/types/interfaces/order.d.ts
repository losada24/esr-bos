import { type User } from '@/types/interfaces/user'
import { type Client } from '@/types/interfaces/client'

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
  frame_color: string
  status?: string
  cost_delivery?: number
  cost_city_fee?: number
  project_amount?: number
  city?: string
  type_of_financing?: string
  payment_definition?: boolean
  initial_payment_percentage?: number
  hide_on_weekends?: boolean
  do_not_send_email?: boolean
}

export interface TypeOfWork {
  id: number
  name: string
  notes: string
  productCosts: ProductCost[]
  orders: Order[]
  orderProducts?: OrderProduct[]
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
  updated_at: Date
  pickup_date: Date
  inspection_date: Date
  finish_date: Date
  final_inspection_date: Date
  complete_date: Date
}
