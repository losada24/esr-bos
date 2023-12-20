import { type User } from '@/types/interfaces/user'
import { type Client } from '@/types/interfaces/client'
import { type Product } from '@/types/interfaces/product'
import { type MaterialConsumption } from './materialConsumption'

export interface Order {
  id: number
  name: string
  status?: string
  project_name?: string
  frame_color: string
  glass_color: string
  markup: number
  notes?: string
  client_id: number
  created_at?: Date
  updated_at?: Date
  user?: User
  client?: Client
  products?: Product[]
  products_count?: number
  tax_amount?: number
  tax_rate?: number
  installation?: number
  permit?: number
  other?: number
  external_purchase_id?: string
  glass_type: string
  materialConsumption?: MaterialConsumption[]
  productsCount?: number
}
