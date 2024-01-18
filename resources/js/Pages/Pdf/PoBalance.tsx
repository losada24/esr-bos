import React, { Fragment } from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page, Text, View } from '@react-pdf/renderer'
import { type PageProps, type Order } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import PrintButton from '@/Pages/Order/PrintButton'
import POHeaders from './POHeaders'
import { PO_BALANCE, PO_TITLES } from '@/Utils/constants'
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

const PoBalance = ({ order, auth }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`PO Balance ${order.name}`}
          actions={
            <PrintButton id={order.id} />
          }
      >
        <Head title={`PO Balance ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <POHeaders order={order} poType={PO_BALANCE} documentTitle={PO_TITLES[PO_BALANCE]} />
            {order?.balances?.map((balance, index) => {
              return <Fragment key={index}>
                <View style={tw('flex flex-row gap-4 justify-between mb-3 pt-3 pb-3 border-b border-t border-gray-200')}>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Part No:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{balance.part_no}</Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Size:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{balance.size}</Text>
                  </View>
                  <View style={tw('flex flex-row justify-start items-center gap-3')}>
                    <Text style={tw('text-xs text-white-dark text-black')}>Qty:</Text>
                    <Text style={tw('text-xs text-white-dark dark:text-gray-500')}>{balance.qty * 2}</Text>
                  </View>
                </View>
              </Fragment>
            })}
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default PoBalance
