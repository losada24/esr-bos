import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type PageProps, type PaginatorLink } from '@/types'
import 'flatpickr/dist/flatpickr.css'
import EditIcon from '@/Components/Icons/EditIcon'
import Pagination from '@/Components/Pagination'
import EyeIcon from '@/Components/Icons/EyeIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'

interface Source {
  id: number
  name: string
  description: string
}

type IndexUserProps = PageProps & {
  sources: {
    data: Source[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, sources }: IndexUserProps) {
  // console.log(orders)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={'Sources'}
          actions={
            <Link
              className="btn btn-primary"
              href={route('source.create')}
            >
              <span>Create Source</span>
            </Link>
          }
      >
        <Head title={'Biweekly periods'} />
            <div className='table-responsive'>
          <table className="table-auto w-full">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Source Name</th>
              <th className="px-6 pt-5 pb-4">Description</th>
              <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {sources.data.map((source: Source, index) => {
                return (
                  <tr key={source.id}>
                    <td className="px-6 py-4 border-t">
                      {source.name}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {source.description}
                    </td>
                     <td className="border-t flex items-center px-6 py-4">
                    <Link
                          href={route('source.edit', source.id)}
                        >
                          <EditIcon />
                        </Link>
                        <button
                        >
                          <DeleteIcon />
                        </button>
                    </td>
                  </tr>
                )
              })}
              {sources.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Sources found.
                  </td>
                </tr>
              )}
            </tbody>
            <tfoot>
            </tfoot>
          </table>
          <Pagination links={sources.links} />
        </div>
      </AuthenticatedLayout>
  )
}
