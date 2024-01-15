import React from 'react'
import { Text, View, Image } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { type Order } from '@/types'
import { PO_TITLES } from '@/Utils/constants'
import logo from '../../../assets/images/logo-reylosglass.png'

const COMPANY_ADDRESS = import.meta.env.VITE_COMPANY_ADDRESS
const COMPANY_PHONE = import.meta.env.VITE_COMPANY_PHONE
const COMPANY_EMAIL = import.meta.env.VITE_COMPANY_EMAIL
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

const POHeaders = ({ order, poType, documentTitle }: { order: Order, poType?: string, documentTitle: string }) => {
  return (
    <>
    <View style={tw('flex flex-row gap-4 justify-between mb-5')}>
      <View style={tw('flex flex-row justify-start w-9/12 gap-x-4')}>
        <Image style={tw('w-4/12 h-auto')} src={logo} />
        <View style={tw('flex flex-col justify-start w-4/12')}>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{COMPANY_ADDRESS}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{COMPANY_PHONE}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{COMPANY_EMAIL}</Text>
        </View>
      </View>
      <View style={tw('flex flex-col')}>
        <View style={tw('flex flex-row justify-end items-center gap-3')}>
          <Text style={tw('text-base text-gray-900 font-bold text-right')}>{documentTitle}</Text>
        </View>
        <View style={tw('flex flex-row justify-start items-center gap-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Order #</Text>
          <Text style={tw('text-base text-gray-500')}>{createMarkWithLeadingZero(order.id ?? 0, 6)}{poType}</Text>
        </View>
      </View>
    </View>
    <View style={tw('flex flex-row gap-4 justify-between')}>
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
    </>
  )
}

export default POHeaders
