import React from 'react'
import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { type Order } from '@/types'
import { getSubTotalPriceByRole, formatPrice, getGrandTotalByRole, getOrderPromotion } from '@/Utils/price'
import { isAccountManager, isAdmin, isDealer, isSubDealer } from '@/Utils/user'

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

const ReportTotal = ({ order, roles }: { order: Order, roles: string[] }) => {
  const SUB_TOTAL: number = Number(getSubTotalPriceByRole(order, roles) ?? 0)
  const RG_OTHER_PRICE: number = Number(order?.rg_other_price ?? 0)
  const IS_DEALER = isDealer(roles)
  const IS_SUBDEALER = isSubDealer(roles)
  const IS_ACCOUNT_MANAGER = isAccountManager(roles)
  const IS_ADMIN = isAdmin(roles)

  return (
    <View style={tw('flex flex-row mt-4')}>
      <View style={tw('w-6/12')}>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Date:</Text>
          <Text style={tw('text-base text-gray-900 font-regular')}>{order.created_at?.toString()}</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Estimate:</Text>
          <Text style={tw('text-base text-gray-900 font-regular')}>{order.user?.name}</Text>
        </View>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold')}>Client:</Text>
          <Text style={tw('text-base text-gray-900 font-regular')}>{order.user?.company?.name}</Text>
        </View>
      </View>
      <View style={tw('w-6/12')}>
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>Sub Total:</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-6/12 text-left')}>{`${formatPrice(SUB_TOTAL)}`}</Text>
        </View>
        {(order?.rg_other_price ?? 0) > 0 && (
          <View style={tw('flex flex-row justify-start gap-x-3')}>
            <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>RG Other:</Text>
            <Text style={tw('text-base text-gray-900 font-regular w-6/12 text-left')}>{`${formatPrice(RG_OTHER_PRICE)}`}</Text>
          </View>
        )}
        {((IS_DEALER || IS_SUBDEALER || IS_ACCOUNT_MANAGER || IS_ADMIN) && (order?.company_promotion ?? 0) > 0) && (
          <View style={tw('flex flex-row justify-start gap-x-3')}>
            <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>Discount:</Text>
            <Text style={tw('text-base text-gray-900 font-regular w-6/12 text-left')}>{formatPrice(getOrderPromotion(order))}</Text>
          </View>
        )}
        {((IS_DEALER || IS_SUBDEALER || IS_ACCOUNT_MANAGER || IS_ADMIN) && (order?.subdealer_other ?? 0) > 0) && (
          <View style={tw('flex flex-row justify-start gap-x-3')}>
            <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>Sub Dealer Other:</Text>
            <Text style={tw('text-base text-gray-900 font-regular w-6/12 text-left')}>{`${formatPrice(order?.subdealer_other ?? 0)}`}</Text>
          </View>
        )}
        <View style={tw('flex flex-row justify-start gap-x-3')}>
          <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>Total:</Text>
          <Text style={tw('text-base text-gray-900 font-regular w-6/12 text-left')}>{`${formatPrice(getGrandTotalByRole(order, roles))}`}</Text>
        </View>
      </View>
    </View>
  )
}

export default ReportTotal
