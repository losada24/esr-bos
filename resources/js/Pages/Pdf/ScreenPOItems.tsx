import React, { Fragment } from 'react'
import { type CuttingList } from '@/types'
import { createTw } from 'react-pdf-tailwind'
import { View, Text } from '@react-pdf/renderer'

const tw = createTw({
  theme: {
    extend: {
      fontFamily: {
        regular: 'NunitoRegular',
        bold: 'NunitoBold'
      },
      colors: {
        custom: '#bada55'
      }
    }
  }
})

const ScreenPOItems = ({ cuttingList, productId }: { cuttingList: CuttingList[], productId: number }) => {
  return (
    <>
      <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-6/12')}>Part</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12 text-right')}>Qty</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12 text-right')}>Size</Text>
      </View>
      {cuttingList.map((productOrderField, index) => {
        return <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')} key={`${productId}${index}`}>
          <Text style={tw('text-xs text-gray-900 font-regular w-6/12')}>{productOrderField.part}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular w-3/12 text-right')}>{productOrderField.qty}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular w-3/12 text-right')}>{productOrderField.size}</Text>
        </View>
      })}
    </>
  )
}

export default ScreenPOItems
