import { type ProductCost, type OrderProduct } from '@/types'

export const formatPrice = (price: number) => {
  const USDollar = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  })

  return USDollar.format(price)
}

export const getProductExtraWorkPrice = (product: OrderProduct,) => {
  return product?.extra_works?.reduce((acc, extraWork) => {
    let price = parseFloat(extraWork.price.toString())
    if (extraWork.amount !== 0) {
      price = price * extraWork.amount
    }
    return acc + price
  }, 0)
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
  if (product.type_of_product_id === 3) {
    price = price * product.storefront_area
  }

  if (product.installation_other_level) {
    price += parseFloat(productCostPrice?.difficult_hight_price?.toString() ?? '0')
  }

  return price
}
