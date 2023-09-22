import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import DeleteUserForm from './Partials/DeleteUserForm'
import UpdatePasswordForm from './Partials/UpdatePasswordForm'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm'
import { Head } from '@inertiajs/react'
import { type PageProps } from '@/types'

export default function Edit ({
  auth,
  mustVerifyEmail,
  status
}: PageProps<{ mustVerifyEmail: boolean, status?: string }>) {
  return (
    <AuthenticatedLayout
      auth={auth}
      header={
        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
          Profile
        </h2>
      }
    >
      <Head title="Profile" />
      <div className='grid grid-cols-1 gap-y-2.5'>
        <div className="bg-white dark:bg-[#1b2e4b] rounded-md border border-white-light dark:border-dark px-6 py-3.5">
          <UpdateProfileInformationForm
            mustVerifyEmail={mustVerifyEmail}
            status={status}
            className="max-w-xl"
          />
        </div>

        <div className="bg-white dark:bg-[#1b2e4b] rounded-md border border-white-light dark:border-dark px-6 py-3.5">
          <UpdatePasswordForm className="max-w-xl" />
        </div>

        <div className="bg-white dark:bg-[#1b2e4b] rounded-md border border-white-light dark:border-dark px-6 py-3.5">
          <DeleteUserForm className="max-w-xl" />
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
