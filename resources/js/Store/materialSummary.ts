import { create } from 'zustand'
import { SQFT } from '../Utils/constants'

export interface MaterialsSummary {
  material: string
  quantity: number
  unit?: string
  size?: number
  part: string
}

export interface MaterialsSummaryState {
  items: MaterialsSummary[]
  addMaterial: (item: MaterialsSummary) => void
  reset: () => void
}

export const useStore = create<MaterialsSummaryState>((set) => ({
  items: [],
  addMaterial: (item) => {
    set((state) => {
      if (item.unit === SQFT) {
        const itemIndex = state.items.findIndex((i) => i.material === item.material && i.size === item.size)
        if (itemIndex !== -1) {
          state.items[itemIndex].quantity += item.quantity
          return { ...state }
        } else {
          state.items.push(item)
        }
      } else {
        const itemIndex = state.items.findIndex((i) => i.material === item.material)
        if (itemIndex !== -1) {
          state.items[itemIndex].quantity += item.quantity
          // state.items[itemIndex].size += Number(item.size) ?? 0
          return { ...state }
        } else {
          state.items.push(item)
        }
      }
      return { ...state }
    })
  },
  reset: () => {
    set((state) => {
      state.items = []
      return { ...state }
    })
  }
}))
