import React from 'react'
import { type CuttingList } from '@/types'

const ShowCuttingList = ({ cuttingList, productId }: { cuttingList: CuttingList[], productId: number }) => {
  return (
    <>
      <tr className="font-bold">
        <th className="px-6 pt-5 pb-4 text-left" colSpan={2}>Part</th>
        <th className="px-6 pt-5 pb-4 text-left">Raw Material</th>
        <th className="px-6 pt-5 pb-4 text-right">Qty</th>
        <th className="px-6 pt-5 pb-4 text-right">Size</th>
      </tr>
      {cuttingList.map((productOrderField, index) => {
        return <tr key={`${productId}${index}`}>
          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" colSpan={2}>{productOrderField.part}</td>
          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{productOrderField.material}</td>
          <th className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">{productOrderField.qty}</th>
          <th className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">{productOrderField.size}</th>
        </tr>
      })}
    </>
  )
}

export default ShowCuttingList
