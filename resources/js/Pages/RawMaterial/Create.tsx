import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { rawMaterialSchema } from './RawMaterialCommon'
import RawMaterialForm from './RawMaterialForm'
import { type PageProps, type RawMaterial } from '@/types'

export default function Create ({ auth, unit_of_measurement }: PageProps & { unit_of_measurement: string[] }) {
  const initialValues: RawMaterial = {
    id: 0,
    name: '',
    qty: 0,
    unit_of_measurement: '',
    cost_per_unit: 0,
    featured_image: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<RawMaterial>) => {
    router.post(route('raw-material.store'), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Raw Material"
      >
          <Head title="Create Raw Material" />
          <Formik<RawMaterial>
            initialValues={initialValues}
            validationSchema={rawMaterialSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue }) => (
              <RawMaterialForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                unit_of_measurement={unit_of_measurement}
                setFieldValue={setFieldValue}
                modalProps={null}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
