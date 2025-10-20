import React, { useMemo, useRef, useState } from 'react'

type TagColor =
  | 'none' | 'red' | 'orange' | 'amber' | 'yellow' | 'lime'
  | 'green' | 'teal' | 'sky' | 'blue' | 'indigo' | 'violet' | 'purple' | 'pink' | 'gray'

export interface TagItem { name: string, color?: TagColor | null, count?: number }

const COLOR_TOKENS: Record<TagColor, {
  bg: string
  text: string
  dot: string
  focus: string
}> = {
  none: { bg: 'bg-slate-200', text: 'text-slate-800', dot: 'bg-slate-500', focus: 'focus:ring-slate-200/60' },
  red: { bg: 'bg-red-200', text: 'text-red-900', dot: 'bg-red-500', focus: 'focus:ring-red-200/60' },
  orange: { bg: 'bg-orange-200', text: 'text-orange-900', dot: 'bg-orange-500', focus: 'focus:ring-orange-200/60' },
  amber:{ bg: 'bg-amber-200', text: 'text-amber-900', dot: 'bg-amber-500', focus: 'focus:ring-amber-200/60' },
  yellow: { bg: 'bg-yellow-200', text: 'text-yellow-900', dot: 'bg-yellow-500', focus: 'focus:ring-yellow-200/60' },
  lime: { bg: 'bg-lime-200', text: 'text-lime-900', dot: 'bg-lime-500', focus: 'focus:ring-lime-200/60' },
  green: { bg: 'bg-green-200', text: 'text-green-900', dot: 'bg-green-500', focus: 'focus:ring-green-200/60' },
  teal: { bg: 'bg-teal-200', text: 'text-teal-900', dot: 'bg-teal-500', focus: 'focus:ring-teal-200/60' },
  sky: { bg: 'bg-cyan-200', text: 'text-cyan-900', dot: 'bg-cyan-500', focus: 'focus:ring-cyan-200/60' },
  blue: { bg: 'bg-blue-200', text: 'text-blue-900', dot: 'bg-blue-500', focus: 'focus:ring-blue-200/60' },
  indigo: { bg: 'bg-indigo-200', text: 'text-indigo-900', dot: 'bg-indigo-500', focus: 'focus:ring-indigo-200/60' },
  violet: { bg: 'bg-purple-200', text: 'text-purple-900', dot: 'bg-purple-500', focus: 'focus:ring-purple-200/60' },
  purple: { bg: 'bg-violet-200', text: 'text-violet-900', dot: 'bg-violet-500', focus: 'focus:ring-violet-200/60' },
  pink: { bg: 'bg-pink-200', text: 'text-pink-900', dot: 'bg-pink-500', focus: 'focus:ring-pink-200/60' },
  gray: { bg: 'bg-gray-200', text: 'text-gray-900', dot: 'bg-gray-500', focus: 'focus:ring-gray-200/60' }
}

const PALETTE: TagColor[] = ['none', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'teal', 'sky', 'blue', 'indigo', 'violet', 'purple', 'pink', 'gray']

