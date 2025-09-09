export type TagColor =
  | 'none' | 'red' | 'orange' | 'amber' | 'yellow' | 'lime'
  | 'green' | 'teal' | 'sky' | 'blue' | 'indigo' | 'violet'
  | 'purple' | 'pink' | 'gray'

const COLOR_STYLES: Record<TagColor, { chip: string, ring: string, text: string }> = {
  none: { chip: 'bg-slate-100 text-slate-700', ring: 'ring-slate-200', text: 'text-slate-700' },
  red: { chip: 'bg-red-100 text-red-800', ring: 'ring-red-200', text: 'text-red-800' },
  orange: { chip: 'bg-orange-100 text-orange-800', ring: 'ring-orange-200', text: 'text-orange-800' },
  amber: { chip: 'bg-amber-100 text-amber-800', ring: 'ring-amber-200', text: 'text-amber-800' },
  yellow: { chip: 'bg-yellow-100 text-yellow-800', ring: 'ring-yellow-200', text: 'text-yellow-800' },
  lime: { chip: 'bg-lime-100 text-lime-800', ring: 'ring-lime-200', text: 'text-lime-800' },
  green: { chip: 'bg-green-100 text-green-800', ring: 'ring-green-200', text: 'text-green-800' },
  teal: { chip: 'bg-teal-100 text-teal-800', ring: 'ring-teal-200', text: 'text-teal-800' },
  sky: { chip: 'bg-sky-100 text-sky-800', ring: 'ring-sky-200', text: 'text-sky-800' },
  blue: { chip: 'bg-blue-100 text-blue-800', ring: 'ring-blue-200', text: 'text-blue-800' },
  indigo: { chip: 'bg-indigo-100 text-indigo-800', ring: 'ring-indigo-200', text: 'text-indigo-800' },
  violet: { chip: 'bg-violet-100 text-violet-800', ring: 'ring-violet-200', text: 'text-violet-800' },
  purple: { chip: 'bg-purple-100 text-purple-800', ring: 'ring-purple-200', text: 'text-purple-800' },
  pink: { chip: 'bg-pink-100 text-pink-800', ring: 'ring-pink-200', text: 'text-pink-800' },
  gray: { chip: 'bg-gray-100 text-gray-800', ring: 'ring-gray-200', text: 'text-gray-800' }
}

export const tagClasses = (color: TagColor = 'gray') => {
  const c = COLOR_STYLES[color] ?? COLOR_STYLES.gray
  return `inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ${c.chip} ${c.ring}`
};