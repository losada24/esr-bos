const DATE_ONLY_PATTERN = /^(\d{4})-(\d{2})-(\d{2})/

export const formatDateOnlyValue = (value: Date): string => {
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export const parseDateOnly = (value?: string | Date | null): Date | null => {
  if (!value) return null

  if (value instanceof Date) {
    if (Number.isNaN(value.getTime())) return null
    return new Date(value.getFullYear(), value.getMonth(), value.getDate())
  }

  const normalized = String(value).trim()
  if (!normalized) return null

  const match = normalized.match(DATE_ONLY_PATTERN)
  if (match) {
    const [, yearStr, monthStr, dayStr] = match
    const year = Number(yearStr)
    const monthIndex = Number(monthStr) - 1
    const day = Number(dayStr)
    const date = new Date(year, monthIndex, day)
    return Number.isNaN(date.getTime()) ? null : date
  }

  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? null : date
}

export const toDateOnlyString = (value?: string | Date | null): string | null => {
  if (!value) return null
  const date = parseDateOnly(value)
  return date ? formatDateOnlyValue(date) : String(value).trim() || null
}

export const formatDateOnlyDisplay = (value?: string | Date | null): string | null => {
  if (!value) return null
  const date = parseDateOnly(value)
  return date ? date.toLocaleDateString() : String(value)
}

export const isDateOnlyPast = (value?: string | Date | null): boolean => {
  const date = parseDateOnly(value)
  if (!date) return false

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return date.getTime() < today.getTime()
}
