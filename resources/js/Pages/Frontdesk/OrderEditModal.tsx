import Modal from '@/Components/Modal'
import OrderQualifiedForm from './OrderQualifiedForm'
import { Formik, type FormikHelpers } from 'formik'
import { orderQualifiedSchema, type OrderFormValues } from './OrderCommon'
import * as Yup from 'yup'
import { type Client } from '../Client/ClientCommon'
import { type CompanyContact, type User } from '@/types'
import { type Source, type Attachment } from '@/types/interfaces/order'
import CloseIcon from '@/Components/Icons/CloseIcon'
import OrderNotesForOrder from '@/Components/OrderNotesForOrder'

const esrOrderEditSchema = Yup.object({
  order_type: Yup.string().required('Order Type is required'),
  product_line: Yup.string().nullable(),
  name: Yup.string().required('Order Name is required'),
  job_address: Yup.string().nullable().when('service', {
    is: (service?: string | null) => service !== 'PICKUP',
    then: (schema) => schema.required('Job Address is required')
  }),
  city: Yup.string().nullable().when('service', {
    is: (service?: string | null) => service !== 'PICKUP',
    then: (schema) => schema.required('City is required')
  }),
  job_state: Yup.string().nullable().when('service', {
    is: (service?: string | null) => service !== 'PICKUP',
    then: (schema) => schema.required('State is required')
  }),
  job_zip: Yup.string().nullable().when('service', {
    is: (service?: string | null) => service !== 'PICKUP',
    then: (schema) => schema.required('ZIP Code is required')
  }),
  client_id: Yup.number().required('Contact Name is required').min(1, 'Contact Name is required'),
  company_contact_id: Yup.number().required('Company is required').min(1, 'Company is required'),
  client_email_selection: Yup.string().required('Client Email Delivery is required'),
  status: Yup.string().required('Status is required'),
  order_number: Yup.string().nullable(),
  project_amount: Yup.number().nullable().min(0, 'Project Amount cannot be negative'),
  service: Yup.string().nullable(),
  method_of_payment: Yup.string().nullable(),
  type_of_financing: Yup.string().nullable(),
  down_payment: Yup.number().nullable().min(0, 'Cash Amount cannot be negative'),
  payment_schedule_type: Yup.string().nullable(),
  custom_schedule: Yup.array().nullable(),
  change_order_enabled: Yup.boolean().optional(),
  change_order_amount: Yup.number()
    .nullable()
    .when('change_order_enabled', {
      is: true,
      then: (schema) => schema.required('Change Order Price is required')
    }),
  change_order_note: Yup.string().nullable().max(2000, 'Change Order Note must be less than 2000 characters')
})

interface OrderEditModalProps {
  open: boolean
  initialValues: OrderFormValues
  onClose: () => void
  onSubmit: (values: OrderFormValues, helpers: FormikHelpers<OrderFormValues>) => Promise<void> | void
  clients: Client[]
  owners: User[]
  status: string[]
  sources: Source[]
  order_types: string[]
  companies: CompanyContact[]
  sourcesClients: string[]
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  glass_coatings: string[]
  languages: string[]
  methodsOfPayment?: string[]
  financingOptions?: string[]
  paymentScheduleTemplates?: Record<string, { label: string, percentage: number }[]>
  services?: string[]
  showPaymentInformationSection?: boolean
  showProjectAmountOnlySection?: boolean
  projectAmountReadOnly?: boolean
  canManageOwners?: boolean
  esrMode?: boolean
  attachments?: Attachment[]
  errorMessage?: string | null
}

