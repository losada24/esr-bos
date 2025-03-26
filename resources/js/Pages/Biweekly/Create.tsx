import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import {
  type PageProps,
  type BiweeklyInstaller
} from '@/types'
import ReportBiweeklyForm from './ReportBiweeklyForm'

export default function Create ({
  auth
}: PageProps) {
  const initialValues: BiweeklyInstaller = {
    id: 0,
    start_biweekly_period: null,
    end_biweekly_period: null,
    period: ['', '']
  }
  // console.log(initialValues)

  const handleSubmit = async (values: any, helpers: FormikHelpers<BiweeklyInstaller>) => {
    router.post(route('biweekly.store', values), {
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
          pageTitle="Create Biweekly"
      >
          <Head title="Create Biweekly" />
          <Formik<BiweeklyInstaller>
            initialValues={initialValues}
            // validationSchema={orderSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <ReportBiweeklyForm
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
