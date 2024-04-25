import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Order } from '@/types'
import { getGlassCount } from '@/Utils/products'

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

interface SummaryProduct {
  system: string
  quantity: number
  sqft: number
}

const SystemSummary = ({ order, totalStickers }: { order: Order, totalStickers?: number }) => {
  const systemSummary: SummaryProduct[] = []
  let screenTotal = 0
  let muntinTotal = 0

  const getSqft = (width: number, height: number) => {
    return (width * height) / 144
  }

  order?.products?.forEach((product) => {
    const systemIndex = systemSummary.findIndex((summaryProduct) => summaryProduct.system === product.system)
    if (systemIndex === -1) {
      systemSummary.push({
        system: product.system,
        quantity: product.qty,
        sqft: getSqft(product.width, product.height) * product.qty
      })
    } else {
      systemSummary[systemIndex].quantity += product.qty
      systemSummary[systemIndex].sqft += getSqft(product.width, product.height) * product.qty
    }

    if (product.extras?.screen) {
      screenTotal += product.qty
    }

    if (product.extras?.muntin_panels) {
      muntinTotal += 1
    }
  })

  return (
    <View style={tw('flex flex-row mt-4 gap-4')}>
      <View style={tw('w-6/12')}>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>System Summary</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Systems</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right font-bold')}>{systemSummary.length}</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Glases</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right font-bold')}>{getGlassCount(order.products ?? [])}</Text>
        </View>
        {totalStickers && (
          <View style={tw('flex flex-row justify-start gap-x-3')}>
            <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Stickers</Text>
            <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right font-bold')}>{totalStickers}</Text>
          </View>
        )}
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Screens</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right font-bold')}>{screenTotal > 0 ? screenTotal : 'No' }</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Muntins</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right font-bold')}>{muntinTotal > 0 ? muntinTotal : 'No' }</Text>
        </View>
        {systemSummary.map((summaryProduct, index) => {
          return (
            <View key={`productSummary${index}`} style={tw('flex flex-row justify-start gap-x-3')}>
              <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>{summaryProduct.system}</Text>
              <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}>{summaryProduct.quantity}</Text>
            </View>
          )
        })}
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Units</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}>{systemSummary.reduce((acc, value) => acc + value.quantity, 0)}</Text>
        </View>
      </View>
      <View style={tw('w-6/12')}>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Sqft Summary</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}> </Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}> </Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}> </Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}> </Text>
        </View>
        {systemSummary.map((summaryProduct, index) => {
          return (
            <View key={`productSummary${index}`} style={tw('flex flex-row justify-start gap-x-3')}>
              <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>{summaryProduct.system}</Text>
              <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}>{`${summaryProduct.sqft.toFixed(2)} sqft`}</Text>
            </View>
          )
        })}
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Sqft</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}>{`${systemSummary.reduce((acc, value) => acc + value.sqft, 0).toFixed(2)} sqft`}</Text>
        </View>
      </View>
    </View>
  )
}

export default SystemSummary
