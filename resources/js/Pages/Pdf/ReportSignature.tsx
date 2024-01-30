import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'

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

const ReportSignature = () => {
  return (
    <View style={tw('flex flex-row gap-4 mt-6')}>
      <View style={tw('flex flex-col justify-start gap-x-2 w-4/12')}>
        <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Salesman:</Text>
        <View style={tw('border-b border-gray-200')}>
          <Text style={tw('text-base text-gray-900 font-regular w-6/12')}>X</Text>
        </View>
      </View>
      <View style={tw('flex flex-col justify-start gap-x-2 w-4/12')}>
        <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Client:</Text>
        <View style={tw('border-b border-gray-200')}>
          <Text style={tw('text-base text-gray-900 font-regular w-6/12')}>X</Text>
        </View>
      </View>
      <View style={tw('flex flex-col justify-start gap-x-2 w-4/12')}>
        <Text style={tw('text-base text-gray-900 font-bold w-6/12')}>Date:</Text>
        <View style={tw('border-b border-gray-200')}>
          <Text style={tw('text-base text-gray-900 font-regular w-6/12')}>X</Text>
        </View>
      </View>
    </View>
  )
}

export default ReportSignature
