import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head } from '@inertiajs/react'
import { type PageProps } from '@/types'

export default function Dashboard ({ auth }: PageProps) {
  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle='Dashboard'
    >
      <Head title="Dashboard" />
      <div className="grid lg:grid-cols-3 gap-6 mb-6">
        
      </div>
    </AuthenticatedLayout>
  )
}
