import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head } from '@inertiajs/react'
import { type PageProps } from '@/types'
import EstimatesByMonths from './EstimatesByMonths'
import OrdersByStatus from './OrdersByStatus'

export interface EstimatesByMonth {
  months: string[]
  counts: number[]
  year: number
}

export interface OrdersByStatusCount {
  status: string
  count: number
}

type IndexReportsProps = PageProps & {
  estimatesByMonth: EstimatesByMonth
  ordersByStatus: OrdersByStatusCount[]
}

export default function Dashboard ({ auth, estimatesByMonth, ordersByStatus }: IndexReportsProps) {
  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle='Dashboard'
    >
      <Head title="Dashboard" />
      <div className="grid lg:grid-cols-3 gap-6 mb-6">
      <EstimatesByMonths estimatesByMonth={estimatesByMonth} />
        <OrdersByStatus ordersByStatus={ordersByStatus} />
       { /* <ReferralsByStatus referralsByStatus={referralsByStatus}/> <CopyAddressToClipboard link={route('referred.create', { reference_code: auth.user.reference_code })} />
        */ }
      </div>
    </AuthenticatedLayout>
  )
}
