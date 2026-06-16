import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import {
  orderFormObj,
  type OrderFormValues,
  orderSchema
} from './OrderCommon'
import ServiceForm from './ServiceForm'
import {
  type PageProps,
  type TravelCost,
  type TypeOfWork,
  type ProductConfig,
  type TypeOfProduct,
  type ProductCategory,
  type ProductCost,
  type DurationOfWork,
  type InstallationTeam
} from '@/types'
import { PAYMENT_METHODS, SERVICES } from '@/Utils/constants'
import { NO_CLIENT_EMAIL_SELECTION } from '@/Pages/Sales/ContractSignedModal'

export default function CreateService ({
  auth,
  supervisors,
  methods_of_payment,
  services,
  payment_schedule_types,
  payment_schedule_templates,
  travel_costs,
  status,
  type_of_products,
  product_category,
  products_config,
  extraWorks,
  product_costs,
  type_of_works,
  duration_of_works,
  installation_teams,
  type_of_financing,
  defaultService
}: PageProps & {
  supervisors: Array<{ id: number, name: string }>
  methods_of_payment: string[]
  services: string[]
  payment_schedule_types: string[]
  payment_schedule_templates: Record<string, { label: string, percentage: number }[]>
  travel_costs: TravelCost[]
  status: string[]
  type_of_products: TypeOfProduct[]
  product_category: ProductCategory[]
  products_config: ProductConfig[]
  extraWorks: Array<{ id: number, name: string }>
  product_costs: ProductCost[]
  type_of_works: TypeOfWork[]
  duration_of_works: DurationOfWork[]
  installation_teams: InstallationTeam[]
  type_of_financing: string[]
  defaultService?: string
}) {
  const resolvedService = defaultService ?? services[0] ?? SERVICES.SERVICE
  const initialValues: OrderFormValues = {
    ...orderFormObj,
    service: resolvedService,
    status: status[0] ?? '',
    contact_type: 'RESIDENTIAL CONTACT',
    travel_cost_id: 0,
    city: orderFormObj.city ?? '',
    method_of_payment: '',
    project_amount: 0,
    duration_of_work_id: 0,
    type_of_financing: '',
    vip_clients: false,
    vip_notes: '',
    do_not_send_email: true,
    client_email_selection: NO_CLIENT_EMAIL_SELECTION,
    // @ts-expect-error Formik additional field
    orderProducts: []
  }

  const handleSubmit = async (values: OrderFormValues & { orderProducts?: any[] }, helpers: FormikHelpers<OrderFormValues>) => {
    const payload: Record<string, any> = {
      ...values,
      owners: [],
      frame_color: [],
      orderProducts: values.orderProducts ?? [],
      supervisor_id: values.supervisor_id !== 0 ? values.supervisor_id : null,
      travel_cost_id: values.travel_cost_id !== 0 ? values.travel_cost_id : '',
      duration_of_work_id: values.duration_of_work_id !== 0 ? values.duration_of_work_id : '',
      type_of_work_id: 0,
      type_of_housing_id: 0,
      installation_teams: (values.installation_teams ?? []).map((team: any) => {
        if (typeof team === 'number' || typeof team === 'string') {
          return team
        }
        if ('value' in team) {
          return team.value
        }
        if ('id' in team) {
          return team.id
        }
        return team
      }),
      status: values.status,
      method_of_payment: values.method_of_payment,
      payment_schedule_type: values.method_of_payment === PAYMENT_METHODS.CASH ? values.payment_schedule_type : null,
      custom_schedule: values.method_of_payment === PAYMENT_METHODS.CASH && values.payment_schedule_type === 'CUSTOMIZED'
        ? (values.custom_schedule ?? [])
          .map((item: { label?: string, amount?: string | number }) => ({
            label: String(item.label ?? '').trim(),
            amount: Number(String(item.amount ?? '').replace(/,/g, ''))
          }))
          .filter((item: { label: string, amount: number }) => item.label !== '' && Number.isFinite(item.amount))
        : [],
      type_of_financing: values.type_of_financing ? values.type_of_financing : null,
      service: values.service ?? resolvedService,
      contact_type: 'RESIDENTIAL CONTACT',
      vip_clients: values.vip_clients,
      vip_notes: values.vip_notes,
      client_email_selection: values.client_email_selection ?? NO_CLIENT_EMAIL_SELECTION,
      do_not_send_email: (values.client_email_selection ?? NO_CLIENT_EMAIL_SELECTION) === NO_CLIENT_EMAIL_SELECTION
    }

    delete payload.user
    delete payload.client
    delete payload.typeOfWork
    delete payload.type_of_work
    delete payload.type_of_housing
    delete payload.duration_of_work
    delete payload.travel_cost
    delete payload.order_colors
    delete payload.order_products
    delete payload.installation_payment
    delete payload.installation_payments
    delete payload.payment_extra_fields
    delete payload.pre_inspection_attach
    delete payload.inspection_attach
    delete payload.walk_trough_attach

    router.post(route('order.store_service'), payload, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Create Service"
    >
      <Head title="Create Service" />
      <Formik<OrderFormValues>
        initialValues={initialValues}
        validationSchema={orderSchema}
        onSubmit={handleSubmit}
      >
        {({ errors, submitCount, setFieldValue, values }) => (
          <ServiceForm
            errors={errors}
            submitCount={submitCount}
            isCreate={true}
            // owners={owners}
            supervisors={supervisors}
            methods_of_payment={methods_of_payment}
            services={services.length > 0 ? services : [resolvedService]}
            payment_schedule_types={payment_schedule_types}
            payment_schedule_templates={payment_schedule_templates}
            travel_costs={travel_costs}
            status={status}
            setFieldValue={setFieldValue}
            values={values}
            type_of_products={type_of_products}
            product_category={product_category}
            products_config={products_config}
            extraWorks={extraWorks}
            product_costs={product_costs}
            type_of_works={type_of_works}
            duration_of_works={duration_of_works}
            installation_teams={installation_teams}
            type_of_financing={type_of_financing}
          />
        )}
      </Formik>
    </AuthenticatedLayout>
  )
}
