import React, { Fragment } from 'react'
import { type CuttingList } from '@/types'
import { createTw } from 'react-pdf-tailwind'
import { View, Text } from '@react-pdf/renderer'
import { getDecimalFromFractionNumber } from '@/Utils/numbers'

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

const CuttingListItems = ({ cuttingList, productId }: { cuttingList: CuttingList[], productId: number }) => {
  const printGlassNote = (size: string): boolean => {
    const sizeArray = size.split('x')
    const width = sizeArray[0].trim()
    const height = sizeArray[1].trim()

    let result = false
    if (getDecimalFromFractionNumber(width) < 13.75 || getDecimalFromFractionNumber(height) < 13.75) {
      result = true
    }

    return result
  }

  return (
    <>
      <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-4/12')}>Part</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-4/12')}>Raw Material</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-2/12 text-right')}>Qty</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-2/12 text-right')}>Size</Text>
      </View>
      {cuttingList.map((productOrderField, index) => {
        return <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')} key={`${productId}${index}`}>
          <View style={tw('w-4/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{productOrderField.part}</Text>
            {(productOrderField.part === 'Glass' || productOrderField.part === 'Glass Fixed' || productOrderField.part === 'Glass Move') && printGlassNote(productOrderField.size) && (
              <Text style={tw('text-xs text-red-700 font-regular')}>ORDER TO OTHER PROVIDER</Text>
            )}
          </View>
          <Text style={tw('text-xs text-gray-900 font-regular w-4/12')}>{productOrderField.material}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular w-2/12 text-right')}>{productOrderField.qty}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular w-2/12 text-right')}>{productOrderField.size}</Text>
        </View>
      })}
    </>
  )
}

export default CuttingListItems
