import { type Order } from '@/types'

export const getSubtotal = (estimate: Order) => {
  const subtotal: number | undefined = estimate.products?.reduce((acc, product) => {
    return acc + Number(product.total_price)
  }, 0)

  return Math.round(subtotal ?? 0)
}

export const getTaxAmount = (estimate: Order) => {
  const tax_race: number = estimate.tax_rate ?? 0
  const subtotal: number = getSubtotal(estimate) ?? 0
  return Math.round(subtotal * tax_race) / 100
}

export const getGrandTotal = (estimate: Order) => {
  const subtotal: number = getSubtotal(estimate) ?? 0
  const tax_amount: number = getTaxAmount(estimate) ?? 0
  const installation: number = estimate.installation ?? 0
  const permit: number = estimate.permit ?? 0
  const other: number = estimate.other ?? 0
  return Math.round(Number(subtotal) + Number(tax_amount) + Number(installation) + Number(permit) + Number(other))
}
