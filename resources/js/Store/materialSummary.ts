import { create } from 'zustand'
import { FOOT, UNIT, SQFT } from '../Utils/constants'

export interface MaterialsSummary {
  material: string
  quantity: number
  unit?: string
  size?: string
}

export interface MaterialsSummaryState {
  items: MaterialsSummary[]
  addMaterial: (item: MaterialsSummary) => void
}

export const useStore = create<MaterialsSummaryState>((set) => ({
  items: [],
  addMaterial: (item) => {
    set((state) => {
      const itemIndex = state.items.findIndex((i) => i.material === item.material)
      if (itemIndex !== -1) {
        state.items[itemIndex].quantity += item.quantity
        if (item.unit === FOOT) {
         //  state.items[itemIndex]?.size += item.size ?? 0
        }
        return { ...state }
      } else {
        state.items.push(item)
      }
      return { ...state }
    })
  }
}))
