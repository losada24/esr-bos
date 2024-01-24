import React from 'react'
import { Text, View, Image } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { createMarkWithLeadingZero } from '@/Utils/mark'

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

interface ReportHeaderDataProps {
  id: number
  featured_image: string
  address: string
  phone_number: string
  email?: string
}

const ReportHeader = ({ data, documentTitle }: { data?: ReportHeaderDataProps, documentTitle?: string }) => {
  return (
    <View style={tw('flex flex-row gap-4 justify-between')}>
      <View style={tw('flex flex-row justify-start w-9/12 gap-x-4')}>
        <Image style={tw('w-4/12 h-auto')} src={data?.featured_image} />
        <View style={tw('flex flex-col justify-start w-4/12')}>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{data?.address}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{data?.phone_number}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{data?.email}</Text>
        </View>
      </View>
      <View style={tw('flex flex-row justify-between w-3/12 text-right')}>
        <Text style={tw('text-base text-gray-900 font-bold')}> {documentTitle ?? 'Quote #'} {createMarkWithLeadingZero(data?.id ?? 0, 6)}</Text>
      </View>
    </View>
  )
}

export default ReportHeader
