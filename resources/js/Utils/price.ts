import { type ProductCost, type OrderProduct } from '@/types'
import { PIVOT_CONFIG } from './constants'

export const formatPrice = (price: number) => {
  const USDollar = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  })

  return USDollar.format(price)
}

export const getProductExtraWorkPrice = (product: OrderProduct) => {
  const productWithExtraWork = product?.extra_works?.reduce((acc, extraWork) => {
    let price = parseFloat(extraWork.price.toString())
    if (extraWork.amount !== 0) {
      price = price * extraWork.amount
    }
    return acc + price
  }, 0)

  return productWithExtraWork
}

export const getProductPriceWithExtraWorks = (
  product: OrderProduct,
  productCost: ProductCost[]
) => {
  const extraWorkPrice = getProductExtraWorkPrice(product)
  const price = getProductPrice(product, productCost)
  return price + (extraWorkPrice ?? 0)
}

export const getProductPrice = (
  product: OrderProduct,
  productCost: ProductCost[]
) => {
  const productCostPrice = productCost.find((productCost) =>
    productCost.product_config_id === product.product_config_id && productCost.type_of_work_id === product.type_of_work_id
  )

  let price: number = parseFloat(productCostPrice?.price.toString() ?? '0')
  if (product.new_price_storefront !== 0.00 && product.new_price_storefront !== undefined) {
    price = product.new_price_storefront
  }
  if (product.type_of_product_id === 3) {
    price = price * product.storefront_area
  } else if (product.product_config_id === PIVOT_CONFIG) {
    price = product.pivot_cost ?? 0
  }

  if (product.installation_other_level) {
    price += parseFloat(productCostPrice?.difficult_hight_price?.toString() ?? '0')
  }

  return price
}
