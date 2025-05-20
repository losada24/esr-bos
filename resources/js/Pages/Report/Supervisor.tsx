import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import { type PageProps, type User, type PaginatorLink, type Role } from '@/types'
import Pagination from '@/Components/Pagination'
import SupervisorFilter from './SupervisorFilter'
import { isAdmin, getRoleName } from '@/Utils/user'
import EyeIcon from '@/Components/Icons/EyeIcon'
import ExportIcon from '@/Components/Icons/ExportIcon'
import MoneyIcon from '@/Components/Icons/MoneyIcon'
import EyesIcon from '@/Components/Icons/Auth/EyesIcon'

type IndexUserProps = PageProps & {
  users: {
    data: User[]
    links: PaginatorLink[]
  }
}

export default function Supervisor ({ auth, users }: IndexUserProps) {
  // const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this User?')) {
      router.delete(route('user.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Supervisors'
      >
        <Head title="Supervisors" />
        <SupervisorFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Email</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.data.map(({ id, name, email }) => {
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
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('report.show_supervisor', id)}
                        >
                          <MoneyIcon/>
                        </Link>
                        <Link
                          href={route('report.show-supervisor-report', id)}
                        >
                          <EyeIcon/>
                        </Link>
                        <a href={route('report.excel-supervisor', id)} target='_blank' rel="noreferrer">
                          <ExportIcon/>
                        </a>
                    </td>
                  </tr>
                )
              })}
              {users.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Users found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={users.links} />
      </AuthenticatedLayout>
  )
}