export default function TagPicker({
  value,
  onChange,
  placeholder = 'Agregar o buscar tag',
  maxLength = 28,
  suggestions = [],
  allowCreate = true
}: {
  value: TagItem[]
  onChange: (tags: TagItem[]) => void
  placeholder?: string
  maxLength?: number
  suggestions?: TagItem[]
  allowCreate?: boolean
}) {
  const [text, setText] = useState('')
  const [color, setColor] = useState<TagColor>('gray')// color para NUEVOS tags
  const [open, setOpen] = useState(false) // dropdown de sugerencias (multi)
  const [paletteOpen, setPaletteOpen] = useState(false)// popover de colores
  const inputRef = useRef<HTMLInputElement>(null)

  const normalized = (s: string) => s.trim().toLowerCase()

  // Para evitar duplicados por nombre (case-insensitive)
  const selectedNames = useMemo(
    () => new Set(value.map(v => normalized(v.name))),
    [value]
  )

  // Filtra sugerencias por texto y quita las ya seleccionadas (puedes permitir re-deselección visual)
  const filteredSuggestions = useMemo(() => {
    const q = normalized(text)
    return suggestions
      .filter(s => (q ? normalized(s.name).includes(q) : true))
      .sort((a, b) => (b.count ?? 0) - (a.count ?? 0))
      .slice(0, 40)
  }, [suggestions, text])

  const canCreate = allowCreate && text.trim().length > 0 && !selectedNames.has(normalized(text))

  // Agregar nuevo (sin Enter)
  function createNew() {
    const name = text.trim()
    if (!name || selectedNames.has(normalized(name))) return
    onChange([...value, { name, color }])
    setText('') // limpia para seguir creando
    setOpen(true) // mantiene abierto para multi selección
    inputRef.current?.focus()
  }

  // Toggle de sugerencia (multi-select). Si está, quita; si no, agrega.
  function toggleSuggestion(t: TagItem) {
    const key = normalized(t.name)
    if (selectedNames.has(key)) {
      // remover
      const next = value.filter(v => normalized(v.name) !== key)
      onChange(next)
    } else {
      // agregar (mantiene su color guardado o 'gray')
      onChange([...value, { name: t.name, color: (t.color as TagColor) ?? 'gray' }])
    }
    setOpen(true) // mantener abierto para seguir seleccionando
    inputRef.current?.focus()
  }

  // Quitar chip
  function remove (i: number) {
    const next = [...value]
    next.splice(i, 1)
    onChange(next)
  }

  // Teclado (Enter opcional, no requerido)
  function onKeyDown (e: React.KeyboardEvent<HTMLInputElement>) {
    if (e.key === 'Enter') {
      e.preventDefault()
      if (canCreate) createNew()
    } else if (e.key === 'Escape') {
      if (text) setText('')
      else (e.currentTarget as HTMLInputElement).blur()
      setOpen(false)
    } else if (e.key === 'ArrowDown') {
      setOpen(true)
    }
  }

  return (
    <div className="w-full">
      {/* Chips seleccionados (pequeños, sin círculo, centrados) */}
      <div className="mb-3 flex flex-wrap gap-2">
        {value.map((t, i) => {
          const palette = COLOR_TOKENS[(t.color as TagColor) || 'gray']
          return (
            <span
              key={`${t.name}-${i}`}
              className={`group inline-flex items-center gap-2 rounded-full ${palette.bg} ${palette.text} px-3 py-1 text-xs font-semibold shadow-sm transition`}
              style={{ minWidth: 66 }}
              title={t.name}
            >
              <span className={`h-2.5 w-2.5 rounded-full ${palette.dot} shadow-sm shadow-black/15`} />
              <span className="flex-1 truncate">{t.name}</span>
              <button
                type="button"
                onClick={() => { remove(i) }}
                className="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-black/10 text-slate-600 transition group-hover:bg-black/15 group-hover:text-slate-800"
                aria-label={`Quitar ${t.name}`}
              >
                <svg viewBox="0 0 20 20" className="h-3 w-3" fill="currentColor">
                  <path
                    fillRule="evenodd"
                    d="M10 8.586 5.757 4.343 4.343 5.757 8.586 10l-4.243 4.243 1.414 1.414L10 11.414l4.243 4.243 1.414-1.414L11.414 10l4.243-4.243-1.414-1.414L10 8.586Z"
                    clipRule="evenodd"
                  />
                </svg>
              </button>
            </span>
          )
        })}
      </div>

      {/* Fila: botón de color (para nuevos) + input con dropdown del MISMO ancho */}
      <div className="flex items-center gap-2">
        {/* Botón de color para NUEVOS tags */}
        <div className="relative">
          <button
            type="button"
            onMouseDown={(e) => e.preventDefault()}
            onClick={() => { setPaletteOpen(v => !v) }}
            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white text-slate-500 shadow-sm transition hover:shadow-md focus:outline-none ${COLOR_TOKENS[color].focus}`}
            title="Color para nuevos tags"
          >
            <span className={`block h-4 w-4 rounded-full ${COLOR_TOKENS[color].dot} shadow-sm shadow-black/20`} />
          </button>

          {paletteOpen && (
            <div
              className="absolute left-0 z-30 mt-2 w-64 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-2xl backdrop-blur"
              onMouseDown={(e) => { e.preventDefault() }}
            >
              <div className="mb-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">
                Color nuevo tag
              </div>
              <div className="grid grid-cols-8 gap-2">
                {PALETTE.map((c) => (
                  <button
                    key={c}
                    type="button"
                    aria-label={c}
                    onClick={() => { setColor(c); setPaletteOpen(false) }}
                    className={`relative flex h-8 w-8 items-center justify-center rounded-full border border-white/60 bg-white/70 shadow-sm transition hover:-translate-y-0.5 hover:shadow focus:outline-none ${COLOR_TOKENS[c].focus}`}
                  >
                    <span className={`block h-5 w-5 rounded-full ${COLOR_TOKENS[c].dot} shadow-inner shadow-black/20`} />
                    {color === c && (
                      <svg viewBox="0 0 20 20" className="absolute h-3.5 w-3.5 text-white">
                        <path fill="currentColor" d="M8.143 13.314 4.586 9.757l1.414-1.414 2.143 2.143 5.857-5.857 1.414 1.414z"/>
                      </svg>
                    )}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Wrapper relativo SOLO del input (dropdown mismo ancho) */}
        <div className="relative flex-1">
          <input
            ref={inputRef}
            value={text}
            onChange={(e) => { setText(e.target.value.slice(0, maxLength)); setOpen(true) }}
            onKeyDown={onKeyDown}
            onFocus={() => { setOpen(true) }}
            onBlur={() => setTimeout(() => { setOpen(false) }, 120)}
            placeholder={placeholder}
            className="h-10 w-full rounded-xl border border-slate-200 bg-white/90 px-3 text-sm text-slate-600 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
            role="combobox"
            aria-expanded={open}
            aria-controls="tagpicker-options"
          />

          {/* Dropdown: multi-select + crear, MISMO ancho del input */}
          {open && (canCreate || filteredSuggestions.length > 0) && (
            <ul
              id="tagpicker-options"
              role="listbox"
              className="absolute left-0 top-full mt-1 z-40 w-full max-h-60 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg"
              onMouseDown={(e) => { e.preventDefault() }}
            >
              {/* Crear nuevo (sin Enter) */}
              {canCreate && (
                <li
                  role="option"
                  onClick={createNew}
                  className="px-3 py-2 text-sm cursor-pointer hover:bg-slate-50 flex items-center gap-2"
                >
                  <span className="inline-flex h-4 w-4 items-center justify-center rounded border border-slate-300">+</span>
                  Crear “{text.trim()}”
                </li>
              )}

              {/* Sugerencias seleccionables (multi) */}
              {filteredSuggestions.map((t, i) => {
                const isSelected = selectedNames.has(normalized(t.name))
                return (
                  <li
                    key={`${t.name}-${i}`}
                    role="option"
                    aria-selected={isSelected}
                    onClick={() => { toggleSuggestion(t) }}
                    className={`px-3 py-2 text-sm cursor-pointer flex items-center justify-between ${
                      isSelected ? 'bg-slate-100' : 'hover:bg-slate-50'
                    }`}
                    title={t.name}
                  >
                    <div className="flex min-w-0 items-center gap-3">
                      <span className={`h-2.5 w-2.5 rounded-full ${COLOR_TOKENS[(t.color as TagColor) || 'gray'].dot} shadow-sm`} />
                      <span className={`truncate font-medium ${COLOR_TOKENS[(t.color as TagColor) || 'gray'].text}`}>{t.name}</span>
                    </div>

                    {/* contador opcional */}
                    <div className="flex items-center gap-2 pl-3">
                      {typeof t.count === 'number' && (
                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                          {t.count}
                        </span>
                      )}
                      {isSelected && (
                        <span className="inline-flex h-5 w-5 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                          <svg viewBox="0 0 20 20" className="h-3 w-3" fill="currentColor">
                            <path d="M8.143 13.314 4.586 9.757l1.414-1.414 2.143 2.143 5.857-5.857 1.414 1.414z" />
                          </svg>
                        </span>
                      )}
                    </div>
                  </li>
                )
              })}
            </ul>
          )}
        </div>
      </div>
    </div>
  )
}