export default function OrderEditModal ({
  open,
  initialValues,
  onClose,
  onSubmit,
  clients,
  owners,
  status,
  sources,
  order_types,
  companies,
  sourcesClients,
  frame_colors,
  glass_colors,
  glass_types,
  glass_coatings,
  languages,
  methodsOfPayment = [],
  financingOptions = [],
  paymentScheduleTemplates = {},
  services = [],
  showPaymentInformationSection = false,
  showProjectAmountOnlySection = false,
  projectAmountReadOnly = false,
  canManageOwners = false,
  esrMode = false,
  attachments,
  errorMessage
}: OrderEditModalProps) {
  const hasInitialOwners = Array.isArray(initialValues.owner_ids) && initialValues.owner_ids.length > 0
  const shouldShowOwnerField = hasInitialOwners && canManageOwners
  return (
    <Formik<OrderFormValues>
      initialValues={{
        ...initialValues,
        notes: '',
        attachments: []
      }}
      validationSchema={esrMode ? esrOrderEditSchema : orderQualifiedSchema}
      enableReinitialize
      onSubmit={onSubmit}
    >
      {({ errors, submitCount, setFieldValue, values, isSubmitting, submitForm }) => (
        <Modal
          show={open}
          maxWidth="5xl"
          closeable={!isSubmitting}
          onClose={() => { if (!isSubmitting) onClose() }}
        >
          <div className="mx-auto w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-xl">
            <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3">
              <h3 className="text-lg font-semibold text-slate-800">Edit Order</h3>
              <button
                type="button"
                className="text-slate-400 transition hover:text-slate-600 disabled:opacity-60"
                onClick={() => { if (!isSubmitting) onClose() }}
                disabled={isSubmitting}
              >
                <CloseIcon />
                <span className="sr-only">Close</span>
              </button>
            </div>

            <div className="p-5">
              {errorMessage && (
                <div className="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-600">{errorMessage}</div>
              )}

              <div className="max-h-[70vh] overflow-y-auto pr-1">
                <OrderQualifiedForm
                  errors={errors}
                  submitCount={submitCount}
                  isCreate={false}
                  clients={clients}
                  setFieldValue={setFieldValue}
                  values={values}
                  owners={owners}
                  status={status}
                  sources={sources}
                  order_types={order_types}
                  companies={companies}
                  sourcesClients={sourcesClients}
                  frame_colors={frame_colors}
                  glass_colors={glass_colors}
                  glass_types={glass_types}
                  glass_coatings={glass_coatings}
                  languages={languages}
                  methodsOfPayment={methodsOfPayment}
                  financingOptions={financingOptions}
                  paymentScheduleTemplates={paymentScheduleTemplates}
                  services={services}
                  attachments={attachments}
                  onCancel={() => { if (!isSubmitting) onClose() }}
                  submitLabel={isSubmitting ? 'Saving…' : 'Save Changes'}
                  showClientField
                  showNotesField={false}
                  showAttachmentsField
                  useModalLayout
                  showOwnerField={shouldShowOwnerField}
                  showInvoiceField
                  showPaymentInformationSection={showPaymentInformationSection}
                  showProjectAmountOnlySection={showProjectAmountOnlySection}
                  projectAmountReadOnly={projectAmountReadOnly}
                  appointmentDateReadOnly
                  esrMode={esrMode}
                  showCommercialSourceField={!esrMode}
                  showAddCommercialCompanyButton={!esrMode}
                  hideActions
                />
                <div className="mt-5 rounded-xl border border-slate-200 p-4">
                  <OrderNotesForOrder
                    orderId={initialValues.id || null}
                    canCreate={Boolean(initialValues.id)}
                    includeRelatedActivities
                    listTitle="Order Notes"
                    emptyMessage="No notes for this order."
                  />
                </div>
                <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                  <button
                    type="button"
                    onClick={() => { if (!isSubmitting) onClose() }}
                    disabled={isSubmitting}
                    className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    onClick={() => { void submitForm() }}
                    disabled={isSubmitting}
                    className="rounded-lg bg-sky-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  >
                    {isSubmitting ? 'Saving…' : 'Save Changes'}
                  </button>
                </div>
            </div>
          </div>
          </div>
        </Modal>
      )}
    </Formik>
  )
}
