import { type Product, type Order, Role } from '@/types'
import { isAccounting, isAdmin, isDealer, isProduction, isSubDealer } from './user'

export const getDealerUnitPrice = (product: Product, estimate: Order) => {
  return Number(product.unit_price) + getMarkup(product.unit_price, estimate.user_markup ?? 0)
}

export const getDealerTotalPrice = (product: Product, estimate: Order) => {
  return Number(product.total_price) + getMarkup(product.total_price, estimate.user_markup ?? 0)
}

export const getDealerSubtotal = (estimate: Order) => {
  const subtotal: number | undefined = estimate.products?.reduce((acc, product) => {
    return acc + getDealerTotalPrice(product, estimate)
  }, 0)

  return subtotal
}

export const getDealerSubtotalWithMarkup = (estimate: Order) => {
  const subtotal: number | undefined = estimate.products?.reduce((acc, product) => {
    const markup = getDealerTotalPrice(product, estimate) * Number(product.markup) / 100
    return acc + Number(getDealerTotalPrice(product, estimate)) + markup
  }, 0)

  return subtotal
}

export const getDealerGrandTotal = (estimate: Order) => {
  const subtotal: number = getDealerSubtotalWithMarkup(estimate) ?? 0
  const tax_amount: number = getDealerTaxAmount(estimate) ?? 0
  const installation: number = estimate.installation ?? 0
  const permit: number = estimate.permit ?? 0
  const other: number = estimate.other ?? 0
  return Number(subtotal) + Number(tax_amount) + Number(installation) + Number(permit) + Number(other)
}

export const getSubtotal = (estimate: Order) => {
  const subtotal: number | undefined = estimate.products?.reduce((acc, product) => {
    return acc + Number(product.total_price)
  }, 0)

  return subtotal
}

export const getSubtotalWithMarkup = (estimate: Order) => {
  const subtotal: number | undefined = estimate.products?.reduce((acc, product) => {
    const markup = Number(product.total_price) * Number(product.markup) / 100
    return acc + Number(product.total_price) + markup
  }, 0)

  return subtotal
}

export const getDealerTaxAmount = (estimate: Order) => {
  const tax_race: number = estimate.tax_rate ?? 0
  const subtotal: number = getDealerSubtotalWithMarkup(estimate) ?? 0
  return subtotal * tax_race / 100
}

export const getTaxAmount = (estimate: Order) => {
  const tax_race: number = estimate.tax_rate ?? 0
  const subtotal: number = getSubtotalWithMarkup(estimate) ?? 0
  return subtotal * tax_race / 100
}

export const getGrandTotal = (estimate: Order) => {
  const subtotal: number = getSubtotalWithMarkup(estimate) ?? 0
  const tax_amount: number = getTaxAmount(estimate) ?? 0
  const installation: number = estimate.installation ?? 0
  const permit: number = estimate.permit ?? 0
  const other: number = estimate.other ?? 0
  return Number(subtotal) + Number(tax_amount) + Number(installation) + Number(permit) + Number(other)
}

export const getMarkup = (price: number, markup: number) => {
  return Number(price) * Number(markup) / 100
}

export const formatPrice = (price: number) => {
  const USDollar = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  })

  return USDollar.format(price)
}

export const getUnitPriceByRole = (product: Product, role: string[]) => {
  let unit_price = 0
  if (isSubDealer(role)) {
    unit_price = product.sub_dealer_unit_price
  } else if (isDealer(role)) {
    unit_price = product.dealer_unit_price
  } else if (isAccounting(role) || isAdmin(role) || isAccounting(role)) {
    unit_price = product.unit_price
  } else {
    unit_price = product.customer_unit_price
  }

  return unit_price
}

export const getTotalPriceByRole = (product: Product, role: string[]) => {
  let total_price = 0
  if (isSubDealer(role)) {
    total_price = product.sub_dealer_total_price
  } else if (isDealer(role)) {
    total_price = product.dealer_total_price
  } else if (isAccounting(role) || isAdmin(role) || isAccounting(role)) {
    total_price = product.total_price
  } else {
    total_price = product.customer_total_price
  }

  return Number(total_price)
}

export const getSubTotalPriceByRole = (order: Order, role: string[]) => {
  const subtotal: number | undefined = order.products?.reduce((acc, product) => {
    return acc + getTotalPriceByRole(product, role)
  }, 0)

  return subtotal
}

export const getTaxAmountByRole = (order: Order, role: string[]) => {
  const tax_race: number = order.tax_rate ?? 0
  const subtotal: number = getSubTotalPriceByRole(order, role) ?? 0
  return subtotal * tax_race / 100
}

export const getGrandTotalByRole = (order: Order, role: string[]) => {
  const subtotal: number = getSubTotalPriceByRole(order, role) ?? 0
  const tax_amount: number = getTaxAmountByRole(order, role) ?? 0
  const installation: number = order.installation ?? 0
  const permit: number = order.permit ?? 0
  const other: number = order.other ?? 0
  const rg_other_price: number = order.rg_other_price ?? 0
  return Number(subtotal) + Number(tax_amount) + Number(installation) + Number(permit) + Number(other) + Number(rg_other_price)
}
