import { type Order } from './order'

export interface Product {
  id: number
  system: string
  width: number
  height: number
  line_item_name: string
  frame_color: string
  qty: number
  markup: number
  glass_type: string
  glass_color: string
  low_e: string
  privacy: string
  // extras: string
  price: number
  created_at?: Date
  order_id: number
  user_id: number
  unit_price: number
  total_price: number
  order?: Order
}
