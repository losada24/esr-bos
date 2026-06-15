import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type Client, type PaginatorLink, type Role } from '@/types'
import Pagination from '@/Components/Pagination'
import ClientFilter from './ClientFilter'
import ExportIcon from '@/Components/Icons/ExportIcon'
import { isFrontdeskEsr } from '@/Utils/user'

type IndexClientProps = PageProps & {
  clients: {
    data: Client[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, clients }: IndexClientProps) {
  const roleNames = Array.isArray(auth?.user?.roles)
    ? auth.user.roles.map((role: Role) => role.name)
    : []
  const canManageClients = !isFrontdeskEsr(roleNames)

  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Client?')) {
      router.delete(route('client.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Contacts'
          actions={
            canManageClients
              ? (
                <Link
                  className="btn btn-primary"
                  href={route('client.create')}
                >
                  <span>Create Contact</span>
                </Link>
                )
              : null
          }
      >
        <Head title="Contacts" />
        <ClientFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Email</th>
                <th className="px-6 pt-5 pb-4">Phone</th>
                <th className="px-6 pt-5 pb-4">Company</th>
                <th className="px-6 pt-5 pb-4">Address</th>
                <th className="px-6 pt-5 pb-4">Owner</th>
                <th className="px-6 pt-5 pb-4">Created By</th>
                <th className="px-6 pt-5 pb-4">Updated At</th>
                {canManageClients && <th className="px-6 pt-5 pb-4 w-14">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {clients.data.map(({ id, name, email, phone, company_contact, client_address, updated_at, user, created_by_user }) => {
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
                      {company_contact?.name ?? '-'}
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
                      {user?.name ?? '-'}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {created_by_user?.name ?? user?.name ?? '-'}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {updated_at.toString()}
                    </td>
                    {canManageClients && (
                      <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('client.edit', id)}
                        >
                          <EditIcon />
                        </Link>
                        <button
                          onClick={() => { destroy(id) }}
                        >
                          <DeleteIcon />
                        </button>
                      </td>
                    )}
                  </tr>
                )
              })}
              {clients.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={canManageClients ? 9 : 8}>
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
