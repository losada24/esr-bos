import { type Order } from '@/types'
import { getSubtotal } from '@/Utils/price'

export default function PaymentSummary ({ estimate }: {
  estimate: Order
}) {
  return (
    <div className='table-responsive mb-4'>
      <table className="w-full whitespace-nowrap">
        <thead>
          <tr className="font-bold text-left">
            <th className="px-6 pt-5 pb-4">System</th>
            <th className="px-6 pt-5 pb-4">Mark</th>
            <th className="px-6 pt-5 pb-4 text-right">Qty</th>
            <th className="px-6 pt-5 pb-4">Size</th>
            <th className="px-6 pt-5 pb-4">Frame Color</th>
            <th className="px-6 pt-5 pb-4">Glass</th>
            <th className="px-6 pt-5 pb-4 text-right">Price</th>
            <th className="px-6 pt-5 pb-4 text-right">Amount</th>
          </tr>
        </thead>
        <tbody>
          {estimate.products?.map(({ id, system, line_item_name, qty, width, height, frame_color, glass_type, unit_price, total_price }) => (
              <tr
                key={id}
                className="hover:bg-gray-100 focus-within:bg-gray-100"
              >
                <td className="border-t px-6 py-4 align-top">
                  {system}
                </td>
                <td className="border-t px-6 py-4 align-top">
                  {line_item_name}
                </td>
                <td className="border-t px-6 py-4 align-top text-right">
                  {qty}
                </td>
                <td className="border-t px-6 py-4 align-top">
                  {width} x {height}
                </td>
                <td className="border-t px-6 py-4 align-top">
                  {frame_color}
                </td>
                <td className="border-t px-6 py-4 align-top">
                  {glass_type}
                </td>
                <td className="border-t px-6 py-4 align-top text-right">
                  ${unit_price}
                </td>
                <td className="border-t px-6 py-4 align-top text-right">
                  ${total_price}
                </td>
              </tr>
          ))
          }
          {estimate.products?.length === 0 && (
            <tr>
              <td className="px-6 py-4 border-t" colSpan={9}>
                No Products found.
              </td>
            </tr>
          )}
        </tbody>
        <tfoot>
          <tr>
            <td className="px-6 py-4 border-t text-right" colSpan={7}>
              Subtotal
            </td>
            <td className="px-6 py-4 border-t text-right">
              ${getSubtotal(estimate)}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  )
}
