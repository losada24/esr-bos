import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Order } from '@/types'
import { getSubtotal, getTaxAmount, getGrandTotal } from '@/Utils/price'

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

const ReportTotal = ({ order }: { order: Order }) => {
  return (
    <View style={tw('flex flex-row mt-4')}>
      <View style={tw('w-6/12')}>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Date:</Text>
          <Text style={tw('text-base text-gray-900 font-regular')}>{order.created_at?.toString()}</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Estimate:</Text>
          <Text style={tw('text-base text-gray-900 font-regular')}>{order.client?.name}</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Client:</Text>
          <Text style={tw('text-base text-gray-900 font-regular')}>{order.user?.company?.name}</Text>
        </View>
      </View>
      <View style={tw('w-6/12')}>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>Subtotal:</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-6/12 text-left')}>{`$${getSubtotal(order)}`}</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>Grand Total:</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-6/12 text-left')}>{`$${getSubtotal(order)}`}</Text>
        </View>
      </View>
    </View>
  )
}

export default ReportTotal
