import { useEffect, useState, type FormEvent } from 'react'

export type FilterRowPayload = {
  field: string
  value?: string
  value_secondary?: string
  op?: string
  value_min?: string
  value_max?: string
}

export type BoardFilters = {
  filter_field?: string
  filter_value?: string
  filter_value_secondary?: string
  filter_op?: string
  filter_value_min?: string
  filter_value_max?: string
  filters?: FilterRowPayload[]
  filter_match?: string
}

export type FilterSelectOption = { label: string, value: string }

export type FilterFieldConfig = {
  value: string
  label: string
  type: 'text' | 'select' | 'amount' | 'dual_text' | 'date'
  options?: FilterSelectOption[]
  primaryLabel?: string
  secondaryLabel?: string
  placeholder?: string
  secondaryPlaceholder?: string
}

type OrderBoardFilterProps = {
  fields: FilterFieldConfig[]
  initialFilters?: BoardFilters
  onApply: (params: BoardFilters) => void
  onReset: () => void
}

type FilterRow = {
  id: string
  field: string
  value: string
  valueSecondary: string
  op: string
  min: string
  max: string
}

const OPERATOR_OPTIONS: FilterSelectOption[] = [
  { label: '=', value: '=' },
  { label: '!=', value: '!=' },
  { label: '<', value: '<' },
  { label: '<=', value: '<=' },
  { label: '>', value: '>' },
  { label: '>=', value: '>=' },
  { label: 'Between', value: 'between' }
]

const DATE_OPERATOR_OPTIONS: FilterSelectOption[] = [
  { label: 'On', value: 'on' },
  { label: 'Before', value: 'before' },
  { label: 'After', value: 'after' },
  { label: 'Between', value: 'between' },
  { label: 'Today', value: 'today' },
  { label: 'Yesterday', value: 'yesterday' },
  { label: 'This Week', value: 'this_week' },
  { label: 'This Month', value: 'this_month' },
  { label: 'This Year', value: 'this_year' },
  { label: 'Last Week', value: 'last_week' },
  { label: 'Last Month', value: 'last_month' }
]

const MAX_FILTERS = 5
let rowIdSeed = 0

const getRowId = () => {
  rowIdSeed += 1
  return `filter-row-${rowIdSeed}`
}

const getDefaultOperator = (field?: FilterFieldConfig): string => {
  if (!field) return '='
  if (field.type === 'amount') return '='
  if (field.type === 'date') return 'today'
  return '='
}

const normalizeMatch = (value?: string): 'and' | 'or' => {
  if (value && value.toLowerCase() === 'or') return 'or'
  return 'and'
}

const buildRow = (fields: FilterFieldConfig[], payload?: Partial<FilterRowPayload>): FilterRow => {
  const fieldValue = payload?.field ?? ''
  const config = fields.find((field) => field.value === fieldValue)
  return {
    id: getRowId(),
    field: fieldValue,
    value: payload?.value ?? '',
    valueSecondary: payload?.value_secondary ?? '',
    op: payload?.op ?? getDefaultOperator(config),
    min: payload?.value_min ?? '',
    max: payload?.value_max ?? ''
  }
}

const buildInitialRows = (fields: FilterFieldConfig[], initialFilters?: BoardFilters): FilterRow[] => {
  if (initialFilters?.filters && initialFilters.filters.length) {
    return initialFilters.filters.slice(0, MAX_FILTERS).map((filter) => buildRow(fields, filter))
  }

  if (initialFilters?.filter_field) {
    return [
      buildRow(fields, {
        field: initialFilters.filter_field,
        value: initialFilters.filter_value,
        value_secondary: initialFilters.filter_value_secondary,
        op: initialFilters.filter_op,
        value_min: initialFilters.filter_value_min,
        value_max: initialFilters.filter_value_max
      })
    ]
  }

  return [buildRow(fields)]
}

