import InputError from '@/Components/InputError'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { Formik, Form } from 'formik'
import { PRODUCT_LINES } from '@/Utils/constants'

interface FollowUpFormValues {
  projectAmount: string
  note: string
  attachments: File[]
  productLine: string
  esrCost: string
}

export interface FollowUpModalProps {
  open: boolean
  taskTitle: string
  targetStatus: string
  initialProjectAmount: string
  initialNote: string
  initialProductLine: string
  initialEsrCost?: string
  requireProductLine?: boolean
  loading?: boolean
  error?: string | null
  onSubmit: (values: { projectAmount: string, note: string, attachments: File[], productLine: string, esrCost: string }) => void
  onCancel: () => void
}

export default function FollowUpModal ({
  open,
  taskTitle,
  targetStatus,
  initialProjectAmount,
  initialNote,
  initialProductLine,
  initialEsrCost = '',
  requireProductLine = false,
  loading = false,
  error,
  onSubmit,
  onCancel
}: FollowUpModalProps) {
  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h3 className="text-lg font-semibold text-slate-800">Move to {targetStatus}</h3>
            <p className="mt-2 text-sm text-slate-600">
              Complete the details below to move order <strong>{taskTitle}</strong> to <strong>{targetStatus}</strong>.
            </p>
          </div>
          <button
            type="button"
            onClick={onCancel}
            className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            disabled={loading}
          >
            <CloseIcon />
          </button>
        </div>

        <Formik<FollowUpFormValues>
          enableReinitialize
          initialValues={{
            projectAmount: initialProjectAmount ?? '',
            note: initialNote ?? '',
            attachments: [],
            productLine: initialProductLine ?? '',
            esrCost: initialEsrCost ?? ''
          }}
          validate={(values) => {
            const issues: Partial<Record<keyof FollowUpFormValues, string>> = {}
            if (requireProductLine && !values.productLine) {
              issues.productLine = 'Product Line is required.'
            }
            if (values.productLine === 'MIXED' && String(values.esrCost ?? '').trim() === '') {
              issues.esrCost = 'ESR Cost is required.'
            }
            return issues
          }}
          onSubmit={(values) => {
            onSubmit({
              projectAmount: values.projectAmount,
              note: values.note,
              attachments: values.attachments ?? [],
              productLine: values.productLine,
              esrCost: values.esrCost
            })
          }}
        >
          {({ values, errors, submitCount, handleChange, handleBlur, setFieldValue }) => (
            <Form className="mt-4 space-y-4" encType="multipart/form-data">
              {requireProductLine && <div className={submitCount ? (errors.productLine ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600">Product Line</label>
                <select name="productLine" value={values.productLine} onChange={handleChange} onBlur={handleBlur} className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700" disabled={loading}>
                  <option value="">Select Product Line</option>
                  {PRODUCT_LINES.map((line) => <option key={line} value={line}>{line}</option>)}
                </select>
                {submitCount && errors.productLine ? <InputError message={errors.productLine} className="mt-2" /> : null}
              </div>}
              {values.productLine === 'MIXED' && <div className={submitCount ? (errors.esrCost ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600">ESR Cost</label>
                <input name="esrCost" type="number" min="0" step="0.01" value={values.esrCost} onChange={handleChange} onBlur={handleBlur} className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" placeholder="Enter ESR cost" disabled={loading} />
                {submitCount && errors.esrCost ? <InputError message={errors.esrCost} className="mt-2" /> : null}
              </div>}
              <div className={submitCount ? (errors.projectAmount ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600">Project Amount</label>
                <input
                  name="projectAmount"
                  type="number"
                  step="0.01"
                  value={values.projectAmount}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                  placeholder="Enter project amount"
                />
                {submitCount && errors.projectAmount
                  ? <InputError message={errors.projectAmount} className="mt-2" />
                  : null}
              </div>

              <div className={submitCount ? (errors.note ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600">Note</label>
                <textarea
                  name="note"
                  rows={4}
                  value={values.note}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                  placeholder="Add a note"
                />
                {submitCount && errors.note
                  ? <InputError message={errors.note} className="mt-2" />
                  : null}
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600">Attachments</label>
                <input
                  name="attachments"
                  type="file"
                  multiple
                  onChange={(event) => {
                    const files = Array.from(event.currentTarget.files ?? [])
                    setFieldValue('attachments', files)
                  }}
                  className="block w-full cursor-pointer rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-sky-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed"
                  disabled={loading}
                />
                {values.attachments?.length > 0 && (
                  <ul className="mt-2 space-y-1 text-xs text-slate-500">
                    {values.attachments.map((file, index) => (
                      <li key={`${file.name}-${index}`}>{file.name}</li>
                    ))}
                  </ul>
                )}
              </div>

              {error && (
                <div className="rounded-lg bg-rose-100 px-3 py-2 text-sm text-rose-700">
                  {error}
                </div>
              )}

              <div className="mt-6 flex items-center justify-end gap-3">
                <button
                  type="button"
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-70"
                  onClick={onCancel}
                  disabled={loading}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={loading}
                >
                  {loading ? 'Saving…' : 'Confirm'}
                </button>
              </div>
            </Form>
          )}
        </Formik>
      </div>
    </div>
  )
}
