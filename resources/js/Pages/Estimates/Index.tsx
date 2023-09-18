import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

export default function Dashboard ({ auth }: PageProps) {
  return (
    <AuthenticatedLayout
      user={auth.user}
      pageTitle='Dashboard'
    >
      <div>
        <div className="panel mb-5">
          <div className="mb-4 flex items-center sm:flex-row flex-col sm:justify-between justify-center">
            <div className="sm:mb-0 mb-4">
              <div className="text-lg font-semibold ltr:sm:text-left rtl:sm:text-right text-center">Calendar</div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
