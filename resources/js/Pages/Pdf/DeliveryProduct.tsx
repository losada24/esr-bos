import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Product } from '@/types'
import { getNumberWithFraction } from '@/Utils/numbers'

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

const DeliveryProduct = ({ product }: { product: Product }) => {
  return (
    <View style={tw('flex flex-col mb-4 border border-gray-200')}>
      <View style={tw('flex flex-col bg-gray-200')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3')}>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Mark</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>System</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-3/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Size</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Frame</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Qty</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Pending</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Total Completed</Text>
          </View>
        </View>
      </View>
      <View style={tw('flex flex-col')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3')}>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.line_item_name}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.system}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-3/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{getNumberWithFraction(product.width)} x {getNumberWithFraction(product.height)}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.frame_color}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.qty}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular border-b border-gray-200')}></Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular border-b border-gray-200')}></Text>
          </View>
        </View>
      </View>
    </View>
  )
}

export default DeliveryProduct
