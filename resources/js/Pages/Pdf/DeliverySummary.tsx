import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Order } from '@/types'

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

const DeliverySummary = ({ order }: { order: Order }) => {
  const systemSummary: SummaryProduct[] = []
  let screenTotal = 0

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
  })

  return (
    <View style={tw('flex flex-row mt-4 gap-4')}>
      <View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>System Summary</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-6')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Systems</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right font-bold')}>{systemSummary.length}</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 font-bold text-center')}>Pending</Text>
        </View>
        {systemSummary.map((summaryProduct, index) => {
          return (
            <View key={`productSummary${index}`} style={tw('flex flex-row justify-start gap-x-6')}>
              <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>{summaryProduct.system}</Text>
              <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}>{summaryProduct.quantity}</Text>
              <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right border-b border-gray-200')}></Text>
            </View>
          )
        })}
        <View style={tw('flex flex-row justify-start gap-x-6')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Units</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}>{systemSummary.reduce((acc, value) => acc + value.quantity, 0)}</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right border-b border-gray-200')}></Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-6')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Screens</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}>{screenTotal}</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right border-b border-gray-200')}></Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-6')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Total Completed</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right')}></Text>
          <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right border-b border-gray-200')}></Text>
        </View>
      </View>
    </View>
  )
}

export default DeliverySummary
