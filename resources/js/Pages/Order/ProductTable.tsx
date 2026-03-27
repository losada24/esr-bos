import DeleteIcon from '@/Components/Icons/DeleteIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import { type TypeOfProduct, type OrderProduct, type ProductConfig, type ProductCategory, type TravelCost, type TypeOfWork, type ProductCost } from '@/types'
import { OrderProductsExtraWorks} from '@/types/interfaces/order'
import { formatPrice } from '@/Utils/price'
import React, { useState } from 'react'
import { PAYMENT_METHODS, SERVICES } from '@/Utils/constants'
import { type OrderFormValues, getValueIdNotNull } from './OrderCommon'
import BookIcon from '@/Components/Icons/BookIcon'

const ProductTable = ({
  orderProducts,
  type_of_products,
  products_config,
  product_category,
  service,
  values,
  travel_costs,
  type_of_works,
  extraWorks,
  product_costs,
  removeOrderProduct,
  updateOrderProduct
}: {
  orderProducts: OrderProduct[]
  type_of_products: TypeOfProduct[]
  products_config: ProductConfig[]
  product_category: ProductCategory[]
  service: string
  type_of_works: TypeOfWork[]
  values: OrderFormValues
  travel_costs: TravelCost[]
  extraWorks: Array<{ id: number, name: string }>
  product_costs: ProductCost[]

  removeOrderProduct: (index: number) => void
  updateOrderProduct: (index: number) => void
}) => {
  const extraWorkMap = Object.fromEntries((extraWorks ?? []).map(ew => [ew.id, ew.name]))
  const getProductType = (id: number) => {
    return type_of_products.find((type) => type.id === id)?.name
  }
  const [expandedRows, setExpandedRows] = useState<number[]>([])

  const getTypeOfWork = (id: number | null) => {
    if (id == null) {
      return '-'
    }

    return type_of_works.find((type) => type.id === id)?.name
  }
  const getProductCategory = (id: number) => {
    return product_category.find((type) => type.id === id)?.name
  }
  const getProductConfig = (id: number) => {
    return products_config.find((type) => type.id === id)?.name
  }

  const getProductsTotal = () => {
    const result = orderProducts.reduce((acc, value) => {
      return acc + (Number(value.total_price) + Number(value.extra_work_price))
    }, 0)

    return result
  }
  const getOtherCost = () => {
    return values.additional_travel_costs ? Number(values.additional_travel_costs) : 0
  }

  const getTravelCost = () => {
    const id = getValueIdNotNull(values.travel_cost_id)
    if (values.is_new_travel_cost) {
      return values.new_travel_cost ? Number(values.new_travel_cost) : 0
    }
    const result = travel_costs.find((type) => Number(type.id) === Number(id))?.price
    return result ? Number(result) : 0
  }

  const getGrandTotal = () => {
    const result = getProductsTotal() + getOtherCost() + getTravelCost()
    return result
  }
  const showPricingColumns = service === SERVICES.DELIVERY_AND_INSTALLATION || service === SERVICES.SERVICE
  return (
    <div className='table-responsive mt-3'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                  <th className="px-6 pt-5 pb-4">Type of Product</th>
                  <th className="px-6 pt-5 pb-4">Product Category</th>
                  <th className="px-6 pt-5 pb-4">Product Config</th>
                  <th className="px-6 pt-5 pb-4">Type of Work</th>
                  <th className="px-6 pt-5 pb-4 text-right">Count</th>
                {showPricingColumns && (
                  <>
                  <th className="px-6 pt-5 pb-4 text-right">Unit Price</th>
                  <th className="px-6 pt-5 pb-4 text-right">Extra Work</th>
                  <th className="px-6 pt-5 pb-4 text-right">Total Price</th>
                  </>
                )}
                  <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
            {orderProducts.map((product, index) => {
              const isExpanded = expandedRows.includes(index)
              const storefrontBasePrice = product_costs.find(
                (productCost) =>
                  Number(productCost.product_config_id) === Number(product.product_config_id) &&
                  Number(productCost.type_of_work_id) === Number(product.type_of_work_id)
              )?.price
              const storefrontBasePriceLabel = storefrontBasePrice !== undefined ? formatPrice(storefrontBasePrice) : null
              const parsedStorefrontPrice = Number(product.new_price_storefront ?? 0) || 0
              const hasNewStorefrontPrice = parsedStorefrontPrice !== 0
              return (
          <React.Fragment key={index}>
            <tr className="hover:bg-gray-100 focus-within:bg-gray-100">
              <td className="border-t px-6 py-4 align-top">
                {getProductType(product.type_of_product_id)}
              </td>
              <td className="border-t px-6 py-4 align-top">
                {getProductCategory(product.product_category_id)}
              </td>
              <td className="border-t px-6 py-4 align-top">
                {getProductConfig(product.product_config_id)}
              </td>
              <td className="border-t px-6 py-4 align-top">
                {getTypeOfWork(product.type_of_work_id)}
              </td>
              <td className="border-t px-6 py-4 align-top text-right">
                {product.qty}
                {product.type_of_product_id === 3 && (
                  <> ({product.storefront_area} SQFT)</>
                )}
              </td>

              {showPricingColumns && (
                <>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {product.type_of_product_id === 3
                      ? (
                        hasNewStorefrontPrice
                          ? formatPrice(parsedStorefrontPrice)
                          : (storefrontBasePriceLabel !== null ? `${storefrontBasePriceLabel}` : 'N/A')
                        )
                      : formatPrice(product.unit_price)}
                  </td>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {formatPrice(product.extra_work_price)}
                  </td>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {formatPrice(Number(product.total_price) + Number(product.extra_work_price))}
                  </td>
                </>
              )}

        <td className="border-t px-6 py-4 align-top">
        <div className="flex items-center space-x-2">
          <button
            onClick={(e) => {
              e.preventDefault()
              updateOrderProduct(index)
            }}
            title="Edit Product"
          >
            <EditIcon />
          </button>
          <button
            onClick={(e) => {
              e.preventDefault()
              removeOrderProduct(index)
            }}
            title="Delete Product"
          >
            <DeleteIcon />
          </button>
          {(product.extra_works ?? []).length > 0 && (
          <button
            className="ml-2 text-blue-600 text-sm hover:underline"
            onClick={(e) => {
              e.preventDefault()
              setExpandedRows((prev) =>
                prev.includes(index)
                  ? prev.filter((id) => id !== index)
                  : [...prev, index]
              )
            }}
            title="Extra Works"
          >
            <BookIcon/>
          </button>
       )}
         </div>
        </td>
      </tr>

      {/* Extra Works Table */}
      {(product.extra_works ?? []).length > 0 && isExpanded && (
        <tr>
          {/* Esta celda ocupa solo las primeras 4 columnas */}
          <td colSpan={4} className="p-0">
          <div className="bg-gray-100 px-2 py-1 font-semibold text-sm border-b border-gray-300">
        Extra Works
      </div>

            <table className="w-full text-sm border">
              <thead className="bg-gray-100">
                <tr>
                  <th className="px-2 py-1 text-left">Name</th>
                  <th className="px-2 py-1 text-right">Qty</th>
                  <th className="px-2 py-1 text-right">Unit Price</th>
                  <th className="px-2 py-1 text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                {(product.extra_works ?? []).map((extra, i) => (
                  <tr key={i}>
                    <td className="px-2 py-1">{extraWorkMap[extra.extra_work_id] || 'Unknown'}</td>
                    <td className="px-2 py-1 text-right">{extra.amount}</td>
                    <td className="px-2 py-1 text-right">{formatPrice(extra.price)}</td>
                    <td className="px-2 py-1 text-right">
                      {formatPrice(Number(extra.price) * Number(extra.amount))}
                    </td>
                  </tr>
                ))}
        </tbody>
      </table>
    </td>

    {/* Estas celdas mantienen el espacio de las demás columnas */}
    <td colSpan={showPricingColumns ? 5 : 2}></td>
  </tr>
      )}
     </React.Fragment>
              )
})}
            </tbody>
            {showPricingColumns && (
              <tfoot>
                <tr>
                    <td colSpan={7} className="px-6 py-4 align-top text-right">Total</td>
                    <td className='px-6 py-4 align-top text-right'>{ formatPrice(getProductsTotal())}</td>
                    <td>&nbsp;</td>
                </tr>
              <tr>
                    <td colSpan={7} className="px-6 py-4 align-top text-right">Other Cost</td>
                    <td className='px-6 py-4 align-top text-right'>{ formatPrice(getOtherCost())}</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colSpan={7} className="px-6 py-4 align-top text-right">Travel Cost</td>
                    <td className='px-6 py-4 align-top text-right'>{ formatPrice(getTravelCost())}</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colSpan={7} className="px-6 py-4 align-top text-right">Gran Total</td>
                    <td className='px-6 py-4 align-top text-right'>{ formatPrice(getGrandTotal())}</td>
                    <td>&nbsp;</td>
                </tr>
              </tfoot>
            )}
          </table>
        </div>
  )
}

export default ProductTable
