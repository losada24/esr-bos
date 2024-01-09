interface CuttingListItems {
  frame_color: string
  height: number
  line_item_name: string
  part: string
  qty: number
  rawSize: number
  size: string
  system: string
  width: number
  visual_id: number
}

export interface CuttingListProducts {
  material: string
  items: CuttingListItems[]
}