export default function OrderBoardFilter ({
  fields,
  initialFilters,
  onApply,
  onReset
}: OrderBoardFilterProps) {
  const [matchType, setMatchType] = useState<'and' | 'or'>(() => normalizeMatch(initialFilters?.filter_match))
  const [rows, setRows] = useState<FilterRow[]>(() => buildInitialRows(fields, initialFilters))

  useEffect(() => {
    setMatchType(normalizeMatch(initialFilters?.filter_match))
    setRows(buildInitialRows(fields, initialFilters))
  }, [fields, initialFilters])

  const handleFieldChange = (rowId: string, value: string) => {
    const newField = fields.find((field) => field.value === value)
    setRows((prev) => prev.map((row) => (
      row.id === rowId
        ? {
            ...row,
            field: value,
            value: '',
            valueSecondary: '',
            min: '',
            max: '',
            op: getDefaultOperator(newField)
          }
        : row
    )))
  }

  const updateRow = (rowId: string, patch: Partial<FilterRow>) => {
    setRows((prev) => prev.map((row) => (row.id === rowId ? { ...row, ...patch } : row)))
  }

  const addRow = () => {
    setRows((prev) => (prev.length >= MAX_FILTERS ? prev : [...prev, buildRow(fields)]))
  }

  const removeRow = (rowId: string) => {
    setRows((prev) => {
      const remaining = prev.filter((row) => row.id !== rowId)
      return remaining.length ? remaining : [buildRow(fields)]
    })
  }

  const handleSubmit = (event: FormEvent) => {
    event.preventDefault()

    const normalizedFilters = rows
      .filter((row) => row.field)
      .map((row) => ({
        field: row.field,
        value: row.value,
        value_secondary: row.valueSecondary,
        op: row.op,
        value_min: row.min,
        value_max: row.max
      }))
      .slice(0, MAX_FILTERS)

    if (!normalizedFilters.length) {
      onReset()
      return
    }

    onApply({
      filter_match: matchType,
      filters: normalizedFilters
    })
  }

  const handleReset = () => {
    setMatchType('and')
    setRows([buildRow(fields)])
    onReset()
  }

  return (
    <form onSubmit={handleSubmit} className="w-full">
      <div className="space-y-3 rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-white-dark/10 dark:bg-white-dark/5">
        <div className="flex flex-wrap items-end justify-between gap-3">
          <div className="flex flex-wrap items-end gap-3">
            <div className="min-w-[180px]">
              <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Match</label>
              <select
                className="form-select mt-1"
                value={matchType}
                onChange={(event) => { setMatchType(normalizeMatch(event.target.value)) }}
              >
                <option value="and">All (AND)</option>
                <option value="or">Any (OR)</option>
              </select>
            </div>
            <div className="text-xs text-slate-500">Up to {MAX_FILTERS} filters</div>
          </div>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={addRow}
            disabled={rows.length >= MAX_FILTERS}
          >
            + Add Filter
          </button>
        </div>

        {rows.map((row, index) => {
          const selectedField = fields.find((field) => field.value === row.field)
          return (
            <div
              key={row.id}
              className="flex flex-wrap items-end gap-3 rounded-md border border-slate-200/80 bg-white px-3 py-3 dark:border-white-dark/10"
            >
              <div className="min-w-[220px]">
                <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Choose a Property {rows.length > 1 ? `#${index + 1}` : ''}
                </label>
                <select
                  className="form-select mt-1"
                  value={row.field}
                  onChange={(event) => { handleFieldChange(row.id, event.target.value) }}
                >
                  <option value="">Choose a Property</option>
                  {fields.map((field) => (
                    <option key={`${row.id}-${field.value}`} value={field.value}>{field.label}</option>
                  ))}
                </select>
              </div>

              {selectedField?.type === 'text' && (
                <div className="min-w-[240px]">
                  <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">{selectedField.label}</label>
                  <input
                    className="form-input mt-1"
                    type="text"
                    placeholder={selectedField.placeholder ?? 'Enter value'}
                    value={row.value}
                    onChange={(event) => { updateRow(row.id, { value: event.target.value }) }}
                  />
                </div>
              )}

              {selectedField?.type === 'dual_text' && (
                <>
                  <div className="min-w-[220px]">
                    <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                      {selectedField.primaryLabel ?? selectedField.label}
                    </label>
                    <input
                      className="form-input mt-1"
                      type="text"
                      placeholder={selectedField.placeholder ?? 'Order name'}
                      value={row.value}
                      onChange={(event) => { updateRow(row.id, { value: event.target.value }) }}
                    />
                  </div>
                  <div className="min-w-[220px]">
                    <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                      {selectedField.secondaryLabel ?? 'Job Address'}
                    </label>
                    <input
                      className="form-input mt-1"
                      type="text"
                      placeholder={selectedField.secondaryPlaceholder ?? 'Job address'}
                      value={row.valueSecondary}
                      onChange={(event) => { updateRow(row.id, { valueSecondary: event.target.value }) }}
                    />
                  </div>
                </>
              )}

              {selectedField?.type === 'select' && (
                <div className="min-w-[220px]">
                  <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">{selectedField.label}</label>
                  <select
                    className="form-select mt-1"
                    value={row.value}
                    onChange={(event) => { updateRow(row.id, { value: event.target.value }) }}
                  >
                    <option value="">Select {selectedField.label}</option>
                    {(selectedField.options ?? []).map((option) => (
                      <option key={`${row.id}-${selectedField.value}-${option.value}`} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              {selectedField?.type === 'amount' && (
                <div className="flex flex-wrap items-end gap-3">
                  <div className="min-w-[140px]">
                    <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Operator</label>
                    <select
                      className="form-select mt-1"
                      value={row.op}
                      onChange={(event) => { updateRow(row.id, { op: event.target.value }) }}
                    >
                      {OPERATOR_OPTIONS.map((option) => (
                        <option key={option.value} value={option.value}>{option.label}</option>
                      ))}
                    </select>
                  </div>
                  {row.op === 'between'
                    ? (
                      <>
                        <div className="min-w-[140px]">
                          <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Min</label>
                          <input
                            className="form-input mt-1"
                            type="number"
                            inputMode="decimal"
                            step="0.01"
                            placeholder="Min"
                            value={row.min}
                            onChange={(event) => { updateRow(row.id, { min: event.target.value }) }}
                          />
                        </div>
                        <div className="min-w-[140px]">
                          <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Max</label>
                          <input
                            className="form-input mt-1"
                            type="number"
                            inputMode="decimal"
                            step="0.01"
                            placeholder="Max"
                            value={row.max}
                            onChange={(event) => { updateRow(row.id, { max: event.target.value }) }}
                          />
                        </div>
                      </>
                      )
                    : (
                      <div className="min-w-[200px]">
                        <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</label>
                        <input
                          className="form-input mt-1"
                          type="number"
                          inputMode="decimal"
                          step="0.01"
                          placeholder="Amount"
                          value={row.value}
                          onChange={(event) => { updateRow(row.id, { value: event.target.value }) }}
                        />
                      </div>
                      )}
                </div>
              )}

              {selectedField?.type === 'date' && (
                <div className="flex flex-wrap items-end gap-3">
                  <div className="min-w-[160px]">
                    <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Operator</label>
                    <select
                      className="form-select mt-1"
                      value={row.op}
                      onChange={(event) => { updateRow(row.id, { op: event.target.value }) }}
                    >
                      {DATE_OPERATOR_OPTIONS.map((option) => (
                        <option key={option.value} value={option.value}>{option.label}</option>
                      ))}
                    </select>
                  </div>
                  {row.op === 'between' && (
                    <>
                      <div className="min-w-[180px]">
                        <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Start</label>
                        <input
                          className="form-input mt-1"
                          type="date"
                          value={row.min}
                          onChange={(event) => { updateRow(row.id, { min: event.target.value }) }}
                        />
                      </div>
                      <div className="min-w-[180px]">
                        <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">End</label>
                        <input
                          className="form-input mt-1"
                          type="date"
                          value={row.max}
                          onChange={(event) => { updateRow(row.id, { max: event.target.value }) }}
                        />
                      </div>
                    </>
                  )}
                  {['on', 'before', 'after'].includes(row.op) && (
                    <div className="min-w-[200px]">
                      <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                      <input
                        className="form-input mt-1"
                        type="date"
                        value={row.value}
                        onChange={(event) => { updateRow(row.id, { value: event.target.value }) }}
                      />
                    </div>
                  )}
                </div>
              )}

              {rows.length > 1 && (
                <div className="flex items-end">
                  <button
                    type="button"
                    className="btn btn-outline-danger"
                    onClick={() => { removeRow(row.id) }}
                  >
                    Remove
                  </button>
                </div>
              )}
            </div>
          )
        })}

        <div className="flex items-end gap-2">
          <button type="submit" className="btn btn-primary">Filter</button>
          <button type="button" className="btn btn-outline-primary" onClick={handleReset}>Reset</button>
        </div>
      </div>
    </form>
  )
}
