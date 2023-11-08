import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Order, type Client, type FixWindows } from '@/types'
import FixWindowsForm from './FixWindowsForm'
import { fixWindowsSchema } from './FixWindowsCommon'

export default function Create ({ auth, frame_colors, glass_colors, estimate }: PageProps & {
  frame_colors: string[]
  glass_colors: string[]
  clients: Client[]
  estimate: Order
}) {
  const initialValues: FixWindows = {
    id: 0,
    estimate_id: estimate.id,
    mark: '',
    width: 0,
    height: 0,
    frame_color: estimate.frame_color,
    glass_color: estimate.glass_color,
    glass_type: '',
    low_e: '',
    privacy: '',
    qty: 0,
    markup: estimate.markup
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<FixWindows>) => {
    router.post(route('fix-windows.store'), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={'Add Fixed Window'}
      >
          <Head title={'Add Fixed Window'} />
          <Formik<FixWindows>
            initialValues={initialValues}
            validationSchema={fixWindowsSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, values }) => (
              <FixWindowsForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                glass_colors={glass_colors}
                frame_colors={frame_colors}
                estimate_id={estimate.id}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
