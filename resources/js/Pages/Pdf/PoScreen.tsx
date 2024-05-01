import React, { Fragment } from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page, Text, View } from '@react-pdf/renderer'
import { type PageProps, type Order } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import PrintButton from '@/Pages/Order/PrintButton'
import POHeaders from './POHeaders'
import ScreenPOItems from './ScreenPOItems'
import { getNumberWithFraction } from '@/Utils/numbers'
import { PO_SCREEN, PO_TITLES } from '@/Utils/constants'
import Pagination from './Pagination'

type IndexOrderProps = PageProps & {
  order: Order
}

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

const PoScreen = ({ order, auth }: IndexOrderProps) => {
  let screenTotal = 0
  order?.products?.forEach((product) => {
    if (product.extras?.screen) {
      screenTotal += product.qty
    }
  })

  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`PO Screen ${order.name}`}
          actions={
            <PrintButton id={order.id} />
          }
      >
        <Head title={`PO Screen ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <POHeaders order={order} poType={PO_SCREEN} documentTitle={PO_TITLES[PO_SCREEN]} />
            {order?.products?.map((product, index) => {
              return <Fragment key={index}>
                <View style={tw('flex flex-row gap-4 justify-between bg-gray-200 mb-3 p-3')}>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Mark:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{product.line_item_name}</Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>System Product:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{product.system} ({product.frame_color}) {product?.extras?.config}</Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Size:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{getNumberWithFraction(product.width) } x {getNumberWithFraction(product.height)}</Text>
                  </View>
                </View>
                <ScreenPOItems cuttingList={product?.cutting_list ?? []} productId={product.id} system={product.system} />
              </Fragment>
            })}
            <View style={tw('mt-2 mb-4 px-3 pb-3 border border-gray-200')}>
              <View style={tw('flex flex-row mt-4 gap-4 justify-end')}>
                <View style={tw('w-6/12')}>
                  <View style={tw('flex flex-row justify-start gap-x-3')}>
                    <Text style={tw('text-base text-gray-900 font-bold w-6/12 text-right')}>Total Screens</Text>
                    <Text style={tw('text-base text-gray-900 font-regular w-3/12 text-right font-bold')}>{screenTotal}</Text>
                  </View>
                </View>
              </View>
            </View>
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default PoScreen
