interface ProductLineBadgeProps {
  productLine?: string | null
}

export default function ProductLineBadge ({ productLine }: ProductLineBadgeProps) {
  const value = typeof productLine === 'string' ? productLine.trim() : ''

  if (!value) return null

  return (
    <span className="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-200 dark:ring-indigo-400/40">
      {value}
    </span>
  )
}
