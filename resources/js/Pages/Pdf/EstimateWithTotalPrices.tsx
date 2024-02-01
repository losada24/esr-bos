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

import ReportCompany from './ReportCompany'
import EstimateTotal from './EstimateTotal'
import { Notes } from './Notes'
import Pagination from './Pagination'
import SystemSummary from './SystemSummary'
import { GLASS_TYPE } from '@/Utils/constants'

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

const EstimateWithTotalPrices = ({ order, auth, company }: IndexOrderProps) => {
  const IS_IMPACT_GLASS = order.glass_type !== GLASS_TYPE.RUSH
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Estimate with Total Prices: ${order.name}`}
          actions={
            <PrintEstimateOrderButton id={order.id} status={order.status} user={auth.user} />
          }
      >
        <Head title={`Estimate with Total Prices: ${order.name}`} />
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
            <ReportCompany order={order} />
            {order?.products?.map((product, index) => {
              return <EstimateProduct product={product} key={index} showPrices={false} roles={[]} isImpactGlass={IS_IMPACT_GLASS} />
            })}
            <EstimateTotal order={order} />
            {order.notes !== null && (
              <Notes notes={order.notes ?? ''} />
            )}
            <SystemSummary order={order} />
            <ReportSignature />
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default EstimateWithTotalPrices
