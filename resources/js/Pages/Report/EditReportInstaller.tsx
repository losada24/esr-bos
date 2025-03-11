import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { orderSchema } from './ReportInstallerCommon'
import ReportInstallerForm from './ReportInstallerForm'
import {
  type PageProps,
  type Order,
  type PaymentExtraFields,
  type InstallationPayment,
  type BiweeklyInstaller
} from '@/types'
import ReportInstallerPaymentForm from './ReportInstallerPaymentForm'

export default function EditReportInstaller ({
  auth,
  order,
  installation_team_id,
  installer_payment_status,
  amount,
  payment,
  biweeklys,
  payment_status
}: PageProps & {
  order: Order
  installation_team_id: number
  installer_payment_status: string []
  amount: number
  payment: InstallationPayment []
  biweeklys: BiweeklyInstaller []
  payment_status: string []
}) {
  const initialValues: PaymentExtraFields = {
    id: order.payment_extra_fields?.id ?? 0,
    order_id: order.id,
    installation_team_id,
    installer_payment_status: order.payment_extra_fields?.installer_payment_status ?? 'OPEN'
    //  installer_payment_status: order.payment_extra_fields?.installer_payment_status ?? ''
  }

  const initialValuesPayment: InstallationPayment = {
    id: 0,
    order_id: order.id,
    installation_team_id,
    installer_payment: 0,
    percentage_payment: 0,
    payment_date: null,
    extra_work: 0,
    extra_discount: 0,
    other_cost_installer: 0,
    payment_status: '',
    biweekly_id: 0,
    responsible_extra_work: '',
    notes: ''
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
      onSuccess: () => {
        helpers.resetForm()
      },
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
                <div className="w-1/3 mt-3">
                <label htmlFor='inspection_date' className='block text-sm font-semibold text-black'>Documents Submited</label>
                    <div className="flex flex-row justify-start font-extrabold text-black">
                     {[order.pre_inspection ? 'PI' : '',
                       order.walk_trough ? 'WT' : '',
                       order.inspection ? 'IN' : ''].filter(Boolean).join(' , ')}
                    </div>
                  </div>
                  <div className="w-1/3 mt-3">
                <label htmlFor='inspection_date' className='block text-sm font-semibold text-black'>Collect Payment</label>
                    <div className="flex flex-row justify-start font-extrabold text-black">
                     {[order.partial_payment_installation ? 'PARTIAL' : '',
                       order.final_payment_installation ? 'FINAL' : ''].filter(Boolean).join(' , ')}
                    </div>
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
                isCreate={true}
                installerId= { order.installation_teams[0]?.user?.id ?? 0 }
                amount={amount}
                order={order}
                payment={payment}
                biweeklys={biweeklys}
                setFieldValue={setFieldValue}
                values={values}
                payment_status={payment_status}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
