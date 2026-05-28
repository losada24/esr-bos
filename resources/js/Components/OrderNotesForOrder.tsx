import React, { useEffect, useState } from 'react'
import EditIcon from './Icons/EditIcon'
import DeleteIcon from './Icons/DeleteIcon'

// ===== Helpers (CSRF + parseo simple) =====
const getCsrf = () =>
  (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''

const parseItem = async (res: Response) => {
  const json = await res.json()
  return (json as { data?: unknown })?.data ?? json
}

const parseList = async (res: Response) => {
  const json = await res.json()
  const data = (json as { data?: unknown })?.data ?? json
  return Array.isArray(data) ? data : []
}

// ===== Tipos =====
export interface NoteDTO {
  id: number | string
  content: string
  type?: string | null
  context_label?: string | null
  created_at: string
  user?: { name: string } | null
  can?: { update?: boolean, delete?: boolean } | null
}

export interface UiNote {
  id: number | string
  body: string
  title?: string | null
  type?: string | null
  contextLabel?: string | null
  authorName: string
  createdAt: string
  can?: { update?: boolean, delete?: boolean } | null
}

interface OrderNotesForOrderProps {
  orderId?: number | string | null
  endpointBase?: string | null
  canCreate?: boolean
  noteType?: string
  refreshKey?: number
  includeRelatedActivities?: boolean
  listTitle?: string
  emptyMessage?: string
  disabledMessage?: string
}

// ===== UI utils =====
const fmt = (iso: string) =>
  new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(
    new Date(iso)
  )

const initials = (name: string) =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((n) => n[0]?.toUpperCase())
    .join('')

const LINKABLE_PATTERN = /([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|(?:https?:\/\/|www\.)[^\s]+|(?:[A-Z0-9-]+\.)+[A-Z]{2,}(?:\/[^\s]*)?)/gi
const TRAILING_PUNCTUATION = /[.,!?;:]+$/

const splitTrailingPunctuation = (value: string) => {
  let linkText = value
  let trailingText = ''

  while (linkText.length > 0) {
    const trimmed = linkText.replace(TRAILING_PUNCTUATION, '')
    if (trimmed.length === linkText.length) {
      break
    }

    trailingText = linkText.slice(trimmed.length) + trailingText
    linkText = trimmed
  }

  while (linkText.endsWith(')')) {
    const openCount = (linkText.match(/\(/g) ?? []).length
    const closeCount = (linkText.match(/\)/g) ?? []).length
    if (closeCount <= openCount) {
      break
    }

    trailingText = `)${trailingText}`
    linkText = linkText.slice(0, -1)
  }

  return { linkText, trailingText }
}

const getLinkHref = (value: string) => {
  if (value.includes('@') && !value.startsWith('http')) {
    return `mailto:${value}`
  }

  if (/^https?:\/\//i.test(value)) {
    return value
  }

  return `https://${value}`
}

const renderLinkedText = (value: string) => {
  const parts: React.ReactNode[] = []
  let cursor = 0

  for (const match of value.matchAll(LINKABLE_PATTERN)) {
    const matchedText = match[0]
    const start = match.index ?? 0

    if (start > cursor) {
      parts.push(value.slice(cursor, start))
    }

    const { linkText, trailingText } = splitTrailingPunctuation(matchedText)

    if (linkText.length > 0) {
      parts.push(
        <a
          key={`${start}-${linkText}`}
          href={getLinkHref(linkText)}
          target="_blank"
          rel="noreferrer"
          className="font-medium text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-700"
        >
          {linkText}
        </a>
      )
    } else {
      parts.push(matchedText)
    }

    if (trailingText.length > 0) {
      parts.push(trailingText)
    }

    cursor = start + matchedText.length
  }

  if (cursor < value.length) {
    parts.push(value.slice(cursor))
  }

  return parts.length > 0 ? parts : value
}

const normalize = (n: NoteDTO): UiNote => ({
  id: n.id,
  body: n.content,
  title: n.type && !['work_team_note', 'event_note', 'call_note'].includes(n.type) ? n.type : undefined,
  type: n.type ?? null,
  contextLabel: n.context_label ?? null,
  authorName: n.user?.name ?? 'Unknown',
  createdAt: n.created_at,
  can: n.can ?? null
})

export default function OrderNotesForOrder({
  orderId,
  endpointBase,
  canCreate = true,
  noteType,
  refreshKey = 0,
  includeRelatedActivities = false,
  listTitle,
  emptyMessage,
  disabledMessage
}: OrderNotesForOrderProps) {
  const [notes, setNotes] = useState<UiNote[] | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [title, setTitle] = useState('')
  const [body, setBody] = useState('')
  const [saving, setSaving] = useState(false)

  // edición
  const [editingId, setEditingId] = useState<string | number | null>(null)
  const [editTitle, setEditTitle] = useState('')
  const [editBody, setEditBody] = useState('')
  const [savingEdit, setSavingEdit] = useState(false)
  const [deletingId, setDeletingId] = useState<string | number | null>(null)

  // GET list
  const resolvedOrderId = orderId ?? null
  const resolvedEndpoint = endpointBase ?? (resolvedOrderId !== null ? `/order/${resolvedOrderId}/notes` : null)
  const listEndpoint = resolvedEndpoint && includeRelatedActivities ? `${resolvedEndpoint}?include_related=1` : resolvedEndpoint
  const canActuallyCreate = canCreate && resolvedEndpoint !== null
  const showTitleInput = !noteType

  useEffect(() => {
    if (listEndpoint === null) {
      setNotes([])
      setLoading(false)
      return
    }

    let alive = true
    ;(async () => {
      try {
        setLoading(true)
        setError(null)
        const res = await fetch(listEndpoint, {
          credentials: 'include',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        if (!res.ok) throw new Error('No se pudieron cargar las notas')
        const list: NoteDTO[] = await parseList(res)
        if (alive) {
          let normalized = list.map(normalize)
          if (noteType) {
            normalized = normalized.filter((note) => note.type === noteType)
          }
          setNotes(normalized)
        }
      } catch (e: any) {
        if (alive) setError(e?.message ?? 'Error cargando notas')
      } finally {
        if (alive) setLoading(false)
      }
    })()
    return () => {
      alive = false
    }
  }, [listEndpoint, noteType, refreshKey])

  // CREATE
  const onSave = async (e?: React.MouseEvent | React.FormEvent) => {
    e?.preventDefault?.()
    if (!body.trim() || resolvedEndpoint === null) return
    setSaving(true)
    setError(null)

    const resolvedType = noteType ?? (title.trim() || null)
    const optimistic: UiNote = {
      id: `tmp-${Date.now()}`,
      body,
      title: showTitleInput ? (title.trim() || undefined) : undefined,
      type: resolvedType ?? null,
      authorName: 'You',
      createdAt: new Date().toISOString(),
      can: { update: true, delete: true }
    }
    setNotes((prev) => (prev ? [optimistic, ...prev] : [optimistic]))

    try {
      const res = await fetch(resolvedEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrf()
        },
        credentials: 'include',
        body: JSON.stringify({ content: body, type: resolvedType }),
      })
      if (!res.ok) throw new Error('No se pudo guardar la nota')
      const created: NoteDTO = await parseItem(res)
      const ui = normalize(created)
      setNotes((prev) => (prev ?? []).map((n) => (n.id === optimistic.id ? ui : n)))
      if (showTitleInput) {
        setTitle('')
      }
      setBody('')
    } catch (err: any) {
      setNotes((prev) => (prev ?? []).filter((n) => n.id !== optimistic.id))
      setError(err?.message ?? 'Error guardando la nota')
    } finally {
      setSaving(false)
    }
  }

  // EDIT
  const startEdit = (note: UiNote) => {
    setEditingId(note.id)
    setEditTitle(note.title ?? '')
    setEditBody(note.body)
  }

  const cancelEdit = () => {
    setEditingId(null)
    setEditTitle('')
    setEditBody('')
  }

  const saveEdit = async () => {
    if (!editingId || !editBody.trim() || resolvedEndpoint === null) return
    setSavingEdit(true)
    setError(null)

    const prev = notes
    setNotes((p) =>
      (p ?? []).map((n) => (n.id === editingId ? { ...n, body: editBody, title: showTitleInput ? (editTitle || undefined) : n.title } : n))
    )

    try {
      const res = await fetch(`${resolvedEndpoint}/${editingId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrf()
        },
        credentials: 'include',
        body: JSON.stringify({ content: editBody, type: noteType ?? (editTitle.trim() || null) }),
      })
      if (!res.ok) throw new Error('No se pudo actualizar la nota')
      const updated: NoteDTO = await parseItem(res)
      const ui = normalize(updated)
      setNotes((p) => (p ?? []).map((n) => (n.id === editingId ? ui : n)))
      cancelEdit()
    } catch (err: any) {
      setNotes(prev ?? null)
      setError(err?.message ?? 'Error actualizando la nota')
    } finally {
      setSavingEdit(false)
    }
  }

  // DELETE
  const deleteNote = async (id: string | number) => {
    if (!confirm('¿Eliminar esta nota?') || resolvedEndpoint === null) return
    setDeletingId(id)
    setError(null)

    const prev = notes
    setNotes((p) => (p ?? []).filter((n) => n.id !== id))

    try {
      const res = await fetch(`${resolvedEndpoint}/${id}`, {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrf() },
        credentials: 'include'
      })
      if (!res.ok) throw new Error('No se pudo eliminar la nota')
    } catch (err: any) {
      setNotes(prev ?? null)
      setError(err?.message ?? 'Error eliminando la nota')
    } finally {
      setDeletingId(null)
    }
  }

  if (resolvedEndpoint === null) {
    const placeholder = noteType === 'work_team_note'
      ? 'Save the order first to add and view work team notes.'
      : (disabledMessage ?? 'Save the record first to add and view notes.')
    return (
      <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        {placeholder}
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Editor */}
      {canActuallyCreate && (
        <form
          onSubmit={(e) => { e.preventDefault() }}
          className="space-y-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4"
        >
          {showTitleInput && (
            <div>
              <input
                type="text"
                placeholder="Add a title (optional)"
                value={title}
                onChange={(e) => { setTitle(e.target.value) }}
                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                aria-label="Note title"
              />
            </div>
          )}
          <textarea
            placeholder="What's this note about?"
            value={body}
            onChange={(e) => { setBody(e.target.value) }}
            rows={4}
            className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
            aria-label="Note body"
          />
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={onSave}
              disabled={saving || !body.trim()}
              className="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
            >
              {saving ? 'Saving…' : 'Save'}
            </button>
            {showTitleInput && (
              <button
                type="button"
                onClick={() => { setTitle(''); setBody('') }}
                className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
            )}
            {error && <span className="ml-auto text-sm text-red-600">{error}</span>}
          </div>
        </form>
      )}

      {/* Lista */}
      <div className="space-y-4">
        <h4 className="text-sm font-semibold uppercase tracking-wide text-slate-400">
          {listTitle ?? (noteType === 'work_team_note' ? 'Work Team Notes' : 'All Notes')}
        </h4>

        {loading && <div className="text-sm text-slate-500">Cargando notas…</div>}

        {!loading && (notes?.length ?? 0) === 0 && (
          <div className="text-sm text-slate-500">{emptyMessage ?? 'No hay notas para esta orden.'}</div>
        )}

        {!loading && notes && notes.length > 0 && (
          <ul className="space-y-3">
            {notes.map((n) => (
              <li key={n.id} className="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 shadow-sm">
                <div className="flex items-start gap-3">
                  <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-sky-500 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wide">{initials(n.authorName)}</span>
                  </span>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-baseline gap-2">
                      <span className="truncate font-medium">{n.authorName}</span>
                      <span className="text-xs text-slate-400">{fmt(n.createdAt)}</span>
                    </div>

                    {editingId === n.id
                      ? (
                      <>
                        {showTitleInput && (
                          <input
                            className="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            value={editTitle}
                            onChange={(e) => { setEditTitle(e.target.value) } }
                            placeholder="Edit title (optional)"
                          />
                        )}
                        <textarea
                          className="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                          rows={3}
                          value={editBody}
                          onChange={(e) => {setEditBody(e.target.value)}}
                        />
                      </>
                        )
                      : (
                      <>
                        {n.title && <div className="text-sm font-semibold">{n.title}</div>}
                        <div className="whitespace-pre-wrap break-words text-slate-700">
                          {renderLinkedText(n.body)}
                        </div>
                        {n.contextLabel && (
                          <div className="mt-1 text-xs font-medium text-sky-600">{n.contextLabel}</div>
                        )}
                      </>
                        )}
                  </div>

                  {/* acciones a la derecha */}
                  <div className="ml-3 flex flex-none items-start gap-2">
                    {editingId === n.id
                      ? (
                      <>
                        <button
                          type="button"
                          onClick={saveEdit}
                          disabled={savingEdit}
                          className="inline-flex items-center gap-2 rounded-lg border border-sky-600 px-3 py-1 text-xs font-semibold text-sky-600 hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                          {savingEdit ? 'Saving…' : 'Save'}
                        </button>
                        <button
                          type="button"
                          onClick={cancelEdit}
                          className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        >
                          Cancel
                        </button>
                      </>
                        )
                      : (
                      <>
                        {n.can?.update && (
                          <button
                            type="button"
                            onClick={() => {startEdit(n)}}
                            className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-sky-600 hover:bg-sky-200"
                            aria-label="Edit note"
                          >
                           <EditIcon />
                          </button>
                        )}
                        {n.can?.delete && (
                          <button
                            type="button"
                            onClick={() => {deleteNote(n.id)}}
                            disabled={deletingId === n.id}
                            className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                            aria-label="Delete note"
                          >
                             <DeleteIcon />
                          </button>
                        )}
                      </>
                        )}
                  </div>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  )
}
