import { type OrderProduct } from '@/types'
import React from 'react'

const ProductTable = ({ orderProducts }: { orderProducts: OrderProduct[] }) => {
  // console.log(orderProducts)
  return (
    <div className='table-responsive mt-3'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Type of Product</th>
                <th className="px-6 pt-5 pb-4">Category</th>
                <th className="px-6 pt-5 pb-4">Count</th>
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
                      {''}
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
