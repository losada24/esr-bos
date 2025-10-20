export interface SaleForm {
  id: number
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
  language: string | null
  door_quantity: number
  window_quantity: number
  order_id: number
}
