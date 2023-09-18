import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type User } from '@/types'
import {
  PERSMISSION_USERS_CREATE,
  PERSMISSION_USERS_UPDATE,
  PERSMISSION_USERS_DELETE
} from '@/Utils/constants'
import Can from '@/Components/Can'

type IndexUserProps = PageProps & {
  users: User[]
}

export default function Index ({ auth, errors, users }: IndexUserProps) {
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this User?')) {
      router.delete(route('user.destroy', id))
    }
  }

  console.log('auth', auth)

  return (
      <AuthenticatedLayout
          auth={auth}
          errors={errors}
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
        {/*<Can
          auth={auth}
          permission={PERSMISSION_USERS_CREATE}
        ></Can>*/}
        <table className="w-full whitespace-nowrap">
          <thead>
            <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Name</th>
              <th className="px-6 pt-5 pb-4">Email</th>
              <th className="px-6 pt-5 pb-4 w-14">Actions</th>
            </tr>
          </thead>
          <tbody>
            {users.map(({ id, name, email }) => {
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
                    <Can
                      auth={auth}
                      permission={PERSMISSION_USERS_UPDATE}
                    >
                      <Link
                        href={route('user.edit', id)}
                      >
                        <EditIcon className='h-8 fill-blue-500' />
                      </Link>
                    </Can>
                    <Can
                      auth={auth}
                      permission={PERSMISSION_USERS_DELETE}
                    >
                      <button
                        onClick={() => destroy(id)}
                      >
                        <DeleteIcon className='h-8 fill-red-500' />
                      </button>
                    </Can>
                  </td>
                </tr>
              );
            })}
            {users.length === 0 && (
              <tr>
                <td className="px-6 py-4 border-t" colSpan={3}>
                  No Users found.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </AuthenticatedLayout>
  )
}
