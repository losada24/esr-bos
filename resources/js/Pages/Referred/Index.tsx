import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import Pagination from '@/Components/Pagination'
import { type PageProps, type Referred, type PaginatorLink } from '@/types'

type IndexReferredProps = PageProps & {
  referrals: {
    data: Referred[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, errors, referrals }: IndexReferredProps) {
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this User?')) {
      router.delete(route('user.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          errors={errors}
          pageTitle='Referrals'
      >
        <Head title="Referrals" />

        <table className="w-full whitespace-nowrap">
          <thead>
            <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Name</th>
              <th className="px-6 pt-5 pb-4">Email</th>
              <th className="px-6 pt-5 pb-4">Phone</th>
              <th className="px-6 pt-5 pb-4">Notes</th>
              <th className="px-6 pt-5 pb-4">Status</th>
              <th className="px-6 pt-5 pb-4 w-14">Actions</th>
            </tr>
          </thead>
          <tbody>
            {referrals.data.map(({ id, name, email, phone, user, notes, status }) => {
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
                    {status}
                  </td>
                  <td className="border-t px-6 py-4 align-top">
                    {notes}
                  </td>
                  <td className="border-t flex items-center px-6 py-4">
                    {/* <Can
                      auth={auth}
                      permission={PERSMISSION_USERS_UPDATE}
                    ></Can> <Can
                      auth={auth}
                      permission={PERSMISSION_USERS_DELETE}
                    > </Can> */}
                      <Link
                        href={route('user.edit', id)}
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
            {referrals.data.length === 0 && (
              <tr>
                <td className="px-6 py-4 border-t" colSpan={6}>
                  No Referrals found.
                </td>
              </tr>
            )}
            <Pagination links={referrals.links} />
          </tbody>
        </table>
      </AuthenticatedLayout>
  )
}
