import { type ServiceControlHistory } from '@/types'

const humanize = (value: string | null | undefined): string => {
  if (!value) return 'N/A'
  return value
    .toLowerCase()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const renderChangeList = (label: string, values: Record<string, unknown> | null | undefined) => {
  if (!values || Object.keys(values).length === 0) return null

  return (
    <div className="space-y-2">
      <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{label}</p>
      <div className="space-y-1">
        {Object.entries(values).map(([field, value]) => (
          <div key={field} className="flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <span className="font-semibold text-slate-500">{humanize(field)}</span>
            <span className="rounded-md bg-slate-100 px-2 py-0.5">{String(value ?? 'N/A')}</span>
          </div>
        ))}
      </div>
    </div>
  )
}

export default function HistoryTimeline ({ histories = [] }: { histories?: ServiceControlHistory[] }) {
  if (!histories.length) {
    return (
      <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-400">
        No service control history recorded yet.
      </div>
    )
  }

  return (
    <div className="space-y-4">
      {histories.map((history, index) => (
        <div key={history.id} className="relative flex gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="relative flex w-10 shrink-0 justify-center">
            <span className="relative z-10 mt-1 inline-flex h-9 w-9 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-[11px] font-bold uppercase tracking-wide text-sky-700">
              {index + 1}
            </span>
            {index < histories.length - 1 && (
              <span className="absolute left-1/2 top-10 h-[calc(100%+1rem)] w-px -translate-x-1/2 bg-slate-200" />
            )}
          </div>

          <div className="min-w-0 flex-1 space-y-3">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p className="text-sm font-semibold text-slate-700">{history.summary ?? humanize(history.event_type)}</p>
                <p className="text-xs text-slate-400">
                  {history.user?.name ?? 'System'} · {history.created_at_label ?? 'No date'}
                </p>
              </div>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {humanize(history.event_type)}
              </span>
            </div>

            {history.comment && (
              <div className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                {history.comment}
              </div>
            )}

            <div className="grid gap-3 md:grid-cols-2">
              {renderChangeList('Old Values', history.old_values)}
              {renderChangeList('New Values', history.new_values)}
            </div>
          </div>
        </div>
      ))}
    </div>
  )
}
