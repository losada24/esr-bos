export interface ExternalProductsExtrasMullion {
  configuration?: string
}

export interface ExternalProducts {
  id: number
  external_product: string
  width: number
  height: number
  price: number
  extras: string | ExternalProductsExtrasMullion
  notes?: string
}
