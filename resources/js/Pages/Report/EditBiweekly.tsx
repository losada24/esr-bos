import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderSchema } from './ReportInstallerCommon'
import {
  type PageProps,
  type BiweeklyInstaller
} from '@/types'
import ReportBiweeklyForm from './ReportBiweeklyForm'

export default function EditBiweekly ({
  auth,
  biweekly,
  installation_team_id,
  method_payment,
  period
}: PageProps & {
  biweekly: BiweeklyInstaller
  installation_team_id: number
  method_payment: string []
  period: string []
}) {
  const initialValues: BiweeklyInstaller = {
    id: biweekly.id ?? 0,
    installation_team_id,
    start_biweekly_period: biweekly.start_biweekly_period ?? null,
    end_biweekly_period: biweekly.end_biweekly_period ?? null,
    payment_method: biweekly.payment_method ?? '',
    period: period ?? ['', '']
  }

  // console.log(initialValues)

  const handleSubmit = async (values: any, helpers: FormikHelpers<BiweeklyInstaller>) => {
    // console.log(values)
    router.post(route('report.update_biweekly', values), {
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
          pageTitle="Installer Report Update"
      >
          <Head title="Update Order" />
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
                installerId={ installation_team_id ?? 0 }
                method_payment={method_payment}
                setFieldValue={setFieldValue}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
