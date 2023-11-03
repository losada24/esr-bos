import { type User } from '@/types/interfaces/user'

export interface RawMaterial {
  id: number
  name: string
  qty: number
  unit_of_measurement: string
  cost_per_unit: number
  featured_image: string
  notes?: string
  created_at?: Date
  updated_at?: Date
  user?: User
}
