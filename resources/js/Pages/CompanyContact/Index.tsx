import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type CompanyContact, type PaginatorLink, type Role } from '@/types'
import Pagination from '@/Components/Pagination'
import ClientFilter from './ClientFilter'
import { isFrontdeskEsr } from '@/Utils/user'

type IndexClientProps = PageProps & {
  company_contacts: {
    data: CompanyContact[]
    links: PaginatorLink[]
  }

}
 
export default function Index ({ auth, company_contacts }: IndexClientProps) {
  console.log(company_contacts)
  const roleNames = Array.isArray(auth?.user?.roles)
    ? auth.user.roles.map((role: Role) => role.name)
    : []
  const canManageCompanies = !isFrontdeskEsr(roleNames)

  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Company?')) {
      router.delete(route('company_contact.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Company'
          actions={
            canManageCompanies
              ? (
                <Link
                  className="btn btn-primary"
                  href={route('company_contact.create')}
                >
                  <span>Create Company</span>
                </Link>
                )
              : null
          }
      >
        <Head title="Companies" />
        <ClientFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Email</th>
                <th className="px-6 pt-5 pb-4">Phone</th>
                <th className="px-6 pt-5 pb-4">Website</th>
                <th className="px-6 pt-5 pb-4">Clients</th>
                {canManageCompanies && <th className="px-6 pt-5 pb-4 w-14">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {company_contacts.data.map(({ id, name, email, phone, website, clients }) => {
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
                      {website}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {clients?.map(({ id, name }) => {
                        return (
                          <div key={id} className="flex flex-col">
                            <div className="flex items-center justify-start gap-3">
                              <span>{name}</span>
                            </div>
                          </div>
                        )
                      })}
                    </td>
                    {canManageCompanies && (
                      <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('company_contact.edit', id)}
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
              {company_contacts.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Companies found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={company_contacts.links} />
      </AuthenticatedLayout>
  )
}
