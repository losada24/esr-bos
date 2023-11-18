import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userUpdateSchema, type UserPageProps, type User } from './UserCommon'
import UserForm from './UserForm'
import { type Role } from '@/types'
import { isAdmin } from '@/Utils/user'

export default function Edit ({ auth, roles, user, companies }: UserPageProps) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const initialValues: User = {
    id: user?.data.id ?? 0,
    name: user?.data.name ?? '',
    email: user?.data.email ?? '',
    password: '',
    password_confirmation: '',
    role: user?.data.role ?? 0,
    company_id: user?.data.company_id ?? 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<User>) => {
    router.put(route('user.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit User'
      >
        <Head title="Edit" />
        <Formik<User>
          initialValues={initialValues}
          validationSchema={userUpdateSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount }) => (
            <UserForm
              errors={errors}
              submitCount={submitCount}
              roles={roles}
              isCreate={false}
              companies={companies}
              isAdmin={IS_ADMIN}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
