import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderFormObj, type OrderFormValues, orderSchema, getValueIdNotNull } from './OrderCommon'
import OrderForm from './OrderForm'
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
  travel_costs,
  supervisors,
  duration_of_works,
  products_config,
  type_of_products,
  product_category,
  product_costs,
  frame_colors,
  status
}: PageProps & {
  clients: Client[]
  owners: User[]
  type_of_works: TypeOfWork[]
  types_of_housing: TypeOfHousing[]
  installation_teams: InstallationTeam[]
  supervisors: User[]
  methods_of_payment: string[]
  services: string[]
  travel_costs: TravelCost[]
  duration_of_works: DurationOfWork[]
  products_config: ProductConfig[]
  type_of_products: TypeOfProduct[]
  product_category: ProductCategory[]
  product_costs: ProductCost[]
  frame_colors: string[]
  status: string[]
}) {
  const initialValues: OrderFormValues = orderFormObj

  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderFormValues>) => {
    const order = {
      ...values,
      duration_of_work_id: values.duration_of_work_id.value !== 0 ? values.duration_of_work_id.value : '',
      type_of_work_id: values.type_of_work_id !== 0 ? values.type_of_work_id : getValueIdNotNull(values.type_of_work_id),
      type_of_housing_id: values.type_of_housing_id !== 0 ? values.type_of_housing_id : getValueIdNotNull(values.type_of_housing_id),
      installation_teams: values.installation_teams.map((installation_team: any) => installation_team.value) ?? [],
      owners: values.owners.map((owner: any) => owner.value),
      supervisor_id: values.supervisor_id.value,
      travel_cost_id: values.travel_cost_id.value !== 0 ? values.travel_cost_id.value : '',
      status: typeof values.status === 'string' ? values.status : getValueIdNotNull(values.status)
    }

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
          pageTitle="Create Order"
      >
          <Head title="Create Order" />
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
                travel_costs={travel_costs}
                supervisors={supervisors}
                duration_of_works={duration_of_works}
                products_config={products_config}
                type_of_works={type_of_works}
                product_category={product_category}
                product_costs={product_costs}
                frame_colors={frame_colors}
                status={status}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
