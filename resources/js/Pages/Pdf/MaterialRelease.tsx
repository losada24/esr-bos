import React, { Fragment } from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page, Text, View } from '@react-pdf/renderer'
import { type PageProps, type Order } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import PrintButton from '@/Pages/Order/PrintButton'
import { FOOT, UNIT, SQFT } from '@/Utils/constants'
import POHeaders from './POHeaders'
import { getNumberWithFraction } from '@/Utils/numbers'
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

const MaterialRelease = ({ order, auth }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Material Release: ${order.name}`}
          actions={
            <PrintButton id={order.id} />
          }
      >
        <Head title={`Material Release: ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <POHeaders order={order} documentTitle='Material Release' />
            <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-4/12')}>Material</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-4/12')}>Notes</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-4/12 text-right')}>Total Amount</Text>
            </View>
            {order?.materialConsumption?.map((product, index) => {
              return <View key={index} style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
                <Text style={tw('text-xs text-gray-900 font-regular w-4/12')}>{product.name}</Text>
                <Text style={tw('text-xs text-gray-900 font-regular w-4/12')}>{product.notes}</Text>
                {product.unit_of_measurement === UNIT && (
                  <Text style={tw('text-xs text-gray-900 font-regular w-4/12 text-right')}>{product.amount} {UNIT}</Text>
                )}
                {product.unit_of_measurement === SQFT && (
                  <Text style={tw('text-xs text-gray-900 font-regular w-4/12 text-right')}>{product.amount / 144} {SQFT}</Text>
                )}
                {product.unit_of_measurement === FOOT && (
                  <Text style={tw('text-xs text-gray-900 font-regular w-4/12 text-right')}>
                    {`${getNumberWithFraction(product.amount)} ${product.unit_of_measurement}`}
                  </Text>
                )}
              </View>
            })}
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default MaterialRelease
