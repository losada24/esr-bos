import { useEffect, useMemo, useRef, useState } from 'react'
import { router } from '@inertiajs/react'
import SearchIcon from '@/Components/Icons/SearchIcon'

const MODULE_OPTIONS = [
  { value: 'all', label: 'All' },
  { value: 'frontdesk', label: 'Frontdesk' },
  { value: 'sales', label: 'Sales' },
  { value: 'order_processing', label: 'Order Processing' }
] as const

type ModuleValue = typeof MODULE_OPTIONS[number]['value']

export type OrderSearchResult = {
  id: number
  name: string | null
  status: string | null
  client: string | null
}

type OrderGlobalSearchProps = {
  origin: ModuleValue
  className?: string
}

const OrderGlobalSearch = ({ origin, className = '' }: OrderGlobalSearchProps) => {
  const [query, setQuery] = useState('')
  const [module, setModule] = useState<ModuleValue>('all')
  const [results, setResults] = useState<OrderSearchResult[]>([])
  const [loading, setLoading] = useState(false)
  const [open, setOpen] = useState(false)
  const [activeIndex, setActiveIndex] = useState(-1)
  const containerRef = useRef<HTMLDivElement | null>(null)
  const latestRequestRef = useRef(0)

  const hasQuery = query.trim().length > 0

  const moduleLabel = useMemo(() => {
    return MODULE_OPTIONS.find((option) => option.value === module)?.label ?? 'All'
  }, [module])

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (!containerRef.current || containerRef.current.contains(event.target as Node)) return
      setOpen(false)
      setActiveIndex(-1)
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [])

  useEffect(() => {
    if (!hasQuery) {
      setResults([])
      setLoading(false)
      setActiveIndex(-1)
      return
    }

    const requestId = latestRequestRef.current + 1
    latestRequestRef.current = requestId
    setLoading(true)

    const handle = window.setTimeout(async () => {
      try {
        const url = route('order.search', {
          q: query.trim(),
          module,
          origin
        })
        const response = await fetch(url, { headers: { Accept: 'application/json' } })
        if (!response.ok) {
          throw new Error('Search request failed')
        }
        const payload = await response.json()
        if (latestRequestRef.current !== requestId) return
        const nextResults = Array.isArray(payload?.results) ? payload.results : []
        setResults(nextResults)
        setActiveIndex(nextResults.length ? 0 : -1)
      } catch (error) {
        if (latestRequestRef.current === requestId) {
          setResults([])
          setActiveIndex(-1)
        }
        console.error(error)
      } finally {
        if (latestRequestRef.current === requestId) {
          setLoading(false)
        }
      }
    }, 250)

    return () => {
      window.clearTimeout(handle)
    }
  }, [query, module, origin, hasQuery])

  const goToOrder = (orderId: number) => {
    if (!orderId) return
    router.visit(route('frontdesk.order_view', { id: orderId }))
    setOpen(false)
  }

  const onKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (!open) return
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      setActiveIndex((prev) => Math.min(prev + 1, results.length - 1))
      return
    }
    if (event.key === 'ArrowUp') {
      event.preventDefault()
      setActiveIndex((prev) => Math.max(prev - 1, 0))
      return
    }
    if (event.key === 'Enter') {
      if (activeIndex >= 0 && results[activeIndex]) {
        event.preventDefault()
        goToOrder(results[activeIndex].id)
      }
      return
    }
    if (event.key === 'Escape') {
      setOpen(false)
      setActiveIndex(-1)
    }
  }

  const showDropdown = open && (hasQuery || results.length > 0 || loading)

  return (
    <div ref={containerRef} className={`relative ${className}`}>
      <div className="flex items-stretch overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-white-dark/20 dark:bg-[#0b1220]">
        <div className="relative">
          <select
            value={module}
            onChange={(event) => {
              const value = event.target.value as ModuleValue
              setModule(value)
              if (hasQuery) setOpen(true)
            }}
            className="h-full cursor-pointer appearance-none border-0 border-r border-slate-200 bg-transparent px-3 py-2 text-sm font-semibold text-slate-700 focus:outline-none dark:border-white-dark/20 dark:text-white"
            aria-label="Search module"
          >
            {MODULE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
          </select>
          <span className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-500 dark:text-white/60">
            v
          </span>
        </div>
        <div className="relative flex-1">
          <input
            type="text"
            value={query}
            placeholder={`Search ${moduleLabel.toLowerCase()} orders...`}
            onFocus={() => { setOpen(true) }}
            onChange={(event) => {
              setQuery(event.target.value)
              setOpen(true)
            }}
            onKeyDown={onKeyDown}
            className="w-full bg-transparent px-3 py-2 pr-10 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none dark:text-white"
          />
          <SearchIcon className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 opacity-70" />
        </div>
      </div>

      {showDropdown && (
        <div className="absolute left-0 right-0 z-50 mt-2 max-h-80 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-white-dark/20 dark:bg-[#0b1220]">
          {loading ? (
            <div className="px-4 py-3 text-sm text-slate-500 dark:text-white/70">Searching...</div>
          ) : null}

          {!loading && results.length === 0 ? (
            <div className="px-4 py-3 text-sm text-slate-500 dark:text-white/70">No results found.</div>
          ) : null}

          {!loading && results.length > 0 ? (
            <div className="py-1">
              {results.map((result, index) => (
                <button
                  key={result.id}
                  type="button"
                  onClick={() => { goToOrder(result.id) }}
                  className={`flex w-full items-center justify-between gap-3 px-4 py-2 text-left transition hover:bg-slate-50 dark:hover:bg-white/5 ${index === activeIndex ? 'bg-slate-50 dark:bg-white/5' : ''}`}
                >
                  <div className="min-w-0">
                    <div className="truncate text-sm font-semibold text-slate-800 dark:text-white">
                      {result.name ?? 'Order'}
                    </div>
                    <div className="truncate text-xs text-slate-500 dark:text-white/60">
                      {result.client ?? 'No client'}
                    </div>
                  </div>
                  <span className="shrink-0 rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:border-white-dark/30 dark:bg-white/10 dark:text-white/80">
                    {result.status ?? 'Unknown'}
                  </span>
                </button>
              ))}
            </div>
          ) : null}
        </div>
      )}
    </div>
  )
}

export default OrderGlobalSearch
