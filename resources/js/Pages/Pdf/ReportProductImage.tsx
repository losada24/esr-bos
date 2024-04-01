import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Product } from '@/types'
import { PRODUCT_SYSTEMS, NO_CERTIFICATION_STANDARD_MESSAGE, CERTIFICATION_SQFT, CONFIG_XO, CONFIG_OX, EXTERNAL_PRODUCTS, CASEMENT_MULTIPOINT_CONFIG } from '@/Utils/constants'
import FixedWindowsTall from '../FixedWindows/FixedWindowsTall'
import FixedWindowsWider from '../FixedWindows/FixedWindowsWider'
import FixedWindowsSquared from '../FixedWindows/FixedWindowsSquared'
import SingleHungTall from '../SingleHunt/SingleHungTall'
import SingleHungWider from '../SingleHunt/SingleHungWider'
import SingleHungSquared from '../SingleHunt/SingleHungSquared'
import { getFrameJambs } from '@/Utils/SingleHung'
import { getVentBottomAndTop } from '@/Utils/HorizontalRoller'
import HorizontalRollerXOSquared from '../HorizontalRoller/HorizontalRollerXOSquared'
import MullionSGV from '../Mullion/MullionCSV'
import CasementMultipoint from '../Casement/CasementMultipoint'
import CasementMultipointXX from '../Casement/CasementMultipointXX'

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

const ReportProductImage = ({ product, isImpactGlass }: { product: Product, isImpactGlass?: boolean }) => {
  // TODO: Create helper function to check if product is certified
  return (
    <View>
      {product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS && ((product.width * product.height / 144) > CERTIFICATION_SQFT) && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.SINGLE_HUNG && (product.width > 53.13 || product.height > 74) && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{product.width} {NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER && (product.width > 74 || product.height > 53) && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {!isImpactGlass && (
        <View style={tw('flex flex-row mb-4 p-3')}>
          <Text style={tw('text-xs text-red-700 font-regular text-center')}>{NO_CERTIFICATION_STANDARD_MESSAGE}</Text>
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS && (
        <View style={tw('flex flex-row mb-4 p-3 justify-center')}>
          {product.height > product.width && <FixedWindowsTall width={product.width} height={product.height} svgHeight={250} svgWidth={250} /> }
          {product.width > product.height && <FixedWindowsWider width={product.width} height={product.height} svgHeight={250} svgWidth={250}/> }
          {product.height === product.width && <FixedWindowsSquared width={product.width} height={product.height} svgHeight={250} svgWidth={250} />}
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.SINGLE_HUNG && (
        <View style={tw('flex flex-row mb-4 p-3 justify-center')}>
          {product.height > product.width && <SingleHungTall width={product.width} height={product.height} svgHeight={250} svgWidth={250} heightOfMovementPart={getFrameJambs(product.height)} /> }
          {product.width > product.height && <SingleHungWider width={product.width} height={product.height} svgHeight={250} svgWidth={250} heightOfMovementPart={getFrameJambs(product.height)}/> }
          {product.height === product.width && <SingleHungSquared width={product.width} height={product.height} svgHeight={250} svgWidth={250} heightOfMovementPart={getFrameJambs(product.height)} />}
        </View>
      )}
      {product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER && (
        <View style={tw('flex flex-row mb-4 p-3 justify-center')}>
          {product.extras?.config === CONFIG_XO && <HorizontalRollerXOSquared width={product.width} height={product.height} svgHeight={250} svgWidth={250} widthtOfMovementPart={getVentBottomAndTop(product.width)} /> }
          {product.extras?.config === CONFIG_OX && <HorizontalRollerXOSquared width={product.width} height={product.height} svgHeight={250} svgWidth={250} widthtOfMovementPart={getVentBottomAndTop(product.width)} /> }
        </View>
      )}
      {product.system === EXTERNAL_PRODUCTS.MULLION && (
        <View style={tw('flex flex-row mb-4 p-3 justify-center')}>
          <MullionSGV width={product.width} height={product.height} svgHeight={250} svgWidth={250} />
        </View>
      )}
      {product.system === EXTERNAL_PRODUCTS.CASEMENT && (
        <View style={tw('flex flex-row mb-4 p-3 justify-center')}>
          {product.extras?.config === CASEMENT_MULTIPOINT_CONFIG
            ? <CasementMultipoint width={product.width} height={product.height} svgHeight={250} svgWidth={250} />
            : <CasementMultipointXX width={product.width} height={product.height} svgHeight={250} svgWidth={250} widthtOfMovementPart={product.width / 2} />
          }
        </View>
      )}
    </View>
  )
}

export default ReportProductImage
