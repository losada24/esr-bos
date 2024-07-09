import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderFormObj, OrderFormValues, orderSchema } from './OrderCommon'
import OrderForm from './OrderForm'
import {
  type PageProps,
  type Order,
  type Client,
  type TypeOfWork,
  type User,
  type TypeOfHousing,
  type InstallationTeam,
  type TravelCost,
  type DurationOfWork,
  type ProductConfig,
  type TypeOfProduct
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
  type_of_products
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
}) {
  const initialValues: OrderFormValues = orderFormObj

  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderFormValues>) => {
    router.post(route('order.store'), values, {
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
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
