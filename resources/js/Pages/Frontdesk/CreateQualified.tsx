import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import {
  type PageProps,
  type User,
  type CompanyContact

} from '@/types'
import { getValueIdNotNull, orderFormObj, orderQualifiedSchema, requestSchema, type OrderFormValues } from './OrderCommon'
import OrderQualifiedForm from './OrderQualifiedForm'
import { type Source } from '@/types/interfaces/order'
import { type Client } from '../Client/ClientCommon'

export default function CreateQualified ({
  auth,
  clients,
  owners,
  status,
  sources,
  order_types,
  companies,
  sourcesClients,
  frame_colors,
  glass_colors,
  glass_types,
  glass_coatings,
  languages
}: PageProps & {
  clients: Client[]
  owners: User[]
  status: string[]
  sources: Source[]
  order_types: string[]
  companies: CompanyContact[]
  sourcesClients: string[]
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  glass_coatings: string[]
  languages: string[]
}) {
  const initialValues: OrderFormValues = orderFormObj
  // console.log('Initial values:', initialValues)
  const toNull = (v: any) =>
 (v === 0 || v === '0' || v === '' || v === undefined) ? null : v

  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderFormValues>) => {
    const order = {
      ...values,
      associate_company_contact_id_1: toNull(values.associate_company_contact_id_1),
      associate_company_contact_id_2: toNull(values.associate_company_contact_id_2),
      associate_client_id_1: toNull(values.associate_client_id_1),
      associate_client_id_2: toNull(values.associate_client_id_2),
      status: typeof values.status === 'string' ? values.status : getValueIdNotNull(values.status),
      source: typeof values.source === 'string' ? values.source : getValueIdNotNull(values.source)
    }

    console.log('Order data:', order)

    router.post(route('frontdesk.store-qualified-order'), order, {
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
          <Formik<OrderFormValues>
            initialValues={initialValues}
            validationSchema={orderQualifiedSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <OrderQualifiedForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                clients={clients}
                setFieldValue={setFieldValue}
                values={values}
                owners={owners}
                status={status}
                sources={sources}
                order_types={order_types}
                companies={companies}
                sourcesClients={sourcesClients}
                frame_colors={frame_colors}
                glass_colors={glass_colors}
                glass_types={glass_types}
                glass_coatings={glass_coatings}
                languages={languages}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
