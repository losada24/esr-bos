import { Fragment } from 'react'
import { useStore } from '@/Store/materialSummary'
import { FOOT, UNIT, SQFT, LENGTH_OF_BARS } from '@/Utils/constants'
import { getNumberWithFraction } from '@/Utils/numbers'

const MaterialConsumption = () => {
  const store = useStore()
  return (
    <>
      <h2 className='text-lg font-semibold ltr:sm:text-left rtl:sm:text-right text-center my-4'>Material Consumption</h2>
      <table>
        <thead>
          <tr className="font-bold">
            <th className="px-6 pt-5 pb-4 text-left">Material</th>
            <th className="px-6 pt-5 pb-4 text-right">Necessary Amount</th>
            <th className="px-6 pt-5 pb-4 text-right">Wharehouse Delivery</th>
          </tr>
        </thead>
        <tbody>
          {store.items.map((item, index) => {
            return <tr key={index}>
              <td className="border-t px-6 py-4 align-top">{item.material}</td>
              {item.unit === UNIT && (
                <>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {`${item.quantity} ${UNIT}`}
                  </td>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {`${item.quantity} ${UNIT}`}
                  </td>
                </>
              )}
              {item.unit === SQFT && (
                <>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {`${item.part}`}
                  </td>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {`${item.quantity}(pcs)`}
                  </td>
                </>
              )}
              {item.unit === FOOT && (
                <>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {`${getNumberWithFraction(item.size ?? 0 * 0.083)} ${FOOT}`}
                  </td>
                  <td className="border-t px-6 py-4 align-top text-right">
                    {`${Math.ceil((item.size ?? 0) / LENGTH_OF_BARS)}(pcs)`}
                  </td>
                </>
              )}
            </tr>
          })}
        </tbody>
      </table>
    </>
  )
}

export default MaterialConsumption
