import React from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page } from '@react-pdf/renderer'
import { type PageProps, type Order } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import ReportHeader from './ReportHeader'
import DeliveryProduct from './DeliveryProduct'
import ReportSignature from './ReportSignature'

import logo from '../../../assets/images/logo-reylosglass.png'
import ReportCompany from './ReportCompany'
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

const Delivery = ({ order, auth }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Shipping: ${order.name}`}
      >
        <Head title={`Shipping: ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <ReportHeader data={{
              id: order.id,
              address: '',
              featured_image: '',
              phone_number: ''
            }} />
            <ReportCompany order={order} isForClient={false} />
            {order?.products?.map((product, index) => {
              return <DeliveryProduct product={product} key={index} />
            })}
            <ReportSignature />
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default Delivery
