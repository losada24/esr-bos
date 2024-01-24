import React from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page } from '@react-pdf/renderer'
import { type PageProps, type Order, type Company } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import ReportHeader from './ReportHeader'
import EstimateProduct from './ReportProduct'
import ReportSignature from './ReportSignature'
import PrintEstimateOrderButton from '@/Pages/Pdf/PrintEstimateOrderButton'

import logo from '../../../assets/images/logo-reylosglass.png'
import ReportCompany from './ReportCompany'
import { Notes } from './Notes'
import Pagination from './Pagination'

type IndexOrderProps = PageProps & {
  order: Order
  company: Company
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

const EstimateWithoutPrices = ({ order, auth, company }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Estimate without Prices: ${order.name}`}
          actions={
            <PrintEstimateOrderButton id={order.id} status={order.status} user={auth.user} />
          }
      >
        <Head title={`Estimate without Prices: ${order.name}`} />
        <PrintLayout>
          <Page size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <ReportHeader data={{
              id: order.id,
              address: company.address,
              featured_image: company.featured_image,
              phone_number: company.phone_number,
              email: company.email
            }} />
            <ReportCompany order={order} isForClient={true} />
            {order?.products?.map((product, index) => {
              return <EstimateProduct product={product} key={index} showPrices={false} roles={[]} />
            })}
            {order.notes !== null && (
              <Notes notes={order.notes ?? ''} />
            )}
            <ReportSignature />
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default EstimateWithoutPrices
