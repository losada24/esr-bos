export type TagColor =
  | 'none' | 'red' | 'orange' | 'amber' | 'yellow' | 'lime'
  | 'green' | 'teal' | 'sky' | 'blue' | 'indigo' | 'violet'
  | 'purple' | 'pink' | 'gray'

const COLOR_STYLES: Record<TagColor, { bg: string, text: string }> = {
  none: { bg: 'bg-slate-200', text: 'text-slate-800' },
  red: { bg: 'bg-red-200', text: 'text-red-900' },
  orange: { bg: 'bg-orange-200', text: 'text-orange-900' },
  amber: { bg: 'bg-amber-200', text: 'text-amber-900' },
  yellow: { bg: 'bg-yellow-200', text: 'text-yellow-900' },
  lime: { bg: 'bg-lime-200', text: 'text-lime-900' },
  green: { bg: 'bg-green-200', text: 'text-green-900' },
  teal: { bg: 'bg-teal-200', text: 'text-teal-900' },
  sky: { bg: 'bg-cyan-200', text: 'text-cyan-900' },
  blue: { bg: 'bg-blue-200', text: 'text-blue-900' },
  indigo: { bg: 'bg-indigo-200', text: 'text-indigo-900' },
  violet: { bg: 'bg-purple-200', text: 'text-purple-900' },
  purple: { bg: 'bg-violet-200', text: 'text-violet-900' },
  pink: { bg: 'bg-pink-200', text: 'text-pink-900' },
  gray: { bg: 'bg-gray-200', text: 'text-gray-900' }
}

export const tagClasses = (color: TagColor = 'gray') => {
  const c = COLOR_STYLES[color] ?? COLOR_STYLES.gray
  return `inline-flex items-center rounded-full ${c.bg} ${c.text} px-3 py-1 text-xs font-semibold shadow-sm`
};
