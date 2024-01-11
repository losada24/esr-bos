import React from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page, Text } from '@react-pdf/renderer'
import { type PageProps, type Order } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import ReportHeader from './ReportHeader'
import ReportProduct from './ReportProduct'
import ReportTotal from './ReportTotal'
import ReportSignature from './ReportSignature'
import PrintEstimateOrderButton from '@/Pages/Pdf/PrintEstimateOrderButton'

import logo from '../../../assets/images/logo-reylosglass.png'
import ReportCompany from './ReportCompany'
import { Notes } from './Notes'

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

const Report = ({ order, auth }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Report: ${order.name}`}
          actions={
            <PrintEstimateOrderButton id={order.id} status={order.status} />
          }
      >
        <Head title={`Report: ${order.name}`} />
        <PrintLayout>
          <Page size="A4" style={tw('p-6 font-regular')}>
            <ReportHeader data={{
              id: order.id,
              address: '',
              featured_image: '',
              phone_number: ''
            }} logo={logo}/>
            <ReportCompany order={order} isForClient={false} />
            {order?.products?.map((product, index) => {
              return <ReportProduct product={product} key={index} />
            })}
            <ReportTotal order={order} />
            {order.notes !== null && (
              <Notes notes={order.notes ?? ''} />
            )}
            <ReportSignature />
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default Report
