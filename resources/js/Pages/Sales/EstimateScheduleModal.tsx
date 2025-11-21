import { Formik, Form } from 'formik'
import Flatpickr from 'react-flatpickr'
import InputError from '@/Components/InputError'
import Select, { type MultiValue } from 'react-select'
import CloseIcon from '@/Components/Icons/CloseIcon'
import 'flatpickr/dist/flatpickr.css'

export interface EstimateScheduleFormValues {
  scheduleDate: string
  ownerIds: number[]
}

export interface EstimateScheduleModalProps {
  open: boolean
  taskTitle: string
  initialScheduleDate: string
  initialOwnerIds: number[]
  ownerOptions: Array<{ id: number, name: string }>
  error?: string | null
  saving?: boolean
  onClose: () => void
  onSubmit: (values: { scheduleDate: string, ownerIds: number[] }) => void
}

const formatForInput = (date: Date) => {
  const pad = (n: number) => n.toString().padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

export default function EstimateScheduleModal ({
  open,
  taskTitle,
  initialScheduleDate,
  initialOwnerIds,
  ownerOptions,
  error,
  saving = false,
  onClose,
  onSubmit
}: EstimateScheduleModalProps) {
  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-slate-800">Assign Appointment</h3>
            <p className="text-sm text-slate-500">{taskTitle}</p>
          </div>
          <button
            type="button"
            className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            onClick={onClose}
          >
            <CloseIcon />
          </button>
        </div>

        <Formik<EstimateScheduleFormValues>
          enableReinitialize
          initialValues={{
            scheduleDate: initialScheduleDate ?? '',
            ownerIds: initialOwnerIds ?? []
          }}
          validate={(values) => {
            const issues: Partial<Record<keyof EstimateScheduleFormValues, string>> = {}

            /* if (!values.scheduleDate) {
              issues.scheduleDate = 'Appointment date is required.'
            } */

            if (!values.ownerIds || values.ownerIds.length === 0) {
              issues.ownerIds = 'Select at least one owner.'
            }

            return issues
          }}
          onSubmit={(values) => {
            onSubmit({
              scheduleDate: values.scheduleDate,
              ownerIds: values.ownerIds
            })
          }}
        >
          {({ values, setFieldValue, errors, touched, submitCount }) => {
            const ownerSelectOptions = ownerOptions.map(owner => ({ value: owner.id, label: owner.name }))

            const selectedOwners = ownerSelectOptions.filter(option =>
              values.ownerIds?.includes(Number(option.value))
            )

            return (
              <Form className="space-y-4">
                <div className={submitCount ? (errors.scheduleDate ? 'has-error' : 'has-success') : ''}>
                  <label className="mb-1 block text-sm font-medium text-slate-600">Appointment Date</label>
                <Flatpickr
                  value={values.scheduleDate ?? ''}
                  onChange={(dates) => {
                    const date = dates?.[0]
                    setFieldValue('scheduleDate', date ? formatForInput(date) : '')
                  }}
                  options={{
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    time_24hr: false
                  }}
                  className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                />
                {(submitCount && errors.scheduleDate) ? <InputError message={errors.scheduleDate} className="mt-2" /> : null}
              </div>

              <div className={submitCount ? (errors.ownerIds ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600">Assign Owner(s)</label>
                <Select
                  inputId="owners"
                  classNamePrefix="react-select"
                  placeholder="Select Owners"
                  isMulti
                  value={selectedOwners}
                  options={ownerSelectOptions}
                  onChange={(selected: MultiValue<{ value: number, label: string }>) => {
                    const ids = selected.map(option => Number(option.value))
                    setFieldValue('ownerIds', ids)
                  }}
                />
                {(submitCount && typeof errors.ownerIds === 'string') ? <InputError message={errors.ownerIds} className="mt-2" /> : null}
              </div>

                {error && (
                  <div className="rounded-lg bg-rose-100 px-3 py-2 text-sm text-rose-700">
                    {error}
                  </div>
                )}

              <div className="mt-6 flex items-center justify-end gap-3">
                <button
                  type="button"
                  onClick={onClose}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                  disabled={saving}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={saving}
                >
                  {saving ? 'Guardando…' : 'Guardar'}
                </button>
              </div>
              </Form>
            )
          }}
        </Formik>
      </div>
    </div>
  )
}
