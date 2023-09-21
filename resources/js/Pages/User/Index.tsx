import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import CopyIcon from '@/Components/Icons/CopyIcon'
import { type PageProps, type User, type PaginatorLink } from '@/types'
import Pagination from '@/Components/Pagination'
import Tippy from '@tippyjs/react'
import 'tippy.js/dist/tippy.css'
import Swal from 'sweetalert2'
import withReactContent from 'sweetalert2-react-content'
import UserFilter from './UserFilter'

type IndexUserProps = PageProps & {
  users: {
    data: User[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, errors, users }: IndexUserProps) {
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this User?')) {
      router.delete(route('user.destroy', id))
    }
  }

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
        <Head title="Users" />
        <UserFilter />
        <table className="w-full whitespace-nowrap">
          <thead>
            <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Name</th>
              <th className="px-6 pt-5 pb-4">Email</th>
              <th className="px-6 pt-5 pb-4 text-right">Referrals</th>
              <th className="px-6 pt-5 pb-4">Role</th>
              <th className="px-6 pt-5 pb-4 w-14">Actions</th>
            </tr>
          </thead>
          <tbody>
            {users.data.map(({ id, name, email, roles, reference_code, referrals_count }) => {
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
                  <td className="border-t px-6 py-4 align-top text-right">
                    <button onClick={() => { router.get(route('referred.index', { user_id: id })) }} className='badge bg-danger p-0.5 px-1.5 rounded-full'>{referrals_count}</button>
                  </td>
                  <td className="border-t px-6 py-4 align-top">
                    {roles.map(({ name }) => name).join(', ') || 'N/A'}
                  </td>
                  <td className="border-t flex items-center px-6 py-4">
                      <Tippy content="Copy Link to Clipboard">
                        <button
                          className='mr-2'
                          onClick={() => {
                            const url = route('referred.create', { reference_code })
                            navigator.clipboard.writeText(url)
                            const MySwal = withReactContent(Swal)
                            MySwal.fire({
                              title: 'Link successfully copied to clipboard!',
                              toast: true,
                              position: 'bottom-start',
                              showConfirmButton: false,
                              timer: 3000,
                              showCloseButton: true
                            })
                          }}
                        >
                          <CopyIcon className='h-5 w-5' />
                        </button>
                      </Tippy>
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
        <Pagination links={users.links} />
      </AuthenticatedLayout>
  )
}
