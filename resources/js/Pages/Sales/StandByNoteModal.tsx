import InputError from '@/Components/InputError'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { Formik, Form } from 'formik'
import { PRODUCT_LINES } from '@/Utils/constants'

interface StandByFormValues {
  note: string
  productLine: string
}

export interface StandByNoteModalProps {
  open: boolean
  taskTitle: string
  loading?: boolean
  error?: string | null
  initialNote: string
  initialProductLine: string
  requireProductLine?: boolean
  onSubmit: (values: { note: string, productLine: string }) => void
  onCancel: () => void
}

export default function StandByNoteModal ({
  open,
  taskTitle,
  loading = false,
  error,
  initialNote,
  initialProductLine,
  requireProductLine = false,
  onSubmit,
  onCancel
}: StandByNoteModalProps) {
  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h3 className="text-lg font-semibold text-slate-800">Move to STAND BY</h3>
            <p className="mt-2 text-sm text-slate-600">
              Provide a note to move order <strong>{taskTitle}</strong> to <strong>STAND BY</strong>.
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

        <Formik<StandByFormValues>
          enableReinitialize
          initialValues={{
            note: initialNote ?? '',
            productLine: initialProductLine ?? ''
          }}
          validate={(values) => {
            const issues: Partial<Record<keyof StandByFormValues, string>> = {}

            if (!values.note || values.note.trim() === '') {
              issues.note = 'Note is required.'
            }
            if (requireProductLine && !values.productLine) {
              issues.productLine = 'Product Line is required.'
            }

            return issues
          }}
          onSubmit={(values) => {
            onSubmit({
              note: values.note.trim(),
              productLine: values.productLine
            })
          }}
        >
          {({ values, errors, submitCount, handleChange, handleBlur }) => (
            <Form className="mt-4 space-y-4">
              {requireProductLine && <div className={submitCount ? (errors.productLine ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600">Product Line</label>
                <select name="productLine" value={values.productLine} onChange={handleChange} onBlur={handleBlur} className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700" disabled={loading}>
                  <option value="">Select Product Line</option>
                  {PRODUCT_LINES.map((line) => <option key={line} value={line}>{line}</option>)}
                </select>
                {submitCount && errors.productLine ? <InputError message={errors.productLine} className="mt-2" /> : null}
              </div>}
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
                  disabled={loading}
                />
                {submitCount && errors.note
                  ? <InputError message={errors.note} className="mt-2" />
                  : null}
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
