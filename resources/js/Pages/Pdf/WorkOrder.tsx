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
import VisualId from './VisualId'
import Pagination from './Pagination'
import { getNumberWithFraction } from '@/Utils/numbers'
import SystemSummary from './SystemSummary'
import Muntin from './Muntin'
import { PRODUCT_SYSTEMS } from '@/Utils/constants'

type IndexOrderProps = PageProps & {
  order: Order
  totalStickers: number
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

const WorkOrder = ({ order, auth }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Work Order ${order.name}`}
          actions={
            <PrintButton id={order.id} />
          }
      >
        <Head title={`Work Order ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <POHeaders order={order} documentTitle='Work Order' />
            <View style={tw('mt-2 mb-4 px-3 pb-3 border border-gray-200')}>
              <SystemSummary order={order} />
            </View>
            {order?.products?.map((product, index) => {
              return <Fragment key={index}>
                <View style={tw('flex flex-row gap-4 justify-between bg-gray-200 mb-3 p-3')}>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Mark:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{product.line_item_name}</Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Qty:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{product.qty}</Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>System Product:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>
                      {product.system} ({product.frame_color}) {product?.extras?.config}
                      {(product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER) && (
                          ` | Handle: ${product.extras?.handle}`
                      )}
                    </Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Size:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{getNumberWithFraction(product.width)} x {getNumberWithFraction(product.height)}</Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Visual ID:</Text>
                    <VisualId index={product.visual_id ?? 0} />
                  </View>
                </View>
                {product.extras?.muntin_panels && (
                  <Muntin product={product} />
                )}
                <CuttingListItems cuttingList={product?.cutting_list ?? []} productId={product.id} />
              </Fragment>
            })}
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default WorkOrder
