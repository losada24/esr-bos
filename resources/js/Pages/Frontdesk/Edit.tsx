import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { loadOrderFormObj, type OrderFormValues, requestSchema, getValueIdNotNull, orderFormObj } from './OrderCommon'
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
  owners,
  status,
  sources
}: PageProps & {
  clients: Client[]
  owners: User[]
  status: string[]
  sources: string[]
}) {
  // const initialValues: OrderFormValues = loadOrderFormObj(order)
  const initialValues: OrderFormValues = orderFormObj
  // console.log(initialValues)
  const getSupervisorId = (supervisor: any) => {
    let value = null
    if (supervisor !== null && Object.prototype.hasOwnProperty.call(supervisor, 'value')) {
      value = supervisor.value
    } else if (supervisor !== null && !isNaN(supervisor)) {
      value = supervisor
    }

    return value
  }

  //  console.log(messageconfirm)
  // return

  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderFormValues>) => {
    const selectedStatus = typeof values.status === 'string' ? values.status : getValueIdNotNull(values.status)
    const isConfirmed = selectedStatus === 'CONFIRMED'

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
      status: selectedStatus,
      contact_type: values.contact_type ?? 'RESIDENTIAL CONTACT'
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
            validationSchema={requestSchema}
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
                owners={owners}
                status={status}
                sources={sources}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
