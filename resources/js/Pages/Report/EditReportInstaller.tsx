import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderSchema } from './ReportInstallerCommon'
import ReportInstallerForm from './ReportInstallerForm'
import {
  type PageProps,
  type Order,
  type PaymentExtraFields,
  type InstallationPayment
} from '@/types'
import ReportInstallerPaymentForm from './ReportInstallerPaymentForm'

export default function EditReportInstaller ({
  auth,
  order,
  installation_team_id,
  installer_payment_status,
  amount,
  payment
}: PageProps & {
  order: Order
  installation_team_id: number
  installer_payment_status: string []
  amount: number
  payment: InstallationPayment []
}) {
  const initialValues: PaymentExtraFields = {
    id: order.payment_extra_fields?.id ?? 0,
    order_id: order.id,
    installation_team_id,
    extra_work: order.payment_extra_fields?.extra_work ?? 0,
    extra_discount: order.payment_extra_fields?.extra_discount ?? 0,
    responsible_extra_work: order.payment_extra_fields?.responsible_extra_work ?? '',
    notes: order.payment_extra_fields?.notes ?? '',
    documents_submitted: order.payment_extra_fields?.documents_submitted ?? '',
    collected_payment: order.payment_extra_fields?.collected_payment ?? false,
    other_cost_installer: order.payment_extra_fields?.other_cost_installer ?? 0,
    installer_payment_status: order.payment_extra_fields?.installer_payment_status ?? 'OPEN'
    //  installer_payment_status: order.payment_extra_fields?.installer_payment_status ?? ''
  }

  const initialValuesPayment: InstallationPayment = {
    id: 0,
    order_id: order.id,
    installation_team_id,
    installer_payment: 0,
    percentage_payment: order.initial_payment_percentage ?? 0,
    payment_date: null
    //  installer_payment_status: order.payment_extra_fields?.installer_payment_status ?? ''
  }
  // console.log(initialValues)

  const handleSubmit = async (values: any, helpers: FormikHelpers<PaymentExtraFields>) => {
    // console.log(values)
    router.post(route('report.update_installer_report', values), {
      _method: 'POST'
    }, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }
  const handleSubmitPayment = async (values: any, helpers: FormikHelpers<InstallationPayment>) => {
    router.post(route('report.update_installer_payment', values), {
      _method: 'POST'
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
          pageTitle="Installer Report Update"
      >
          <Head title="Update Order" />
          <fieldset className='p-3 border rounded-xl'>
            <legend className='text-lg font-semibold'>Order data</legend>
              <div className='grid grid-cols-3 gap-4'>
                <div className='mt-3'>
                  <label htmlFor='order_name' className='block text-sm font-semibold text-black'>Order Name</label>
                  <input
                    type='text'
                    name='order_name'
                    id='order_name'
                    autoComplete='order_name'
                    className='form-input'
                    value={order.name}
                    disabled
                  />
                </div>
                <div className='mt-3'>
                  <label htmlFor='owners' className='block text-sm font-semibold text-black'>Owner Name</label>
                  <input
                    type='text'
                    name='owners'
                    id='owners'
                    autoComplete='owners'
                    className='form-input'
                    value= {order.owners.map((owner) => {
                      return owner.name
                    }).join(', ')}
                    disabled
                  />
                </div>
                <div className='mt-3'>
                  <label htmlFor='supervisor' className='block text-sm font-semibold text-black'>Supervisor Name</label>
                  <input
                    type='text'
                    name='supervisor'
                    id='supervisor'
                    autoComplete='supervisor'
                    className='form-input'
                    value = {order.supervisor?.name}
                    disabled
                  />
                </div>
              </div>
              <div className='grid grid-cols-3 gap-4'>
                <div className='mt-3'>
                  <label htmlFor='start_date' className='block text-sm font-semibold text-black '>Start Date</label>
                  <input
                    type='text'
                    name='start_date'
                    id='start_date'
                    autoComplete='start_date'
                    className='form-input'
                    value={order.installation_date?.toString() ?? ''}
                    disabled
                  />
                </div>
                <div className='mt-3'>
                  <label htmlFor='inspection_date' className='block text-sm font-semibold text-black'>Inspection Date</label>
                  <input
                    type='text'
                    name='inspection_date'
                    id='inspection_date'
                    autoComplete='inspection_date'
                    className='form-input'
                    value= {order.inspection_date?.toString() ?? ''}
                    disabled
                  />
                </div>
                <div className='mt-3'>
                  <label htmlFor='complete_date' className='block text-sm font-semibold text-black'>Completed Date</label>
                  <input
                    type='text'
                    name='complete_date'
                    id='complete_date'
                    autoComplete='complete_date'
                    className='form-input'
                    value = {order.complete_date?.toString() ?? ''}
                    disabled
                  />
                </div>
              </div>
          </fieldset>
          <Formik<PaymentExtraFields>
            initialValues={initialValues}
            validationSchema={orderSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <ReportInstallerForm
                errors={errors}
                submitCount={submitCount}
                isCreate={false}
                installerId= { order.installation_teams[0]?.user?.id ?? 0 }
                installer_payment_status={installer_payment_status}
                setFieldValue={setFieldValue}
                values={values}
              />
            )}
          </Formik>
           <Formik<InstallationPayment>
            initialValues={initialValuesPayment}
            validationSchema={orderSchema}
            onSubmit={handleSubmitPayment}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <ReportInstallerPaymentForm
                errors={errors}
                submitCount={submitCount}
                isCreate={false}
                installerId= { order.installation_teams[0]?.user?.id ?? 0 }
                amount={amount}
                order={order}
                payment={payment}

                setFieldValue={setFieldValue}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
