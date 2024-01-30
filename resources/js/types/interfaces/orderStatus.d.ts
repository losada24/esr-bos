import { type User } from './user'

export interface OrderStatus {
  id: number
  status: string
  notes: string
  order_id: number
  created_at: string
  updated_at: string
  user_id?: number
  user?: User
}
