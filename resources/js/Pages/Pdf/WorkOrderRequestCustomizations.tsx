import { type Product } from '@/types'
import { View, Text, Link } from '@react-pdf/renderer'
import React from 'react'
import { createTw } from 'react-pdf-tailwind'

const APP_URL = import.meta.env.VITE_APP_URL

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

const WorkOrderRequestCustomizations = ({ product }: { product: Product }) => {
  return (
    <View style={tw('flex flex-col mt-4 gap-4 border border-gray-200 p-3 mb-3')}>
      <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Request Customizations</Text>
      </View>
      {product.comments !== null && (
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-sm text-gray-900 font-bold w-2/12')}>Comments:</Text>
          <Text style={tw('text-sm text-gray-900 w-10/12')}>{product.comments}</Text>
        </View>
      )}
      {product.attachment !== null && (
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-sm text-gray-900 font-bold w-2/12')}>Attachment:</Text>
          <Link src={`${APP_URL}/storage/${product.attachment}`} style={tw('text-sm text-blui-900 w-10/12')}>Download Attachment</Link>
        </View>
      )}
    </View>
  )
}

export default WorkOrderRequestCustomizations
