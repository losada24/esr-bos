import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type Client, type PaginatorLink } from '@/types'
import Pagination from '@/Components/Pagination'
import ClientFilter from './ClientFilter'
import ExportIcon from '@/Components/Icons/ExportIcon'

type IndexClientProps = PageProps & {
  clients: {
    data: Client[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, clients }: IndexClientProps) {
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Client?')) {
      router.delete(route('client.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Clients'
          actions={
            <Link
              className="btn btn-primary"
              href={route('client.create')}
            >
              <span>Create Client</span>
            </Link>
          }
      >
        <Head title="Clients" />
        <ClientFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Email</th>
                <th className="px-6 pt-5 pb-4">Phone</th>
                <th className="px-6 pt-5 pb-4">Address</th>
                <th className="px-6 pt-5 pb-4">Updated At</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {clients.data.map(({ id, name, email, phone, client_address, updated_at }) => {
                return (
                  <tr
                    key={id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {email}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {phone}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {client_address.map(({ id, address }) => {
                        return (
                          <div key={id} className="flex flex-col">
                            <div className="flex items-center justify-start gap-3">
                              <span>{address}</span> <a href={route('client.document', id)}><ExportIcon /></a>
                            </div>
                          </div>
                        )
                      })}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {updated_at.toString()}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <button
                          onClick={() => { destroy(id) }}
                        >
                          <DeleteIcon />
                        </button>
                    </td>
                  </tr>
                )
              })}
              {clients.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Clients found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={clients.links} />
      </AuthenticatedLayout>
  )
}
