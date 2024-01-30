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

import ReportCompany from './ReportCompany'
import Pagination from './Pagination'
import logo from '../../../assets/images/logo-reylosglass.png'

type IndexOrderProps = PageProps & {
  order: Order
}

const COMPANY_ADDRESS = import.meta.env.VITE_COMPANY_ADDRESS
const COMPANY_PHONE = import.meta.env.VITE_COMPANY_PHONE
const COMPANY_EMAIL = import.meta.env.VITE_COMPANY_EMAIL

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
              address: COMPANY_ADDRESS,
              featured_image: logo,
              phone_number: COMPANY_PHONE,
              email: COMPANY_EMAIL
            }} documentTitle={'Shipping #'} />
            <ReportCompany order={order} />
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
