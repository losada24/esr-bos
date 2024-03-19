import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userUpdateSchema, type UserPageProps, type User } from './ExternalProductsCommon'
import UserForm from './ExternalProductsForm'
import { type ModalProps, type Role } from '@/types'
import { isAccountManager, isAdmin } from '@/Utils/user'
import { useEffect, useState } from 'react'

export default function Edit ({ auth, roles, user, companies }: UserPageProps) {
  const [modalProps, setModalProps] = useState<ModalProps | null>(null)
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const initialValues: User = {
    id: user?.data.id ?? 0,
    name: user?.data.name ?? '',
    email: user?.data.email ?? '',
    password: '',
    password_confirmation: '',
    role: user?.data.role ?? 0,
    company_id: user?.data.company_id ?? 0,
    markup: user?.data.markup ?? 0,
    featured_image: ''
  }

  useEffect(() => {
    setModalProps({
      title: user?.data.name ?? '',
      image: user?.data.featured_image ?? ''
    })
  }, [])

  const handleSubmit = async (values: any, helpers: FormikHelpers<User>) => {
    router.post(route('user.update', values.id), {
      _method: 'PUT',
      ...values
    }, {
      forceFormData: true,
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
          {({ errors, submitCount, setFieldValue }) => (
            <UserForm
              errors={errors}
              submitCount={submitCount}
              roles={roles}
              isCreate={false}
              companies={companies}
              isAdmin={IS_ADMIN}
              setFieldValue={setFieldValue}
              featured_image={user?.data.featured_image ?? ''}
              modalProps={modalProps}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
