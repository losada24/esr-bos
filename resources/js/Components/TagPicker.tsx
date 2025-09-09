import React, { useMemo, useRef, useState } from 'react'

type TagColor =
  | 'none' | 'red' | 'orange' | 'amber' | 'yellow' | 'lime'
  | 'green' | 'teal' | 'sky' | 'blue' | 'indigo' | 'violet' | 'purple' | 'pink' | 'gray';

export interface TagItem { name: string, color?: TagColor | null, count?: number }

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

const DOT_BG: Record<TagColor, string> = {
  none: 'bg-slate-400',
  red: 'bg-red-500',
  orange: 'bg-orange-500',
  amber: 'bg-amber-500',
  yellow: 'bg-yellow-500',
  lime: 'bg-lime-500',
  green: 'bg-green-500',
  teal: 'bg-teal-500',
  sky: 'bg-sky-500',
  blue: 'bg-blue-500',
  indigo: 'bg-indigo-500',
  violet: 'bg-violet-500',
  purple: 'bg-purple-500',
  pink: 'bg-pink-500',
  gray: 'bg-gray-500'
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
  function remove(i: number) {
    const next = [...value]
    next.splice(i, 1)
    onChange(next)
  }

  // Teclado (Enter opcional, no requerido)
  function onKeyDown(e: React.KeyboardEvent<HTMLInputElement>) {
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
      <div className="flex flex-wrap gap-2 mb-2">
        {value.map((t, i) => {
          const c = COLOR_STYLES[(t.color as TagColor) || 'gray']
          return (
            <span
              key={`${t.name}-${i}`}
              className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${c.chip} ring-1 ${c.ring}`}
              style={{ minWidth: 64 }}
              title={t.name}
            >
              <span className="flex-1 text-center truncate">{t.name}</span>
              <button
                type="button"
                onClick={() => { remove(i) }}
                className="ml-1 inline-flex h-3 w-3 items-center justify-center rounded-full hover:bg-black/10"
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
            onClick={() => { setPaletteOpen(v => !v) }}
            className={`h-9 w-9 shrink-0 rounded-lg ring-1 ${COLOR_STYLES[color].ring} flex items-center justify-center`}
            title="Color para nuevos tags"
          >
            <span className={`h-4 w-4 rounded-full ${DOT_BG[color]}`} />
          </button>

          {paletteOpen && (
            <div
              className="absolute z-30 mt-2 left-0 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl"
              onMouseDown={(e) => { e.preventDefault() }}
              onMouseLeave={() => { setPaletteOpen(false) }}
            >
              <div className="mb-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">
                Color nuevo tag
              </div>
              <div className="grid grid-cols-8 gap-2">
                {PALETTE.map((c) => (
                  <button
                    key={c}
                    type="button"
                    aria-label={c}
                    onClick={() => { setColor(c); setPaletteOpen(false) }}
                    className={`h-7 w-7 rounded-full ring-2 ${COLOR_STYLES[c].ring} flex items-center justify-center`}
                  >
                    <span className={`h-4 w-4 rounded-full ${DOT_BG[c]}`} />
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
            className="w-full h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:ring-4 focus:ring-primary/20 focus:border-primary"
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
                    <div className="flex items-center gap-2 min-w-0">
                      {/* checkbox visual */}
                      <span className={`h-4 w-4 rounded-sm border ${isSelected ? 'bg-blue-600 border-blue-600' : 'border-slate-300'}`}>
                        {isSelected && (
                          <svg viewBox="0 0 20 20" className="h-4 w-4 text-white">
                            <path fill="currentColor" d="M8.143 13.314 4.586 9.757l1.414-1.414 2.143 2.143 5.857-5.857 1.414 1.414z"/>
                          </svg>
                        )}
                      </span>
                      <span className="truncate">{t.name}</span>
                    </div>

                    {/* contador opcional */}
                    {typeof t.count === 'number' && (
                      <span className="ml-3 text-[10px] px-1.5 py-0.5 rounded bg-black/5">
                        {t.count}
                      </span>
                    )}
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