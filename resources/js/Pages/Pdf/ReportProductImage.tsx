import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Product } from '@/types'
import { PRODUCT_SYSTEMS, NO_CERTIFICATION_STANDARD_MESSAGE, CERTIFICATION_SQFT } from '@/Utils/constants'
import FixedWindowsTall from '../FixedWindows/FixedWindowsTall'
import FixedWindowsWider from '../FixedWindows/FixedWindowsWider'
import FixedWindowsSquared from '../FixedWindows/FixedWindowsSquared'

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

const ReportProductImage = ({ product, isImpactGlass }: { product: Product, isImpactGlass?: boolean }) => {
  // TODO: Create helper function to check if product is certified
  // TODO: Fix Image
  return (
    <View>
      {product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS && ((product.width * product.height / 144) > CERTIFICATION_SQFT) && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.SINGLE_HUNG && (product.width > 53.125 || product.height > 74) && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER && (product.width > 74 || product.height > 53) && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {!isImpactGlass && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS && (
        <View style={tw('flex flex-row mb-4 p-3 justify-center')}>
          {product.height > product.width && <FixedWindowsTall width={product.width} height={product.height} svgHeight={250} svgWidth={250} /> }
          {product.width > product.height && <FixedWindowsWider width={product.width} height={product.height} svgHeight={250} svgWidth={250}/> }
          {product.height === product.width && <FixedWindowsSquared width={product.width} height={product.height} svgHeight={250} svgWidth={250} />}
        </View>
      )}
    </View>
  )
}

export default ReportProductImage
