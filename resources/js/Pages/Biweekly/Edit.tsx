import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderSchema } from '../Report/ReportInstallerCommon'
import {
  type PageProps,
  type BiweeklyInstaller
} from '@/types'
import ReportBiweeklyForm from './ReportBiweeklyForm'

export default function EditBiweekly ({
  auth,
  biweekly,
  period
}: PageProps & {
  biweekly: BiweeklyInstaller
  period: string []
}) {
  const initialValues: BiweeklyInstaller = {
    id: biweekly.id ?? 0,
    start_biweekly_period: biweekly.start_biweekly_period ?? null,
    end_biweekly_period: biweekly.end_biweekly_period ?? null,
    period: period ?? ['', '']
  }
  const handleSubmit = async (values: any, helpers: FormikHelpers<BiweeklyInstaller>) => {
    console.log(values.id)
    router.post(route('biweekly.update', values.id), {
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
          <Head title="Udpate Biweekly" />
          <Formik<BiweeklyInstaller>
            initialValues={initialValues}
            validationSchema={orderSchema}
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
