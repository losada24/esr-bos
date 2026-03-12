import {
  PIPELINE_SORT_OPTIONS,
  type PipelineSortBy,
  type PipelineSortDir
} from '@/Utils/orderPipelineSort'

type OrderPipelineSortProps = {
  sortBy: PipelineSortBy
  sortDir: PipelineSortDir
  onSortByChange: (value: PipelineSortBy) => void
  onSortDirChange: (value: PipelineSortDir) => void
}

export default function OrderPipelineSort ({
  sortBy,
  sortDir,
  onSortByChange,
  onSortDirChange
}: OrderPipelineSortProps) {
  return (
    <div className="flex items-center gap-2 rounded-md border border-slate-200 bg-white px-2 py-1 dark:border-white-dark/20 dark:bg-white-dark/10">
      <span className="whitespace-nowrap text-xs font-semibold text-slate-600 dark:text-slate-200">Sort By</span>
      <select
        className="form-select h-8 min-w-[170px] py-0 text-sm"
        value={sortBy}
        onChange={(event) => { onSortByChange(event.target.value as PipelineSortBy) }}
      >
        {PIPELINE_SORT_OPTIONS.map(option => (
          <option key={option.value} value={option.value}>{option.label}</option>
        ))}
      </select>
      <select
        className="form-select h-8 min-w-[110px] py-0 text-sm"
        value={sortDir}
        onChange={(event) => { onSortDirChange(event.target.value as PipelineSortDir) }}
      >
        <option value="asc">Asc</option>
        <option value="desc">Desc</option>
      </select>
    </div>
  )
}
