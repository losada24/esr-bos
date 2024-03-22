import React, { Fragment } from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page, Text, View } from '@react-pdf/renderer'
import { type PageProps, type Order } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import PrintButton from '@/Pages/Order/PrintButton'
import POHeaders from './POHeaders'
import CuttingListItems from './CuttingListItems'
import { PO_EXTERNAL_PRODUCTS, PO_GLASS, PO_TITLES } from '@/Utils/constants'
import Pagination from './Pagination'
import { getNumberWithFraction } from '@/Utils/numbers'

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

const POExternalProducts = ({ order, auth }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`PO Glass ${order.name}`}
          actions={
            <PrintButton id={order.id} />
          }
      >
        <Head title={`PO Glass ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <POHeaders order={order} poType={PO_EXTERNAL_PRODUCTS} documentTitle={PO_TITLES[PO_EXTERNAL_PRODUCTS]} />
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
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{getNumberWithFraction(product.width)} x {getNumberWithFraction(product.height)}</Text>
                  </View>
                </View>
              </Fragment>
            })}
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default POExternalProducts
