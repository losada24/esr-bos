import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { getOrderProducts, loadOrderFormObj, type OrderFormValues, orderSchema, getValueIdNotNull } from './OrderCommon'
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
  type TypeOfProduct,
  type ProductCategory,
  type ProductCost
} from '@/types'
import { type OrderColor } from '@/types/interfaces/order'

export default function Edit ({
  auth,
  clients,
  statusPaymentInstaller,
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
  order,
  frame_colors,
  order_colors,
  status,
  type_of_financing,
  extraWorks
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
  order: Order
  frame_colors: string[]
  order_colors: OrderColor[]
  status: string[]
  statusPaymentInstaller: string
  type_of_financing: string[]
  extraWorks: Array<{ id: number, name: string }>
}) {
  const initialValues: OrderFormValues = loadOrderFormObj(order)
  console.log(initialValues)
  const getSupervisorId = (supervisor: any) => {
    let value = null
    if (supervisor !== null && Object.prototype.hasOwnProperty.call(supervisor, 'value')) {
      value = supervisor.value
    } else if (supervisor !== null && !isNaN(supervisor)) {
      value = supervisor
    }

    return value
  }
  let messageconfirm = 'Are you sure you want to change the status to confirmed?'
  const isRentalEquipment: boolean = !!order.equipment_rental
  const isAssociationPermit: boolean = !!order.association_permits

  if (isRentalEquipment) {
    messageconfirm += 'This order required EQUIPMENT RENTAL.'
  }

  if (isAssociationPermit) {
    messageconfirm += 'This order required ASSOCIATION PERMIT.'
  }
  //  console.log(messageconfirm)
  // return

  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderFormValues>) => {
    const selectedStatus = typeof values.status === 'string' ? values.status : getValueIdNotNull(values.status)
    if (selectedStatus === 'CONFIRMED' && !confirm(messageconfirm)) {
      return
    }

    const order = {
      ...values,
      frame_color: (values.frame_color || []).map((color: { label: string, value: string }) => color.label),
      complete_date: values.status.value === 'COMPLETE' ? new Date().toLocaleDateString('en-CA') : null,
      pending_collect: values.status.value === 'PENDING COLLECT' ? new Date().toLocaleDateString('en-CA') : null,
      duration_of_work_id: typeof values.duration_of_work_id === 'number' ? values.duration_of_work_id : getValueIdNotNull(values.duration_of_work_id),
      installation_teams: values.installation_teams.map((installation_team: any) => {
        let value = 0
        if (Object.prototype.hasOwnProperty.call(installation_team, 'value')) {
          value = installation_team.value
        } else {
          value = installation_team.id
        }

        return value
      }) ?? [],
      owners: values.owners.map((owner: any) => {
        let value = 0
        if (Object.prototype.hasOwnProperty.call(owner, 'value')) {
          value = owner.value
        } else {
          value = owner.id
        }

        return value
      }),
      supervisor_id: getSupervisorId(values.supervisor_id),
      travel_cost_id: typeof values.travel_cost_id === 'number' ? values.travel_cost_id : getValueIdNotNull(values.travel_cost_id),
      status: selectedStatus
    }

    if (!Object.prototype.hasOwnProperty.call(order, 'orderProducts')) {
      order.orderProducts = values.order_products.map((orderProduct: any) => {
        console.log(orderProduct)
        return getOrderProducts(orderProduct)
      })
    }
    console.log(order)
    delete order.order_products
    router.post(route('order.update', values.id), {
      _method: 'PUT',
      ...order
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
          pageTitle="Update Order"
      >
          <Head title="Update Order" />
          <Formik<OrderFormValues>
            initialValues={initialValues}
            validationSchema={orderSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <OrderForm
                errors={errors}
                submitCount={submitCount}
                isCreate={false}
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
                attachments={order.attachments}
                status={status}
                statusPaymentInstaller={statusPaymentInstaller}
                type_of_financing={type_of_financing}
                extraWorks={extraWorks}
                order_colors={order_colors}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
