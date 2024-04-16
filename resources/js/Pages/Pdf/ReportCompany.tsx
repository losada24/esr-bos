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

const ReportCompany = ({ order }: { order: Order }) => {
  return (
    <View style={tw('flex flex-row gap-4 justify-between mt-6 border-b border-gray-200 pb-3')}>
      <View style={tw('flex flex-row justify-start w-4/12 gap-x-4')}>
        <Text style={tw('text-xs text-gray-900 font-bold')}>Client Name:</Text>
        <Text style={tw('text-xs text-gray-900 font-regular')}>{order.client?.name}</Text>
      </View>
      <View style={tw('flex flex-row justify-start w-4/12 gap-x-4')}>
        <Text style={tw('text-xs text-gray-900 font-bold')}>Job Name:</Text>
        <Text style={tw('text-xs text-gray-900 font-regular')}>{order.name}</Text>
      </View>
      <View style={tw('flex flex-row justify-start w-4/12 gap-x-4')}>
        <Text style={tw('text-xs text-gray-900 font-bold')}>External Purchase Id:</Text>
        <Text style={tw('text-xs text-gray-900 font-regular')}>{order.external_purchase_id}</Text>
      </View>
    </View>
  )
}

export default ReportCompany
