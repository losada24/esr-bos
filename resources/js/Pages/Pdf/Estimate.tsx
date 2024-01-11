import React from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page } from '@react-pdf/renderer'
import { type PageProps, type Order, type Company } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import ReportHeader from './ReportHeader'
import ReportProduct from './ReportProduct'
import ReportSignature from './ReportSignature'
import PrintEstimateOrderButton from '@/Pages/Pdf/PrintEstimateOrderButton'
import LeadTimeAlert from './LeadTimeAlert'

import logo from '../../../assets/images/logo-reylosglass.png'
import ReportCompany from './ReportCompany'
import EstimateTotal from './EstimateTotal'
import { Notes } from './Notes'

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

const Estimate = ({ order, auth, company }: IndexOrderProps) => {
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Estimate: ${order.name}`}
          actions={
            <PrintEstimateOrderButton id={order.id} status={order.status} />
          }
      >
        <Head title={`Estimate: ${order.name}`} />
        <PrintLayout>
          <Page size="A4" style={tw('p-6 font-regular')}>
            <ReportHeader data={{
              id: order.id,
              address: company.address,
              featured_image: company.featured_image,
              phone_number: company.phone_number,
              email: company.email
            }} logo={logo} isForClient={true} />
            <ReportCompany order={order} isForClient={true} />
            {order?.products?.map((product, index) => {
              return <ReportProduct product={product} key={index} />
            })}
            <LeadTimeAlert glass_type={order.glass_type} />
            <EstimateTotal order={order} />
            {order.notes !== null && (
              <Notes notes={order.notes ?? ''} />
            )}
            <ReportSignature />
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default Estimate
