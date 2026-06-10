export type PipelineSortBy =
  | 'order_owner'
  | 'order_name'
  | 'job_site'
  | 'company_name'
  | 'contact_name'
  | 'amount'
  | 'created_by'
  | 'created_time'
  | 'modified_time'

export type PipelineSortDir = 'asc' | 'desc'

export type PipelineSortState = {
  sort_by: PipelineSortBy
  sort_dir: PipelineSortDir
}

export type PipelineSortOption = {
  value: PipelineSortBy
  label: string
}

export const PIPELINE_SORT_OPTIONS: PipelineSortOption[] = [
  { value: 'order_owner', label: 'Order Owner' },
  { value: 'order_name', label: 'Order Name' },
  { value: 'job_site', label: 'Job Site' },
  { value: 'company_name', label: 'Company Name' },
  { value: 'contact_name', label: 'Contact Name' },
  { value: 'amount', label: 'Amount' },
  { value: 'created_by', label: 'Created By' },
  { value: 'created_time', label: 'Created Time' },
  { value: 'modified_time', label: 'Modified Time' }
]

export const DEFAULT_PIPELINE_SORT: PipelineSortState = {
  sort_by: 'modified_time',
  sort_dir: 'asc'
}

const STORAGE_KEY = 'order-pipeline-sort:v2'
const ALLOWED_SORT_BY = new Set<PipelineSortBy>(PIPELINE_SORT_OPTIONS.map(option => option.value))
const ALLOWED_SORT_DIR = new Set<PipelineSortDir>(['asc', 'desc'])

export const normalizePipelineSortBy = (value?: string | null): PipelineSortBy => {
  if (value && ALLOWED_SORT_BY.has(value as PipelineSortBy)) {
    return value as PipelineSortBy
  }

  return DEFAULT_PIPELINE_SORT.sort_by
}

export const normalizePipelineSortDir = (value?: string | null): PipelineSortDir => {
  const normalized = typeof value === 'string' ? value.toLowerCase() : ''
  if (ALLOWED_SORT_DIR.has(normalized as PipelineSortDir)) {
    return normalized as PipelineSortDir
  }

  return DEFAULT_PIPELINE_SORT.sort_dir
}

export const normalizePipelineSort = (value?: Partial<{ sort_by?: string | null, sort_dir?: string | null }> | null): PipelineSortState => {
  return {
    sort_by: normalizePipelineSortBy(value?.sort_by),
    sort_dir: normalizePipelineSortDir(value?.sort_dir)
  }
}

export const readStoredPipelineSort = (): PipelineSortState | null => {
  if (typeof window === 'undefined') return null

  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw) as Partial<PipelineSortState> | null
    if (!parsed) return null

    return normalizePipelineSort(parsed)
  } catch {
    return null
  }
}

export const storePipelineSort = (sort: PipelineSortState): void => {
  if (typeof window === 'undefined') return

  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(sort))
  } catch {
    // Ignore storage write failures.
  }
}

export const hasPipelineSortInUrl = (search?: string): boolean => {
  const query = typeof search === 'string'
    ? search
    : (typeof window !== 'undefined' ? window.location.search : '')

  if (!query) return false

  const params = new URLSearchParams(query)
  return params.has('sort_by') || params.has('sort_dir')
}
