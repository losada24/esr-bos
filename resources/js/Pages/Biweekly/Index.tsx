import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type PageProps, type PaginatorLink } from '@/types'
import 'flatpickr/dist/flatpickr.css'
import EditIcon from '@/Components/Icons/EditIcon'
import Pagination from '@/Components/Pagination'

interface BiweeklyInstaller {
  id: number
  start_biweekly_period: string
  end_biweekly_period: string
}

type IndexUserProps = PageProps & {
  biweeklies: {
    data: BiweeklyInstaller[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, biweeklies }: IndexUserProps) {
  // console.log(orders)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={'Biweekly periods'}
          actions={
            <Link
              className="btn btn-primary"
              href={route('biweekly.create')}
            >
              <span>Create Biweekly</span>
            </Link>
          }
      >
        <Head title={'Biweekly periods'} />
            <div className='table-responsive'>
          <table className="table-auto w-full">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Biweekly Periods</th>
              <th className="px-6 pt-5 pb-4">Year</th>
              <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {biweeklies.data.map((biweekly: BiweeklyInstaller, index) => {
                const start_biweekly_period = new Date(biweekly.start_biweekly_period).toLocaleDateString('en-US', {
                  timeZone: 'UTC', // ✅ Corrige el desfase
                  day: '2-digit',
                  month: 'long'
                })
                const end_biweekly_period = new Date(biweekly.end_biweekly_period).toLocaleDateString('en-US', {
                  timeZone: 'UTC', // ✅ Corrige el desfase
                  day: '2-digit',
                  month: 'long'
                })
                const year = new Date(biweekly.start_biweekly_period).getFullYear()
                return (
                  <tr key={biweekly.id}>
                     <td className="px-6 py-4 border-t">
                     {start_biweekly_period} to {end_biweekly_period}
                    </td>
                    <td className="px-6 py-4 border-t ">
                    {year}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                      <Link
                        href={route('biweekly.edit', { id: biweekly.id })}
                        title='Edit Biweekly'
                         className='mr-2'
                      >
                        <EditIcon />
                      </Link>
                    </td>
                  </tr>
                )
              })}
              {biweeklies.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Biweeklies found.
                  </td>
                </tr>
              )}
            </tbody>
            <tfoot>
            </tfoot>
          </table>
          <Pagination links={biweeklies.links} />
        </div>
      </AuthenticatedLayout>
  )
}
