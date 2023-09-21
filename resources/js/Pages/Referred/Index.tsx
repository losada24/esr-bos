import { type ReactNode, useState } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import Pagination from '@/Components/Pagination'
import { type PageProps, type Referred, type PaginatorLink, type ReferralsStatusUpdate } from '@/types'
import ReferredFilter from './ReferredFilter'
import { isAdmin } from '@/Utils/user'
import StatusModal from './StatusModal'
import StatusBadge from '@/Components/StatusBadge'

type IndexReferredProps = PageProps & {
  referrals: {
    data: Referred[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, errors, referrals }: IndexReferredProps) {
  const [statusHistoryModal, setStatusHistoryModal] = useState(false)
  const [statusHistory, setStatusHistory] = useState<ReferralsStatusUpdate[]>([])
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Referred?')) {
      router.delete(route('referred.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          errors={errors}
          pageTitle='Referrals'
      >
        <Head title="Referrals" />

        <ReferredFilter />

        <table className="w-full whitespace-nowrap">
          <thead>
            <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Name</th>
              <th className="px-6 pt-5 pb-4">Email</th>
              <th className="px-6 pt-5 pb-4">Phone</th>
              <th className="px-6 pt-5 pb-4">Status</th>
              {isAdmin(auth.user.roles.map((role) => role.name)) && <th className="px-6 pt-5 pb-4">User</th>}
              <th className="px-6 pt-5 pb-4">Notes</th>
              {isAdmin(auth.user.roles.map((role) => role.name)) && <th className="px-6 pt-5 pb-4 w-14">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {referrals.data.map(({ id, name, email, phone, user, notes, status, referrals_status_update }) => {
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
                    <button onClick={() => {
                      setStatusHistory(referrals_status_update)
                      setStatusHistoryModal(true)
                    }}><StatusBadge status={status} /></button>
                  </td>
                  {isAdmin(auth.user.roles.map((role) => role.name)) &&
                    <td className="border-t px-6 py-4 align-top">
                      {user.name}
                    </td>
                  }
                  <td className="border-t px-6 py-4 align-top">
                    {notes}
                  </td>
                  {isAdmin(auth.user.roles.map((role) => role.name)) &&
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('referred.edit', id)}
                        >
                          <EditIcon />
                        </Link>
                        <button
                          onClick={() => { destroy(id) }}
                        >
                          <DeleteIcon />
                        </button>
                    </td>
                  }
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
          </tbody>
        </table>
        <Pagination links={referrals.links} />
        <StatusModal showModal={statusHistoryModal} statusHistory={statusHistory} handleClose={setStatusHistoryModal} />
      </AuthenticatedLayout>
  )
}
