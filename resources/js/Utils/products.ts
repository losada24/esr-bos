import { PRODUCT_SYSTEMS } from '@/Utils/constants'
import { type Product } from '@/types'

export const getProductCertification = (system: string) => {
  if (system === PRODUCT_SYSTEMS.FIXED_WINDOWS) {
    return 'F.B.C FL 41809'
  } else if (system === PRODUCT_SYSTEMS.SINGLE_HUNG) {
    return 'NOA #23-0921.02'
  } else if (system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER) {
    return 'F.B.C FL 41810'
  }

  return ''
}

export const getGlassCount = (products: Product[]) => {
  return products.filter((product) =>
    product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS ||
    product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER ||
    product.system === PRODUCT_SYSTEMS.SINGLE_HUNG).reduce((acc, product) => {
    let glassCount = 1
    if (product.system !== PRODUCT_SYSTEMS.FIXED_WINDOWS) {
      glassCount = 2
    }
    return acc + (product.qty * glassCount)
  }, 0)
}

export const hasCustomization = (product: Product): boolean => {
  return product.comments !== null || product.attachment !== null
}
