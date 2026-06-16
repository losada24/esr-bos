import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import ServiceForm from './ServiceForm'
import {
  loadOrderFormObj,
  orderSchema,
  type OrderFormValues
} from './OrderCommon'
import {
  type PageProps,
  type Order,
  type TravelCost,
  type TypeOfWork,
  type ProductConfig,
  type TypeOfProduct,
  type ProductCategory,
  type ProductCost,
  type DurationOfWork,
  type InstallationTeam
} from '@/types'
import { PAYMENT_METHODS } from '@/Utils/constants'
import { NO_CLIENT_EMAIL_SELECTION } from '@/Pages/Sales/ContractSignedModal'

export default function EditService ({
  auth,
  order,
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
  type_of_financing
}: PageProps & {
  order: Order
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
}) {
  const loadedValues = loadOrderFormObj(order)

  const initialValues: OrderFormValues = {
    ...loadedValues,
    service: order.service,
    status: order.status ?? status[0] ?? '',
    method_of_payment: loadedValues.method_of_payment ?? '',
    type_of_financing: loadedValues.type_of_financing ?? '',
    installation_teams: order.installation_teams ?? [],
    owners: [],
    order_products: loadedValues.order_products ?? [],
    duration_of_work_id: loadedValues.duration_of_work_id ?? 0,
    travel_cost_id: loadedValues.travel_cost_id ?? 0,
    supervisor_id: loadedValues.supervisor_id ?? 0,
    vip_clients: loadedValues.vip_clients ?? false,
    vip_notes: loadedValues.vip_notes ?? '',
    do_not_send_email: loadedValues.do_not_send_email ?? false,
    client_email_selection: loadedValues.client_email_selection ?? NO_CLIENT_EMAIL_SELECTION,
    is_new_travel_cost: loadedValues.is_new_travel_cost ?? false,
    new_travel_cost: loadedValues.new_travel_cost ?? 0,
    additional_travel_costs: loadedValues.additional_travel_costs ?? 0,
  }

  const handleSubmit = async (values: OrderFormValues & { orderProducts?: any[] }, helpers: FormikHelpers<OrderFormValues>) => {
    const isFinancedMethod = values.method_of_payment === PAYMENT_METHODS.FINANCED || values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE
    const isCashMethod = values.method_of_payment === PAYMENT_METHODS.CASH

    const payload = {
      id: values.id,
      client_id: values.client_id,
      client_name: values.client_name,
      last_name: values.last_name,
      phone: values.phone,
      email: values.email,
      vip_clients: values.vip_clients,
      vip_notes: values.vip_notes,
      client_email_selection: values.client_email_selection ?? NO_CLIENT_EMAIL_SELECTION,
      do_not_send_email: (values.client_email_selection ?? NO_CLIENT_EMAIL_SELECTION) === NO_CLIENT_EMAIL_SELECTION,
      name: values.name,
      order_number: values.order_number,
      job_address: values.job_address,
      job_state: values.job_state,
      job_zip: values.job_zip,
      city: values.city,
      city_permits: values.city_permits,
      additional_travel_costs: values.additional_travel_costs,
      travel_cost_id: values.travel_cost_id !== 0 ? values.travel_cost_id : '',
      duration_of_work_id: values.duration_of_work_id !== 0 ? values.duration_of_work_id : '',
      method_of_payment: values.method_of_payment,
      payment_schedule_type: isCashMethod ? (values.payment_schedule_type || null) : null,
      custom_schedule: isCashMethod && values.payment_schedule_type === 'CUSTOMIZED'
        ? (values.custom_schedule ?? [])
          .map((item: { label?: string, amount?: string | number }) => ({
            label: String(item.label ?? '').trim(),
            amount: Number(String(item.amount ?? '').replace(/,/g, ''))
          }))
          .filter((item: { label: string, amount: number }) => item.label !== '' && Number.isFinite(item.amount))
        : [],
      type_of_financing: isFinancedMethod && values.type_of_financing ? values.type_of_financing : null,
      project_amount: values.project_amount,
      down_payment: values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE ? values.down_payment : null,
      change_order_enabled: values.change_order_enabled,
      change_order_amount: values.change_order_amount,
      change_order_note: values.change_order_note,
      service: order.service,
      status: values.status,
      replanned_reasons: values.replanned_reasons ?? [],
      supervisor_id: values.supervisor_id !== 0 ? values.supervisor_id : null,
      notes: values.notes,
      entry_date: values.entry_date,
      delivery_date: values.delivery_date,
      installation_date: values.installation_date,
      is_new_travel_cost: values.is_new_travel_cost,
      new_travel_cost: values.new_travel_cost,
      contact_type: values.contact_type,
      owners: [],
      installation_teams: (values.installation_teams ?? []).map((team: any) => team.value ?? team.id ?? team),
      orderProducts: values.orderProducts ?? [],
      attachment_role_targets: values.attachment_role_targets
    }

    const pendingAttachments = Array.isArray(values.attachments)
      ? values.attachments.filter((item: any) => item instanceof File)
      : []
    ;(payload as any).attachments = pendingAttachments

    const allowsAttachmentRoleSelection = values.service !== 'PICKUP' && values.service !== 'DELIVERY ONLY'
    if (!allowsAttachmentRoleSelection) {
      delete (payload as any).attachment_role_targets
    }

    router.post(route('order.update_service', order.id), {
      _method: 'PUT',
      ...payload
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
      pageTitle="Edit Service"
    >
      <Head title="Edit Service" />
      <Formik<OrderFormValues>
        enableReinitialize={true}
        initialValues={initialValues}
        validationSchema={orderSchema}
        onSubmit={handleSubmit}
      >
        {({ errors, submitCount, setFieldValue, values }) => (
          <ServiceForm
            errors={errors}
            submitCount={submitCount}
            isCreate={false}
            supervisors={supervisors}
            methods_of_payment={methods_of_payment}
            services={services}
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
            attachments={order.attachments}
          />
        )}
      </Formik>
    </AuthenticatedLayout>
  )
}
