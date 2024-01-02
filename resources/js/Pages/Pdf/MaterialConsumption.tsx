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

const MaterialConsumption = ({ order, auth }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Material Consumption: ${order.name}`}
          actions={
            <PrintButton id={order.id} />
          }
      >
        <Head title={`Material Consumption: ${order.name}`} />
        <PrintLayout>
          <Page size="A4" style={tw('p-6 font-regular')}>
            <POHeaders order={order} />
            <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12')}>Material</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12')}>Notes</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12 text-right')}>Total Amount</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12 text-right')}>Wharehouse Delivery</Text>
            </View>
            {order?.materialConsumption?.map((product, index) => {
              return <View key={index} style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
                <Text style={tw('text-xs text-gray-900 font-regular w-3/12')}>{product.name}</Text>
                <Text style={tw('text-xs text-gray-900 font-regular w-3/12')}>{product.notes}</Text>
                {product.unit_of_measurement === UNIT && (
                  <Text style={tw('text-xs text-gray-900 font-regular w-6/12 text-right')}>{product.amount} {UNIT}</Text>
                )}
                {product.unit_of_measurement === SQFT && (
                  <Text style={tw('text-xs text-gray-900 font-regular w-6/12 text-right')}>{product.amount / 144} {SQFT}</Text>
                )}
                {product.unit_of_measurement === FOOT && (
                  <>
                    <Text style={tw('text-xs text-gray-900 font-regular w-3/12 text-right')}>
                      {`${getNumberWithFraction(product.amount)} ${product.unit_of_measurement}`}
                    </Text>
                    <Text style={tw('text-xs text-gray-900 font-regular w-3/12 text-right')}>
                      {`${Math.ceil((product.amount) / product.storage_measure)}(pcs)`}
                    </Text>
                  </>
                )}
              </View>
            })}
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default MaterialConsumption
