import { PRODUCT_SYSTEMS } from '@/Utils/constants'

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
