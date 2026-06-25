import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { type RequestPayload } from '@inertiajs/core'
import { Formik, type FormikHelpers } from 'formik'
import * as Yup from 'yup'
import { type CompanyContact, type PageProps, type User } from '@/types'
import { type Source } from '@/types/interfaces/order'
import { type Client } from '@/Pages/Client/ClientCommon'
import {
  orderFormObj,
  type OrderFormValues
} from '@/Pages/Frontdesk/OrderCommon'
import OrderQualifiedForm from '@/Pages/Frontdesk/OrderQualifiedForm'

const esrOrderSchema = Yup.object({
  order_type: Yup.string().required('Order Type is required'),
  product_line: Yup.string().required('Product Line is required'),
  name: Yup.string().required('Order Name is required'),
  job_address: Yup.string().required('Job Address is required'),
  city: Yup.string().required('City is required'),
  job_state: Yup.string().required('State is required'),
  job_zip: Yup.string().required('ZIP Code is required'),
  client_id: Yup.number().required('Contact Name is required').min(1, 'Contact Name is required'),
  company_contact_id: Yup.number().required('Company is required').min(1, 'Company is required'),
  client_email_selection: Yup.string().required('Client Email Delivery is required'),
  status: Yup.string().required('Status is required'),
  order_number: Yup.string().required('Order Number is required'),
  project_amount: Yup.number().required('Project Amount is required').min(0, 'Project Amount cannot be negative'),
  service: Yup.string().nullable(),
  esr_design: Yup.boolean(),
  esr_express: Yup.boolean(),
  esr_reylos_glass: Yup.boolean(),
  esr_service: Yup.boolean(),
  method_of_payment: Yup.string().nullable(),
  type_of_financing: Yup.string().nullable(),
  down_payment: Yup.number().nullable().min(0, 'Cash Amount cannot be negative'),
  payment_schedule_type: Yup.string().nullable(),
  custom_schedule: Yup.array().nullable(),
  owner_ids: Yup.array().of(Yup.number().required()).min(1, 'Owner is required'),
  notes: Yup.string().nullable().max(4000, 'Notes must be less than 4000 characters')
})

export default function Create ({
  auth,
  clients,
  owners,
  companies,
  sources,
  order_types,
  product_lines,
  statuses,
  services,
  methods_of_payment: methodsOfPayment,
  type_of_financing: typeOfFinancing,
  payment_schedule_templates: paymentScheduleTemplates,
  sources_clients: sourcesClients
}: PageProps & {
  clients: Client[]
  owners: User[]
  companies: CompanyContact[]
  sources: Source[]
  order_types: string[]
  product_lines: string[]
  statuses: string[]
  services: string[]
  methods_of_payment: string[]
  type_of_financing: string[]
  payment_schedule_templates: Record<string, { label: string, percentage: number }[]>
  sources_clients: string[]
}) {
  const initialValues: OrderFormValues = {
    ...orderFormObj,
    order_type: order_types[0] ?? 'COMMERCIAL',
    product_line: product_lines[0] ?? 'ESR',
    status: statuses[0] ?? 'DEALER REQUEST',
    service: '',
    order_number: '',
    owner_ids: owners.length === 1 ? [owners[0].id] : []
  }

  const handleSubmit = (
    values: OrderFormValues,
    helpers: FormikHelpers<OrderFormValues>
  ) => {
    router.post(route('esr-process.store-order'), values as unknown as RequestPayload, {
      forceFormData: true,
      onError: (errors) => {
        helpers.setErrors(errors)
      },
      onFinish: () => {
        helpers.setSubmitting(false)
      }
    })
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Create ESR Order">
      <Head title="Create ESR Order" />
      <Formik<OrderFormValues>
        initialValues={initialValues}
        validationSchema={esrOrderSchema}
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
            status={statuses}
            sources={sources}
            order_types={order_types}
            companies={companies}
            sourcesClients={sourcesClients}
            frame_colors={[]}
            glass_colors={[]}
            glass_types={[]}
            glass_coatings={[]}
            languages={[]}
            methodsOfPayment={methodsOfPayment}
            financingOptions={typeOfFinancing}
            paymentScheduleTemplates={paymentScheduleTemplates}
            services={services}
            showNotesField={true}
            showProjectAmountOnlySection={true}
            showPaymentInformationSection={true}
            submitLabel="Create"
            esrMode={true}
            showCommercialSourceField={false}
            showAttachmentsField={true}
            showOwnerField={true}
            companyStoreRoute="esr-process.companies.store"
            clientStoreRoute="esr-process.clients.store"
            onCancel={() => { router.visit(route('esr-process.index')) }}
          />
        )}
      </Formik>
    </AuthenticatedLayout>
  )
}
