import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderSchema } from './ReportInstallerCommon'
import ReportInstallerForm from './ReportInstallerForm'
import {
  type PageProps,
  type Order,
  type BiweeklyInstaller
} from '@/types'
import ReportBiweeklyForm from './ReportBiweeklyForm'

export default function CreateBiweekly ({
  auth,
  installation_team_id,
  method_payment
}: PageProps & {

  installation_team_id: number
  method_payment: string []
}) {
  const initialValues: BiweeklyInstaller = {
    id: 0,
    installation_team_id,
    start_biweekly_period: null,
    end_biweekly_period: null,
    payment_method: '',
    period: ['', '']
  }
  // console.log(initialValues)

  const handleSubmit = async (values: any, helpers: FormikHelpers<BiweeklyInstaller>) => {
    router.post(route('report.store_biweekly', values), {
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
                installerId= { installation_team_id ?? 0 }
                method_payment={method_payment}
                setFieldValue={setFieldValue}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
