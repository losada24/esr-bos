import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { createMarkWithLeadingZero } from '@/Utils/mark'
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

const POHeaders = ({ order, poType }: { order: Order, poType?: string }) => {
  return (
    <View style={tw('flex flex-row gap-4 justify-between')}>
      <View style={tw('flex flex-row justify-start items-center gap-3')}>
        <Text style={tw('text-base text-white-dark text-black')}>Order #</Text>
        <Text style={tw('text-base text-white-dark dark:text-gray-500')}>{`${createMarkWithLeadingZero(order.id, 6)}${poType ?? ''}`}</Text>
      </View>
      <View style={tw('flex flex-row justify-start items-center gap-3')}>
        <Text style={tw('text-base text-white-dark text-black')}>Client</Text>
        <Text style={tw('text-base text-white-dark dark:text-gray-500')}>{order.client?.name}</Text>
      </View>
      <View style={tw('flex flex-row justify-start items-center gap-3')}>
        <Text style={tw('text-base text-white-dark text-black')}>Project</Text>
        <Text style={tw('text-base text-white-dark dark:text-gray-500')}>{order.project_name ? order.project_name : 'No Project'}</Text>
      </View>
      <View style={tw('flex flex-row justify-start items-center gap-3')}>
        <Text style={tw('text-base text-white-dark text-black')}>Created At</Text>
        <Text style={tw('text-base text-white-dark dark:text-gray-500')}>{order.created_at ? order.created_at.toString() : 'No Date'}</Text>
      </View>
    </View>
  )
}

export default POHeaders
