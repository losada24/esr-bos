import { Fragment } from 'react'
import { useStore } from '@/Store/materialSummary'
import { FOOT, UNIT, SQFT } from '@/Utils/constants'

const MaterialConsumption = () => {
  const store = useStore()
  return (
    <>
      <h2 className='text-lg font-semibold ltr:sm:text-left rtl:sm:text-right text-center my-4'>Material Consumption</h2>
      <table>
        <thead>
          <tr className="font-bold">
            <th className="px-6 pt-5 pb-4 text-left">Material</th>
            <th className="px-6 pt-5 pb-4 text-right">Qty</th>
          </tr>
        </thead>
        <tbody>
          {store.items.map((item, index) => {
            return <tr key={index}>
              <td className="border-t px-6 py-4 align-top">{item.material}</td>
              <td className="border-t px-6 py-4 align-top text-right">
                {item.unit === UNIT && `${item.quantity} ${UNIT}` }
                {item.unit === SQFT && `${item.size} ${SQFT}, ${item.quantity}(pcs)` }
                {item.unit === FOOT && `${item.quantity} ${FOOT}` }
              </td>
            </tr>
          })}
        </tbody>
      </table>
    </>
  )
}

export default MaterialConsumption
