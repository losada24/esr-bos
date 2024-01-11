import React from 'react'
import { Text, View, Image } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { createMarkWithLeadingZero } from '@/Utils/mark'

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

interface ReportHeaderDataProps {
  id: number
  featured_image: string
  address: string
  phone_number: string
  email?: string
}

const ReportHeader = ({ data, logo, isForClient }: { data?: ReportHeaderDataProps, logo: string, isForClient?: boolean }) => {
  return (
    <View style={tw('flex flex-row gap-4 justify-between')}>
      <View style={tw('flex flex-row justify-start w-9/12 gap-x-4')}>
        <Image style={tw('w-4/12 h-auto')} src={isForClient ? data?.featured_image : logo} />
        <View style={tw('flex flex-col justify-start w-4/12')}>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{isForClient ? data?.address : COMPANY_ADDRESS}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{isForClient ? data?.phone_number : COMPANY_PHONE}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{isForClient ? data?.email : COMPANY_EMAIL}</Text>
        </View>
      </View>
      <View style={tw('flex flex-row justify-between w-3/12 text-right')}>
        <Text style={tw('text-base text-gray-900 font-bold')}>Quote #{createMarkWithLeadingZero(data?.id ?? 0, 6)}</Text>
      </View>
    </View>
  )
}

export default ReportHeader
