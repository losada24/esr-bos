import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { paymentInfoSchema } from './EstimateCommon'
import PaymentForm from './PaymentForm'
import { type PageProps, type Order, type PaymentInfo } from '@/types'
import { PAYMENT_METHODS, ROLES } from '@/Utils/constants'
import { getSubTotalPriceByRole } from '@/Utils/price'

export default function Payment ({ estimate, auth, states }: PageProps & {
  estimate: Order
  states: string[]
}) {
  const initialValues: PaymentInfo = {
    order_id: estimate.id,
    method: PAYMENT_METHODS.CASH,
    terms_and_conditions_agreed: false,
    street_address: '',
    country: 'US',
    city: '',
    state: '',
    zip_code: '',
    phone_number: '',
    email: '',
    notes: '',
    first_name: '',
    last_name: '',
    amount: getSubTotalPriceByRole(estimate, [ROLES.DEALER]) ?? 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<PaymentInfo>) => {
    router.post(route('estimate.order.store'), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Order"
      >
          <Head title="Create Order" />
          <Formik<PaymentInfo>
            initialValues={initialValues}
            validationSchema={paymentInfoSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, values }) => (
              <PaymentForm
                errors={errors}
                values={values}
                submitCount={submitCount}
                estimate={estimate}
                states={states}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
