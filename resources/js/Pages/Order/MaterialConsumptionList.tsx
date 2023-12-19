import { Fragment } from 'react'
import { FOOT, UNIT, SQFT } from '@/Utils/constants'
import { getNumberWithFraction } from '@/Utils/numbers'
import { type MaterialConsumption } from '@/types'

const MaterialConsumptionList = ({ materialConsumption }: { materialConsumption: MaterialConsumption[] }) => {
  return (
    <>
      <h2 className='text-lg font-semibold ltr:sm:text-left rtl:sm:text-right text-center my-4'>Material Consumption</h2>
      <table>
        <tbody>
          <tr className="font-bold">
            <th className="px-6 pt-5 pb-4 text-left">Material</th>
            <th className="px-6 pt-5 pb-4 text-right">Total Amount</th>
            <th className="px-6 pt-5 pb-4 text-right">Wharehouse Delivery</th>
          </tr>
          {materialConsumption.map((item, index) => {
            return <tr key={index}>
              <td className="border-t px-6 py-4 align-top">{item.name}</td>
              {item.unit_of_measurement === UNIT && (
                <>
                  <th colSpan={2} className="border-t px-6 py-4 align-top text-right font-normal">
                    {`${item.amount} ${UNIT}`}
                  </th>
                </>
              )}
              {item.unit_of_measurement === SQFT && (
                <>
                  <th className="border-t px-6 py-4 align-top text-right font-normal">
                    {`${item.amount / 144}`}
                  </th>
                </>
              )}
              {item.unit_of_measurement === FOOT && (
                <>
                  <th className="border-t px-6 py-4 align-top text-right font-normal">
                    {`${getNumberWithFraction(item.amount)} ${item.unit_of_measurement}`}
                  </th>
                  <th className="border-t px-6 py-4 align-top text-right font-normal">
                    {`${Math.ceil((item.amount) / item.storage_measure)}(pcs)`}
                  </th>
                </>
              )}
            </tr>
          })}
        </tbody>
      </table>
    </>
  )
}

export default MaterialConsumptionList
