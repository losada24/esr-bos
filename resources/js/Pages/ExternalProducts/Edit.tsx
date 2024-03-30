import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { externalProductSchema } from './ExternalProductsCommon'
import { type PageProps, type ExternalProducts } from '@/types'
import ExternalProductsForm from './ExternalProductsForm'

type ExternalProductsCreateProps = PageProps & {
  externalProducts: string[]
  externalProduct: ExternalProducts
}

export default function Edit ({ auth, externalProducts, externalProduct }: ExternalProductsCreateProps) {
  const initialValues: ExternalProducts = {
    id: externalProduct.id,
    external_product: externalProduct.external_product,
    width: externalProduct.width,
    height: externalProduct.height,
    extras: JSON.stringify(externalProduct.extras) ?? '',
    price: externalProduct.price ?? 0,
    notes: externalProduct.notes ?? ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<ExternalProducts>) => {
    router.put(route('external-products.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit External Products'
      >
        <Head title="Edit" />
        <Formik<ExternalProducts>
          initialValues={initialValues}
          validationSchema={externalProductSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, setFieldValue }) => (
            <ExternalProductsForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              externalProducts={externalProducts}
              setFieldValue={setFieldValue}
              defaultProduct={externalProduct.external_product}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
