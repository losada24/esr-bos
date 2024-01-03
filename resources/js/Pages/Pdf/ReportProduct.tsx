import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Product } from '@/types'
import ReportProductImage from '@/Pages/Pdf/ReportProductImage'
import { PRODUCT_SYSTEMS } from '@/Utils/constants'

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

const ReportProduct = ({ product }: { product: Product }) => {
  return (
    <View style={tw('flex flex-col mb-4 border border-gray-200')}>
      <View style={tw('flex flex-col bg-gray-200')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3')}>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-3/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Mark</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Qty</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>System</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-3/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Size</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Frame</Text>
          </View>
        </View>
      </View>
      <View style={tw('flex flex-col')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3')}>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-3/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.line_item_name}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.qty}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.system}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-3/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.width}&quot; x {product.height}&quot;</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.frame_color}</Text>
          </View>
        </View>
      </View>
      <View>
        <ReportProductImage product={product} />
      </View>
      <View style={tw('flex flex-row gap-4 justify-start bg-gray-200')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3 w-9/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Glass Type:</Text>
        </View>
        <View style={tw('flex flex-row gap-4 justify-start p-3 w-3/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Pressure:</Text>
        </View>
      </View>

      <View style={tw('flex flex-row gap-4 justify-start')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3 w-9/12')}>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{product.glass_type}</Text>
        </View>
        <View style={tw('flex flex-row gap-4 justify-start p-3 w-3/12')}>
          <Text style={tw('text-xs text-gray-900 font-regular text-green-700')}>
            {product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER || product.system === PRODUCT_SYSTEMS.SINGLE_HUNG ? '+70/-70 psf' : '+75/-75 psf'}
          </Text>
        </View>
      </View>
    </View>
  )
}

export default ReportProduct
