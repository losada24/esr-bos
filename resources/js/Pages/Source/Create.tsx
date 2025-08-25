import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import {
  type PageProps,
  type BiweeklyInstaller
} from '@/types'
import SourceForm from './SourceForm'
import { type Source } from '@/types/interfaces/order'

export default function Create ({
  auth
}: PageProps) {
  const initialValues: Source = {
    id: 0,
    name: '',
    description: ''
  }
  // console.log(initialValues)

  const handleSubmit = async (values: any, helpers: FormikHelpers<Source>) => {
    router.post(route('source.store', values), {
      _method: 'POST'
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
          pageTitle="Create Source"
      >
          <Head title="Create Source" />
          <Formik<Source>
            initialValues={initialValues}
            // validationSchema={orderSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <SourceForm
                errors={errors}
                submitCount={submitCount}
                isCreate={false}
                setFieldValue={setFieldValue}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
