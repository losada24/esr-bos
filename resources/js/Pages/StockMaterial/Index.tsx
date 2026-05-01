import { Head, Link, router } from '@inertiajs/react'
import { useState } from 'react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { type PageProps } from '@/types'

type Material = {
  id: number
  name: string
  description?: string | null
  cost?: string | number | null
  area?: string | null
  requested_date?: string | null
  quote_id?: string | null
  quote_id_received_date?: string | null
  updated_at?: string | null
}

type Props = PageProps & {
  materials: Material[]
  filters: { search?: string }
}

export default function Index ({ auth, materials, filters }: Props) {
  const [search, setSearch] = useState(filters.search ?? '')

  const applyFilters = () => {
    router.get(route('stock-material.index'), { search }, { preserveState: true, replace: true })
  }

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      pageTitle="Stock Materials"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <input type="text" value={search} onChange={(event) => { setSearch(event.target.value) }} placeholder="Search materials..." className="form-input w-56" />
          <button type="button" className="btn btn-primary" onClick={applyFilters}>Apply</button>
          <Link href={route('stock-material.create')} className="btn btn-outline-primary">New Material</Link>
        </div>
      }
    >
      <Head title="Stock Materials" />
      <div className="panel">
        <div className="mb-4 flex items-center justify-between gap-3">
          <div>
            <h2 className="text-base font-semibold text-slate-800">Stock Materials</h2>
            <p className="text-sm text-slate-400">Materials purchased for stock.</p>
          </div>
          <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{materials.length} items</span>
        </div>
        <div className="table-responsive">
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th className="px-4 py-3">Name</th>
                <th className="px-4 py-3">Area</th>
                <th className="px-4 py-3">Cost</th>
                <th className="px-4 py-3">Requested</th>
                <th className="px-4 py-3">Quote ID</th>
                <th className="px-4 py-3">Quote Date</th>
                <th className="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              {materials.map((material) => (
                <tr key={material.id} className="border-t border-slate-200 text-sm text-slate-600">
                  <td className="px-4 py-4 align-top">
                    <div className="font-semibold text-slate-700">{material.name}</div>
                    <div className="text-xs text-slate-400">{material.description ?? ''}</div>
                  </td>
                  <td className="px-4 py-4 align-top">{material.area ?? 'N/A'}</td>
                  <td className="px-4 py-4 align-top">{material.cost ?? 'N/A'}</td>
                  <td className="px-4 py-4 align-top">{material.requested_date ?? 'N/A'}</td>
                  <td className="px-4 py-4 align-top">{material.quote_id ?? 'N/A'}</td>
                  <td className="px-4 py-4 align-top">{material.quote_id_received_date ?? 'N/A'}</td>
                  <td className="px-4 py-4 align-top">
                    <Link href={route('stock-material.edit', material.id)} className="btn btn-sm btn-outline-primary">Edit</Link>
                  </td>
                </tr>
              ))}
              {materials.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-10 text-center text-sm text-slate-400">No stock materials found.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AuthenticatedCalendarLayout>
  )
}
