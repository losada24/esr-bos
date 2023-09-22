import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import * as Yup from 'yup'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps } from '@/types'
import { PHONE_REG_EXP, STATUS } from '@/Utils/constants'
import ReferredForm from './ReferredForm'

export interface ReferredEditFormProps {
  id: number
  name: string
  email: string
  phone: string
  notes: string
  status: string
  status_notes: string
}

export type ReferredPageProps = PageProps & {
  referred: ReferredEditFormProps
}

export const referredCreateSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  phone: Yup.string().matches(PHONE_REG_EXP, 'Phone number is not valid'),
  notes: Yup.string().max(500, 'Must be 500 characters or less'),
  status: Yup.string().required('Status is required').oneOf(STATUS.map((item) => item.id)),
  status_notes: Yup.string().max(500, 'Must be 500 characters or less')
})

export default function Edit ({ auth, referred }: ReferredPageProps) {
  const initialValues: ReferredEditFormProps = {
    id: referred.id ?? 0,
    name: referred.name ?? '',
    email: referred.email ?? '',
    status: referred.status ?? '',
    phone: referred.phone ?? '',
    notes: referred.notes ?? '',
    status_notes: referred.status_notes ?? ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<ReferredEditFormProps>) => {
    router.put(route('referred.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit Referred'
      >
        <Head title="Edit" />
        <Formik<ReferredEditFormProps>
          initialValues={initialValues}
          validationSchema={referredCreateSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, values, setFieldValue }) => (
            <ReferredForm
              errors={errors}
              submitCount={submitCount}
              defaultStatus={initialValues.status}
              values={values}
              setFieldValue={setFieldValue}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
