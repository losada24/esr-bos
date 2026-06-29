import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderFormObj, type OrderFormValues, orderSchema, getValueIdNotNull } from './OrderCommon'
import OrderForm from './OrderForm'
import { PAYMENT_METHODS, SERVICES } from '@/Utils/constants'
import {
  type PageProps,
  type Client,
  type TypeOfWork,
  type User,
  type TypeOfHousing,
  type InstallationTeam,
  type TravelCost,
  type DurationOfWork,
  type ProductConfig,
  type TypeOfProduct,
  type ProductCategory,
  type ProductCost
} from '@/types'

export default function Create ({
  auth,
  clients,
  owners,
  type_of_works,
  types_of_housing,
  installation_teams,
  methods_of_payment,
  services,
  order_types,
  payment_schedule_types,
  payment_schedule_templates,
  travel_costs,
  supervisors,
  duration_of_works,
  products_config,
  type_of_products,
  product_category,
  product_costs,
  frame_colors,
  status,
  type_of_financing,
  statusPaymentInstaller,
  extraWorks,
  parent_order_options,
  defaultService,
  pageTitle
}: PageProps & {
  clients: Client[]
  owners: User[]
  type_of_works: TypeOfWork[]
  types_of_housing: TypeOfHousing[]
  installation_teams: InstallationTeam[]
  supervisors: User[]
  methods_of_payment: string[]
  services: string[]
  order_types: string[]
  payment_schedule_types: string[]
  payment_schedule_templates: Record<string, { label: string, percentage: number }[]>
  travel_costs: TravelCost[]
  duration_of_works: DurationOfWork[]
  products_config: ProductConfig[]
  type_of_products: TypeOfProduct[]
  product_category: ProductCategory[]
  product_costs: ProductCost[]
  frame_colors: string[]
  status: string[]
  type_of_financing: string[]
  statusPaymentInstaller: string
  extraWorks: Array<{ id: number, name: string }>
  parent_order_options: Array<{ id: number, order_number: string | number, name: string, status?: string }>
  defaultService?: string
  pageTitle?: string
}) {
  const initialValues: OrderFormValues = {
    ...orderFormObj,
    service: defaultService ?? orderFormObj.service
  }
  const resolvedTitle = pageTitle ?? 'Create Order'
  // console.log('Initial values:', initialValues)
  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderFormValues>) => {
    const isInstallationService = values.service === SERVICES.DELIVERY_AND_INSTALLATION
    const resolvedTypeOfWorkId = isInstallationService
      ? (values.type_of_work_id !== 0 ? values.type_of_work_id : getValueIdNotNull(values.type_of_work_id))
      : null
    const resolvedTypeOfHousingId = isInstallationService
      ? (values.type_of_housing_id !== 0 ? values.type_of_housing_id : getValueIdNotNull(values.type_of_housing_id))
      : null
    const resolvedTravelCostId = isInstallationService
      ? (values.travel_cost_id.value !== 0 ? values.travel_cost_id.value : '')
      : null
    const resolvedDurationOfWorkId = isInstallationService
      ? (values.duration_of_work_id.value !== 0 ? values.duration_of_work_id.value : '')
      : null

    const isCash = values.method_of_payment === PAYMENT_METHODS.CASH
    const isCashAndFinanced = values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE
    const requiresSchedule = isCash || isCashAndFinanced
    const resolvedPaymentScheduleType = requiresSchedule
      ? (isCashAndFinanced ? 'CUSTOMIZED' : values.payment_schedule_type)
      : null

    const order = {
      ...values,
      frame_color: values.frame_color.map((color: { label: string, value: string }) => color.label),
      duration_of_work_id: resolvedDurationOfWorkId,
      type_of_work_id: resolvedTypeOfWorkId,
      type_of_housing_id: resolvedTypeOfHousingId,
      installation_teams: values.installation_teams.map((installation_team: any) => installation_team.value) ?? [],
      owners: values.owners.map((owner: any) => owner.value),
      supervisor_id: values.supervisor_id.value,
      travel_cost_id: resolvedTravelCostId,
      status: typeof values.status === 'string' ? values.status : getValueIdNotNull(values.status),
      contact_type: 'RESIDENTIAL CONTACT',
      type_of_financing: (values.method_of_payment === PAYMENT_METHODS.FINANCED || isCashAndFinanced)
        ? values.type_of_financing
        : null,
      down_payment: isCashAndFinanced ? values.down_payment : null,
      do_not_send_email: values.client_email_selection === '__NONE__',
      payment_schedule_type: resolvedPaymentScheduleType,
      custom_schedule: requiresSchedule && resolvedPaymentScheduleType === 'CUSTOMIZED'
        ? (values.custom_schedule ?? [])
          .map((item: { label?: string, amount?: string | number }) => ({
            label: String(item.label ?? '').trim(),
            amount: Number(String(item.amount ?? '').replace(/,/g, ''))
          }))
          .filter((item: { label: string, amount: number }) => item.label !== '' && Number.isFinite(item.amount))
        : []
    }

    console.log('Order data:', order)

    router.post(route('order.store'), order, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={resolvedTitle}
      >
          <Head title={resolvedTitle} />
          <Formik<OrderFormValues>
            initialValues={initialValues}
            validationSchema={orderSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <OrderForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                clients={clients}
                setFieldValue={setFieldValue}
                values={values}
                type_of_products={type_of_products}
                owners={owners}
                types_of_housing={types_of_housing}
                installation_teams={installation_teams}
                methods_of_payment={methods_of_payment}
                services={services}
                order_types={order_types}
                payment_schedule_types={payment_schedule_types}
                payment_schedule_templates={payment_schedule_templates}
                travel_costs={travel_costs}
                supervisors={supervisors}
                duration_of_works={duration_of_works}
                products_config={products_config}
                type_of_works={type_of_works}
                product_category={product_category}
                product_costs={product_costs}
                frame_colors={frame_colors}
                status={status}
                type_of_financing={type_of_financing}
                statusPaymentInstaller={statusPaymentInstaller}
                extraWorks={extraWorks}
                parent_order_options={parent_order_options}
                order_colors={[]} // Assuming frame_colors are used for order colors

              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
