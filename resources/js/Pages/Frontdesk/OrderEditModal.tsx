import Modal from '@/Components/Modal'
import OrderQualifiedForm from './OrderQualifiedForm'
import { Formik, type FormikHelpers } from 'formik'
import { orderQualifiedSchema, type OrderFormValues } from './OrderCommon'
import { type Client } from '../Client/ClientCommon'
import { type CompanyContact, type User } from '@/types'
import { type Source, type Attachment } from '@/types/interfaces/order'
import CloseIcon from '@/Components/Icons/CloseIcon'

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
  attachments,
  errorMessage
}: OrderEditModalProps) {
  const hasInitialOwners = Array.isArray(initialValues.owner_ids) && initialValues.owner_ids.length > 0
  return (
    <Formik<OrderFormValues>
      initialValues={initialValues}
      validationSchema={orderQualifiedSchema}
      enableReinitialize
      onSubmit={onSubmit}
    >
      {({ errors, submitCount, setFieldValue, values, isSubmitting }) => (
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
                attachments={attachments}
                onCancel={() => { if (!isSubmitting) onClose() }}
                submitLabel={isSubmitting ? 'Saving…' : 'Save Changes'}
                showClientField={false}
                showNotesField={false}
                useModalLayout
                showOwnerField={hasInitialOwners}
              />
            </div>
          </div>
          </div>
        </Modal>
      )}
    </Formik>
  )
}
