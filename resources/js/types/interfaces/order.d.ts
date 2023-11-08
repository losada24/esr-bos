import { type User } from '@/types/interfaces/user'
import { type Client } from '@/types/interfaces/client'
import { type Product } from '@/types/interfaces/product'

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
}
