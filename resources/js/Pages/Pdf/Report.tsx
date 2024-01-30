import React from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page } from '@react-pdf/renderer'
import { type PageProps, type Order, type Company } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import ReportHeader from './ReportHeader'
import ReportProduct from './ReportProduct'
import ReportTotal from './ReportTotal'
import ReportSignature from './ReportSignature'
import PrintEstimateOrderButton from '@/Pages/Pdf/PrintEstimateOrderButton'
import LeadTimeAlert from './LeadTimeAlert'
import { isSubDealer } from '@/Utils/user'

import logo from '../../../assets/images/logo-reylosglass.png'
import ReportCompany from './ReportCompany'
import { Notes } from './Notes'
import Pagination from './Pagination'
import { ROLES } from '@/Utils/constants'
import SystemSummary from './SystemSummary'

const COMPANY_ADDRESS = import.meta.env.VITE_COMPANY_ADDRESS
const COMPANY_PHONE = import.meta.env.VITE_COMPANY_PHONE
const COMPANY_EMAIL = import.meta.env.VITE_COMPANY_EMAIL

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

const Report = ({ order, auth, company }: IndexOrderProps) => {
  const IS_SUBDEALER = isSubDealer(auth.user.roles.map((role) => role.name))
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Report: ${order.name}`}
          actions={
            <PrintEstimateOrderButton id={order.id} status={order.status} user={auth.user} />
          }
      >
        <Head title={`Report: ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <ReportHeader data={{
              id: order.id,
              address: IS_SUBDEALER ? company.address : COMPANY_ADDRESS,
              featured_image: IS_SUBDEALER ? company.featured_image : logo,
              phone_number: IS_SUBDEALER ? company.phone_number : COMPANY_PHONE,
              email: IS_SUBDEALER ? company.email : COMPANY_EMAIL
            }} />
            <ReportCompany order={order} />
            {order?.products?.map((product, index) => {
              return <ReportProduct product={product} key={index} showPrices={true} roles={[ROLES.DEALER]} />
            })}
            <LeadTimeAlert glass_type={order.glass_type} />
            <ReportTotal order={order} roles={[ROLES.DEALER]} />
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

export default Report
