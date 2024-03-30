import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { externalProductSchema } from './ExternalProductsCommon'
import { type PageProps, type ExternalProducts } from '@/types'
import ExternalProductsForm from './ExternalProductsForm'

type ExternalProductsCreateProps = PageProps & {
  externalProducts: string[]
}

export default function Create ({ auth, externalProducts }: ExternalProductsCreateProps) {
  const initialValues: ExternalProducts = {
    id: 0,
    external_product: '',
    width: 0,
    height: 0,
    extras: '',
    price: 0,
    notes: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<ExternalProducts>) => {
    router.post(route('external-products.store'), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create External Products"
      >
          <Head title="Create External Products" />
          <Formik<ExternalProducts>
            initialValues={initialValues}
            validationSchema={externalProductSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue }) => (
              <ExternalProductsForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                externalProducts={externalProducts}
                setFieldValue={setFieldValue}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
