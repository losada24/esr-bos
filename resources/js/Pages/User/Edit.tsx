import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userUpdateSchema, type UserPageProps, type User, type UserFormValues } from './UserCommon'
import UserForm from './UserForm'
import { type ModalProps, type Role } from '@/types'
import { useEffect, useState } from 'react'

export default function Edit ({ auth, roles, user }: UserPageProps) {
  const [modalProps, setModalProps] = useState<ModalProps | null>(null)
  // const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name))
  console.log(roles)
  const initialValues: UserFormValues = {
    id: user?.data.id ?? 0,
    name: user?.data.name ?? '',
    email: user?.data.email ?? '',
    phone: user?.data.phone ?? '',
    password: '',
    password_confirmation: '',
    role: user?.data.role?.map((role: Role) => {
      return { value: role.id, label: role.name }
    }) ?? [],
    // company_id: user?.data.company_id ?? 0,
    // markup: user?.data.markup ?? 0,
    featured_image: ''
  }
  console.log(initialValues)
  useEffect(() => {
    setModalProps({
      title: user?.data.name ?? '',
      image: user?.data.id ?? 0
    })
  }, [])

  const handleSubmit = async (values: any, helpers: FormikHelpers<UserFormValues>) => {
    const userValues = {
      ...values,
      role: values.role.map((role: any) => role.value)
    }
    router.post(route('user.update', values.id), {
      _method: 'PUT',
      ...userValues
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
        <Formik<UserFormValues>
          initialValues={initialValues}
          validationSchema={userUpdateSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, setFieldValue, values}) => (
            <UserForm
              errors={errors}
              submitCount={submitCount}
              roles={roles}
              isCreate={false}
              setFieldValue={setFieldValue}
              featured_image={user?.data.featured_image ?? ''}
              modalProps={modalProps}
              values={values}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
