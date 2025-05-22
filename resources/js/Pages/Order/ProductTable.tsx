import DeleteIcon from '@/Components/Icons/DeleteIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import { type TypeOfProduct, type OrderProduct, type ProductConfig, type ProductCategory, type TravelCost, type TypeOfWork } from '@/types'
import { OrderProductsExtraWorks} from '@/types/interfaces/order'
import { formatPrice } from '@/Utils/price'
import React from 'react'
import { PAYMENT_METHODS, SERVICES } from '@/Utils/constants'
import { type OrderFormValues, getValueIdNotNull } from './OrderCommon'

const ProductTable = ({
  orderProducts,
  type_of_products,
  products_config,
  product_category,
  service,
  values,
  travel_costs,
  type_of_works,
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

  removeOrderProduct: (index: number) => void
  updateOrderProduct: (index: number) => void
}) => {
  const getProductType = (id: number) => {
    return type_of_products.find((type) => type.id === id)?.name
  }
  const getTypeOfWork = (id: number) => {
    return type_of_works.find((type) => type.id === id)?.name
  }
  const getProductCategory = (id: number) => {
    return product_category.find((type) => type.id === id)?.name
  }
  console.log('orderProducts', orderProducts)
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
                {service === SERVICES.DELIVERY_AND_INSTALLATION && (
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
                return (
                  <tr
                    key={index}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
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
                    </td>
                    {service === SERVICES.DELIVERY_AND_INSTALLATION && (
                    <>
                    <td className="border-t px-6 py-4 align-top text-right">
                      {formatPrice(product.unit_price)}
                    </td>
                    <td className="border-t px-6 py-4 align-top text-right">
                      {formatPrice(product.extra_work_price) }
                    </td>
                    <td className="border-t px-6 py-4 align-top text-right">
                      {formatPrice(Number(product.total_price) + Number(product.extra_work_price))}
                    </td>
                    </>
                    )}
                    <td className="border-t px-6 py-4 align-top">
                      {/* <button
                          onClick={(e) => {
                            e.preventDefault()
                            updateOrderProduct(index)
                          }}
                          title='Edit Order'
                        >
                          <EditIcon />
                      </button> */}
                      <button
                          onClick={(e) => {
                            e.preventDefault()
                            removeOrderProduct(index)
                          }}
                          title='Delete Product'
                        >
                          <DeleteIcon />
                      </button>
                    </td>
                  </tr>
                )
              })}
              {orderProducts.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={7}>
                    No Products found.
                  </td>
                </tr>
              )}
            </tbody>
            {service === SERVICES.DELIVERY_AND_INSTALLATION && (
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
