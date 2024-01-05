import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { companySchema, type Company } from './CompanyCommon'
import CompanyForm from './CompanyForm'
import { type PageProps } from '@/types'

export default function Create ({ auth, states }: PageProps & { states: string[] }) {
  const initialValues: Company = {
    id: 0,
    name: '',
    phone_number: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    featured_image: '',
    markup: 0,
    promotion: 0,
    allow_credit_payment: false
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Company>) => {
    router.post(route('company.store'), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Company"
      >
          <Head title="Create Company" />
          <Formik<Company>
            initialValues={initialValues}
            validationSchema={companySchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue }) => (
              <CompanyForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                states={states}
                setFieldValue={setFieldValue}
                modalProps={null}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
