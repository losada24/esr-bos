import React from 'react'
import { Text } from '@react-pdf/renderer'
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

const Pagination = () => {
  return (
    <Text style={tw('text-right text-xs text-gray-500 mb-3')} render={({ pageNumber, totalPages }) => {
      return `Page ${pageNumber} of ${totalPages}`
    }} fixed />
  )
}

export default Pagination
