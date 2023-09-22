import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head } from '@inertiajs/react'
import { type PageProps, type ReferralsByMonth, type ReferralsByStatusCount } from '@/types'
import ReferralsByStatus from './ReferralsByStatus'
import ReferralsByMonths from './ReferralsByMonths'

type IndexReferredProps = PageProps & {
  referralsByMonth: ReferralsByMonth
  referralsByStatus: ReferralsByStatusCount[]
}

export default function Dashboard ({ auth, referralsByMonth, referralsByStatus }: IndexReferredProps) {
  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle='Dashboard'
    >
      <Head title="Dashboard" />
      <div className="grid lg:grid-cols-3 gap-6 mb-6">
        <ReferralsByMonths referralsByMonth={referralsByMonth} />
        <ReferralsByStatus referralsByStatus={referralsByStatus}/>
      </div>

    </AuthenticatedLayout>
  )
}
