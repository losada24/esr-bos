import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head } from '@inertiajs/react'
import { type PageProps, type ReferralsByMonth, type ReferralsByStatusCount } from '@/types'

type IndexReferredProps = PageProps & {
  referralsByMonth: ReferralsByMonth
  referralsByStatus: ReferralsByStatusCount[]
}

export default function Dashboard ({ auth }: IndexReferredProps) {
  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle='Dashboard'
    >
      <Head title="Dashboard" />
      <div className="grid lg:grid-cols-3 gap-6 mb-6">
      { /* <ReferralsByMonths referralsByMonth={referralsByMonth} />
          <ReferralsByStatus referralsByStatus={referralsByStatus}/>
        </div>
        <CopyAddressToClipboard link={route('referred.create', { reference_code: auth.user.reference_code })} />
      */ }
      </div>
    </AuthenticatedLayout>
  )
}
