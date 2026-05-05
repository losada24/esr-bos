import { type FormEvent } from 'react'
import { Head, Link } from '@inertiajs/react'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

type StockMaterialFormData = {
  name: string
  description: string
  cost: string | number
  area: string
  requested_date: string
  quote_id: string
  quote_id_received_date: string
}

type Props = PageProps & {
  title: string
  data: StockMaterialFormData
  setData: (key: keyof StockMaterialFormData, value: StockMaterialFormData[keyof StockMaterialFormData]) => void
  areaOptions: string[]
  processing?: boolean
  errors?: Partial<Record<keyof StockMaterialFormData, string>>
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

const FieldError = ({ message }: { message?: string }) => {
  if (!message) return null
  return <p className="mt-1 text-xs font-medium text-rose-600">{message}</p>
}

const formatDate = (date: Date) => {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${date.getFullYear()}-${month}-${day}`
}

export default function StockMaterialForm ({ auth, title, data, setData, areaOptions, processing = false, errors = {}, onSubmit }: Props) {
  const setDate = (key: keyof StockMaterialFormData, dates: Date[]) => {
    setData(key, dates[0] ? formatDate(dates[0]) : '')
  }

  const setQuoteId = (value: string) => {
    setData('quote_id', value)

    if (value.trim() !== '' && data.quote_id_received_date === '') {
      setData('quote_id_received_date', formatDate(new Date()))
    }
  }

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={title}
      actions={<Link href={route('stock-material.index')} className="btn btn-outline-primary">Back</Link>}
    >
      <Head title={title} />
      <form onSubmit={onSubmit} className="panel space-y-6">
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div>
            <label htmlFor="name" className="text-sm font-semibold text-slate-700">Name</label>
            <input id="name" type="text" value={data.name} onChange={(event) => { setData('name', event.target.value) }} className="form-input mt-1" />
            <FieldError message={errors.name} />
          </div>
          <div>
            <label htmlFor="cost" className="text-sm font-semibold text-slate-700">Cost</label>
            <input id="cost" type="number" min="0" step="0.01" value={data.cost} onChange={(event) => { setData('cost', event.target.value) }} className="form-input mt-1" />
            <FieldError message={errors.cost} />
          </div>
          <div>
            <label htmlFor="area" className="text-sm font-semibold text-slate-700">Area</label>
            <select id="area" value={data.area} onChange={(event) => { setData('area', event.target.value) }} className="form-select mt-1">
              <option value="">Select</option>
              {areaOptions.map((option) => <option key={option} value={option}>{option}</option>)}
            </select>
            <FieldError message={errors.area} />
          </div>
          <div>
            <label htmlFor="requested_date" className="text-sm font-semibold text-slate-700">Requested Date</label>
            <Flatpickr id="requested_date" value={data.requested_date} options={{ dateFormat: 'Y-m-d' }} onChange={(dates) => { setDate('requested_date', dates) }} className="form-input mt-1" />
            <FieldError message={errors.requested_date} />
          </div>
          <div>
            <label htmlFor="quote_id" className="text-sm font-semibold text-slate-700">Quote ID</label>
            <input id="quote_id" type="text" value={data.quote_id} onChange={(event) => { setQuoteId(event.target.value) }} className="form-input mt-1" />
            <FieldError message={errors.quote_id} />
          </div>
          <div>
            <label htmlFor="quote_id_received_date" className="text-sm font-semibold text-slate-700">Quote ID Received Date</label>
            <Flatpickr id="quote_id_received_date" value={data.quote_id_received_date} options={{ dateFormat: 'Y-m-d' }} onChange={(dates) => { setDate('quote_id_received_date', dates) }} className="form-input mt-1" />
            <FieldError message={errors.quote_id_received_date} />
          </div>
        </div>
        <div>
          <label htmlFor="description" className="text-sm font-semibold text-slate-700">Description</label>
          <textarea id="description" value={data.description} onChange={(event) => { setData('description', event.target.value) }} rows={5} className="form-textarea mt-1" />
          <FieldError message={errors.description} />
        </div>
        <div className="flex justify-end">
          <button type="submit" disabled={processing} className="btn btn-primary">{processing ? 'Saving...' : 'Save Material'}</button>
        </div>
      </form>
    </AuthenticatedLayout>
  )
}
