export interface ExternalProductsExtrasMullion {
  configuration?: string
}

export interface ExternalProductsExtrasCasement {
  configuration?: string
  screen?: string
  muntins?: string
  opening?: string
  frame_type?: string
  limit_device?: string
  protective_film?: string
  locking_mechanism?: string
  glass_type?: string
}

export interface ExternalProducts {
  id: number
  external_product: string
  width: number
  height: number
  price: number
  extras: string | ExternalProductsExtrasMullion | ExternalProductsExtrasCasement
  notes?: string
}
