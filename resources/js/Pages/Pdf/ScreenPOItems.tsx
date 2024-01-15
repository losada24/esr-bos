import React, { Fragment } from 'react'
import { type CuttingList } from '@/types'
import { createTw } from 'react-pdf-tailwind'
import { View, Text, Image } from '@react-pdf/renderer'
import { PRODUCT_SYSTEMS } from '@/Utils/constants'

import sh_screen_image from '../../../assets/images/sh_screen_image.jpg'
import hr_screen_image from '../../../assets/images/hr_screen_image.jpg'

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

const ScreenPOItems = ({ cuttingList, productId, system }: { cuttingList: CuttingList[], productId: number, system: string }) => {
  return (
    <>
      <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-4/12')}>Part</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-2/12')}>Image</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12 text-right')}>Qty</Text>
        <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12 text-right')}>Size</Text>
      </View>
      {cuttingList.map((productOrderField, index) => {
        return <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')} key={`${productId}${index}`}>
          <Text style={tw('text-xs text-gray-900 font-regular w-4/12')}>{productOrderField.part}</Text>
          <View style={tw('text-xs text-white-dark text-black font-bold w-2/12')}>
            {PRODUCT_SYSTEMS.SINGLE_HUNG === system && <Image src={sh_screen_image} style={tw('w-10 h-10')} />}
            {PRODUCT_SYSTEMS.HORIZONTAL_ROLLER === system && <Image src={hr_screen_image} style={tw('w-10 h-10')} />}
          </View>
          <Text style={tw('text-xs text-gray-900 font-regular w-3/12 text-right')}>{productOrderField.qty}</Text>
          <Text style={tw('text-xs text-gray-900 font-regular w-3/12 text-right')}>{productOrderField.size}</Text>
        </View>
      })}
    </>
  )
}

export default ScreenPOItems
