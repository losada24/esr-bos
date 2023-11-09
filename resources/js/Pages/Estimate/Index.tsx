import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type Order, type PaginatorLink } from '@/types'
import Pagination from '@/Components/Pagination'
import EstimateFilter from './EstimateFilter'
import EyeIcon from '@/Components/Icons/EyeIcon'

type IndexOrderProps = PageProps & {
  estimates: {
    data: Order[]
    meta: {
      links: PaginatorLink[]
    }
  }
}

export default function Index ({ auth, estimates }: IndexOrderProps) {
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Estimate?')) {
      router.delete(route('estimate.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Estimates'
          actions={
            <Link
              className="btn btn-primary"
              href={route('estimate.create')}
            >
              <span>Create Estimate</span>
            </Link>
          }
      >
        <Head title="Estimate" />
        <EstimateFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Quote #</th>
                <th className="px-6 pt-5 pb-4">Project</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Created At</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {estimates.data.map(({ id, name, project_name, created_at }) => {
                return (
                  <tr
                    key={id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {id}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {project_name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {created_at?.toString()}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('estimate.show', id)}
                          className='mr-2'
                        >
                          <EyeIcon />
                        </Link>
                        <Link
                          href={route('estimate.edit', id)}
                        >
                          <EditIcon />
                        </Link>
                        <button
                          onClick={() => { destroy(id) }}
                        >
                          <DeleteIcon />
                        </button>
                    </td>
                  </tr>
                )
              })}
              {estimates.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={6}>
                    No Estimates found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={estimates.meta.links} />
      </AuthenticatedLayout>
  )
}
