import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Head, Link, useForm } from '@inertiajs/react'
import { type FormEvent } from 'react'
import { type PageProps } from '@/types'

type StatusOnlyOrder = {
  id: number
  order_number: number | string
  name: string
  status: string
  service?: string | null
  service_source?: string | null
  service_type?: string[] | string | null
  client?: {
    id: number
    name: string
  } | null
  parent_order?: {
    id: number
    order_number: number | string
    name: string
  } | null
}

type EditStatusOnlyProps = PageProps & {
  order: StatusOnlyOrder
  statuses: string[]
}

export default function EditStatusOnly ({ auth, order, statuses }: EditStatusOnlyProps) {
  const serviceType = Array.isArray(order.service_type)
    ? order.service_type.join(', ')
    : (order.service_type ?? 'N/A')

  const { data, setData, put, processing, errors } = useForm({
    status: order.status ?? ''
  })

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    put(route('order.update_status_only', order.id), {
      preserveScroll: true
    })
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Update Service Status">
      <Head title="Update Service Status" />

      <div className="panel max-w-3xl">
        <div className="mb-6 border-b border-slate-200 pb-4">
          <h2 className="text-xl font-semibold text-slate-900">Post-Sale Service</h2>
          <p className="mt-1 text-sm text-slate-500">
            Only the service status can be changed from this screen.
          </p>
        </div>

        <div className="mb-6 grid gap-4 sm:grid-cols-2">
          <div>
            <div className="text-xs font-semibold uppercase text-slate-500">Service Order #</div>
            <div className="mt-1 text-sm font-medium text-slate-900">{order.order_number}</div>
          </div>
          <div>
            <div className="text-xs font-semibold uppercase text-slate-500">Service Name</div>
            <div className="mt-1 text-sm font-medium text-slate-900">{order.name}</div>
          </div>
          <div>
            <div className="text-xs font-semibold uppercase text-slate-500">Client</div>
            <div className="mt-1 text-sm font-medium text-slate-900">{order.client?.name ?? 'N/A'}</div>
          </div>
          <div>
            <div className="text-xs font-semibold uppercase text-slate-500">Origin</div>
            <div className="mt-1 text-sm font-medium text-slate-900">{order.service_source ?? 'N/A'}</div>
          </div>
          <div>
            <div className="text-xs font-semibold uppercase text-slate-500">Service Type</div>
            <div className="mt-1 text-sm font-medium text-slate-900">{serviceType || 'N/A'}</div>
          </div>
          <div>
            <div className="text-xs font-semibold uppercase text-slate-500">Associated Order</div>
            <div className="mt-1 text-sm font-medium text-slate-900">
              {order.parent_order ? `${order.parent_order.order_number} - ${order.parent_order.name}` : 'N/A'}
            </div>
          </div>
        </div>

        <form onSubmit={submit}>
          <div className="mb-6">
            <label htmlFor="status" className="mb-2 block text-sm font-semibold text-slate-700">
              Status
            </label>
            <select
              id="status"
              name="status"
              className="form-input"
              value={data.status}
              onChange={(event) => { setData('status', event.target.value) }}
            >
              {statuses.map((status) => (
                <option key={status} value={status}>{status}</option>
              ))}
            </select>
            <InputError message={errors.status} className="mt-2" />
          </div>

          <div className="flex items-center gap-3">
            <PrimaryButton type="submit" className="btn btn-primary" disabled={processing}>
              Save Status
            </PrimaryButton>
            <Link href={route('order.index')} className="btn btn-secondary">
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </AuthenticatedLayout>
  )
}
