import DeleteIcon from '@/Components/Icons/DeleteIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import { type TypeOfProduct, type OrderProduct, type ProductConfig, type ProductCategory } from '@/types'
import { formatPrice } from '@/Utils/price'
import React from 'react'

const ProductTable = ({
  orderProducts,
  type_of_products,
  products_config,
  product_category,
  removeOrderProduct,
  updateOrderProduct
}: {
  orderProducts: OrderProduct[]
  type_of_products: TypeOfProduct[]
  products_config: ProductConfig[]
  product_category: ProductCategory[]
  removeOrderProduct: (index: number) => void
  updateOrderProduct: (index: number) => void
}) => {
  const getProductType = (id: number) => {
    return type_of_products.find((type) => type.id === id)?.name
  }
  const getProductCategory = (id: number) => {
    return product_category.find((type) => type.id === id)?.name
  }
  const getProductConfig = (id: number) => {
    return products_config.find((type) => type.id === id)?.name
  }

  return (
    <div className='table-responsive mt-3'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Type of Product</th>
                <th className="px-6 pt-5 pb-4">Product Category</th>
                <th className="px-6 pt-5 pb-4">Product Config</th>
                <th className="px-6 pt-5 pb-4 text-right">Count</th>
                <th className="px-6 pt-5 pb-4 text-right">Unit Price</th>
                <th className="px-6 pt-5 pb-4 text-right">Total Price</th>
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
                    <td className="border-t px-6 py-4 align-top text-right">
                      {product.qty}
                    </td>
                    <td className="border-t px-6 py-4 align-top text-right">
                      {formatPrice(product.unit_price)}
                    </td>
                    <td className="border-t px-6 py-4 align-top text-right">
                      {formatPrice(product.total_price)}
                    </td>
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
                  <td className="px-6 py-4 border-t" colSpan={6}>
                    No Products found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
  )
}

export default ProductTable
