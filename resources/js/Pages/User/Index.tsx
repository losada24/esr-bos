import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type User, type PaginatorLink, type Role } from '@/types'
import Pagination from '@/Components/Pagination'
import UserFilter from './UserFilter'
import { isAdmin, getRoleName } from '@/Utils/user'

type IndexUserProps = PageProps & {
  users: {
    data: User[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, users }: IndexUserProps) {
  // const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this User?')) {
      router.delete(route('user.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Users'
          actions={
            <Link
              className="btn btn-primary"
              href={route('user.create')}
            >
              <span>Create User</span>
            </Link>
          }
      >
        <Head title="Users" />
        <UserFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Email</th>
                <th className="px-6 pt-5 pb-4">Role</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.data.map(({ id, name, email, roles, company }) => {
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
                      {getRoleName(roles.map(({ name }) => name))}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
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
