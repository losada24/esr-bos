import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderSchema } from '../Report/ReportInstallerCommon'
import {
  type PageProps
} from '@/types'
import { type Source } from '@/types/interfaces/order'
import SourceForm from './SourceForm'

export default function Edit ({
  auth,
  source
}: PageProps & {
  source: Source
}) {
  const initialValues: Source = {
    id: source.id ?? 0,
    name: source.name ?? '',
    description: source.description ?? ''
  }
  const handleSubmit = async (values: any, helpers: FormikHelpers<Source>) => {
    console.log(values.id)
    router.post(route('source.update', values.id), {
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
          pageTitle="Udpate Biweekly"
      >
          <Head title="Udpate Source" />
          <Formik<Source>
            initialValues={initialValues}
            validationSchema={orderSchema}
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
