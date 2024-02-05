import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Product, type Order } from '@/types'
import ReportProductImage from '@/Pages/Pdf/ReportProductImage'
import { PRODUCT_SYSTEMS } from '@/Utils/constants'
import { getNumberWithFraction } from '@/Utils/numbers'
import { formatPrice, getTotalPriceByRole, getUnitPriceByRole } from '@/Utils/price'
import { getProductCertification } from '@/Utils/products'

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

const EstimateProduct = ({ product, showPrices, isImpactGlass }: { product: Product, showPrices: boolean, isImpactGlass?: boolean }) => {
  return (
    <View style={tw('flex flex-col mb-4 border border-gray-200')}>
      <View style={tw('flex flex-col bg-gray-200')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3')}>
          <View style={tw(`flex flex-row justify-start items-center gap-3 ${showPrices ? 'w-2/12' : 'w-3/12'}`)}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Mark</Text>
          </View>
          <View style={tw(`flex flex-row justify-start items-center gap-3 ${showPrices ? 'w-1/12' : 'w-2/12'}`)}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Qty</Text>
          </View>
          <View style={tw(`flex flex-row justify-start items-center gap-3 w-2/12 ${showPrices ? 'w-2/12' : 'w-3/12'}`)}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>System</Text>
          </View>
          <View style={tw(`flex flex-row justify-start items-center gap-3 ${showPrices ? 'w-2/12' : 'w-3/12'}`)}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Size</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Frame</Text>
          </View>
          {showPrices && (
            <>
              <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12 text-right')}>
                <Text style={tw('text-xs text-gray-900 font-bold')}>Unit Price</Text>
              </View>
              <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12 text-right')}>
                <Text style={tw('text-xs text-gray-900 font-bold')}>Total Price</Text>
              </View>
            </>
          )}
        </View>
      </View>
      <View style={tw('flex flex-col')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3')}>
          <View style={tw(`flex flex-row justify-start items-center gap-3 ${showPrices ? 'w-2/12' : 'w-3/12'}`)}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.line_item_name}</Text>
          </View>
          <View style={tw(`flex flex-row justify-start items-center gap-3 ${showPrices ? 'w-1/12' : 'w-2/12'}`)}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.qty}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-2/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>
              {product.system}({product.extras?.config})
              {(product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER || product.system === PRODUCT_SYSTEMS.SINGLE_HUNG) && product.extras?.screen ? ' with Screen' : ''}
            </Text>
          </View>
          <View style={tw(`flex flex-row justify-start items-center gap-3 ${showPrices ? 'w-2/12' : 'w-3/12'}`)}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{getNumberWithFraction(product.width)} x {getNumberWithFraction(product.height)}</Text>
          </View>
          <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12')}>
            <Text style={tw('text-xs text-gray-900 font-regular')}>{product.frame_color}</Text>
          </View>
          {showPrices && (
            <>
              <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12')}>
                <Text style={tw('text-xs text-gray-900 font-regular text-right')}>{formatPrice(getUnitPriceByRole(product, []))}</Text>
              </View>
              <View style={tw('flex flex-row justify-start items-center gap-3 w-1/12')}>
                <Text style={tw('text-xs text-gray-900 font-regular text-right')}>{formatPrice(getTotalPriceByRole(product, []))}</Text>
              </View>
            </>
          )}
        </View>
      </View>
      <View>
        <ReportProductImage product={product} isImpactGlass={isImpactGlass} />
      </View>
      <View style={tw('flex flex-row gap-4 justify-start bg-gray-200')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3 w-8/12')}>
            <Text style={tw('text-xs text-gray-900 font-bold')}>Glass Type:</Text>
        </View>
        {isImpactGlass && (
          <>
            <View style={tw('flex flex-row gap-4 justify-start p-3 w-2/12')}>
                <Text style={tw('text-xs text-gray-900 font-bold')}>Pressure:</Text>
            </View>
            <View style={tw('flex flex-row gap-4 justify-start p-3 w-2/12')}>
                <Text style={tw('text-xs text-gray-900 font-bold')}>Certification:</Text>
            </View>
          </>
        )}
      </View>

      <View style={tw('flex flex-row gap-4 justify-start')}>
        <View style={tw('flex flex-row gap-4 justify-start p-3 w-8/12')}>
          <Text style={tw('text-xs text-gray-900 font-regular')}>{product.glass_type}</Text>
        </View>
        {isImpactGlass && (
          <>
            <View style={tw('flex flex-row gap-4 justify-start p-3 w-2/12')}>
              <Text style={tw('text-xs font-regular text-green-700')}>
                {product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER || product.system === PRODUCT_SYSTEMS.SINGLE_HUNG ? '+70/-70 psf' : '+75/-75 psf'}
              </Text>
            </View>
            <View style={tw('flex flex-row gap-4 justify-start p-3 w-2/12')}>
              <Text style={tw('text-xs text-gray-900 font-regular')}>
                {getProductCertification(product.system)}
              </Text>
            </View>
          </>
        )}
      </View>
    </View>
  )
}

export default EstimateProduct
