import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm } from '@inertiajs/react'
import { type PageProps } from '@/types'
import { type ChangeEvent, type FormEvent, KeyboardEvent, useRef, useState } from 'react'
import type { ComponentType, SVGProps } from 'react'
import { type Attachment, type OrderStatus } from '@/types/interfaces/order'
import TagPicker, { type TagItem } from '@/Components/TagPicker'
import UserIcon from '@/Components/Icons/UserIcon'
import { type Order } from './OrderCommon'
import LocationIcon from '@/Components/Icons/LocationIcon'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import EmailIcon from '@/Components/Icons/EmailIcon'
import ShareIcon from '@/Components/Icons/ShareIcon'
import CrownIcon from '@/Components/Icons/CrownIcon'
import DotsIcon from '@/Components/Icons/DotsIcon'
import CalendarIcon from '@/Components/Icons/CalendarIcon'
import BookIcon from '@/Components/Icons/BookIcon'
import FolderIcon from '@/Components/Icons/FolderIcon'
import ExportIcon from '@/Components/Icons/ExportIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import OrderNotesForOrder from '@/Components/OrderNotesForOrder'

type IndexOrderProps = PageProps & {
  orderStatuses?: OrderStatus[]
  order: Order
  tags: TagItem[]
  usedTags: TagItem[]
}

type TabKey = 'home' | 'profile' | 'contact' | 'sales' | 'attachments'

export default function ShowStatusOrder ({ auth, orderStatuses = [], tags = [], order, usedTags = [] }: IndexOrderProps) {
  const safeOrderStatuses = Array.isArray(orderStatuses) ? orderStatuses : []
  const safeTags = Array.isArray(tags) ? tags : []
  const safeUsedTags = Array.isArray(usedTags) ? usedTags : []
  const [tab, setTab] = useState<TabKey>('home')
  const authUserId = auth?.user?.id ?? null

  type DetailIcon = ComponentType<SVGProps<SVGSVGElement>>

  const contactDetails: Array<{ label: string, value?: string | null, fallback: string, Icon: DetailIcon }> = [
    { label: 'Contact Name', value: order.client?.name, fallback: 'No contact assigned', Icon: UserIcon },
    { label: 'Phone', value: order.client?.phone, fallback: 'No phone available', Icon: PhoneIcon },
    { label: 'Email', value: order.client?.email, fallback: 'No email available', Icon: EmailIcon }
  ]

  const jobLocation = [order.job_address, order.city, order.job_state, order.job_zip].filter(Boolean).join(', ')
  const sourceName = order.client?.source
  const descriptionText = order.description?.trim()
  const rawCompany = order.client?.company_contact as unknown
  const companyContacts = rawCompany
    ? (Array.isArray(rawCompany) ? rawCompany : [rawCompany])
    : []
  const ownerNames = Array.isArray(order.owners)
    ? order.owners.map((owner: any) => owner?.name).filter(Boolean)
    : []
  const primaryOwnerDisplay = ownerNames.length > 0
    ? ownerNames.join(', ')
    : (order.user?.name ?? '')
  const scheduleAppointmentLabel = order.schedule_appointment
    ? new Date(order.schedule_appointment).toLocaleString()
    : null

  const initialAttachments = Array.isArray(order.attachments) ? order.attachments : []
  const [attachments, setAttachments] = useState<Attachment[]>(initialAttachments)
  const [newFiles, setNewFiles] = useState<File[]>([])
  const [uploading, setUploading] = useState(false)
  const [uploadError, setUploadError] = useState<string | null>(null)
  const [deleteError, setDeleteError] = useState<string | null>(null)
  const [deletingIds, setDeletingIds] = useState<number[]>([])
  const fileInputRef = useRef<HTMLInputElement | null>(null)
  const attachmentDateFormatter = useRef(
    typeof window !== 'undefined'
      ? new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
      : null
  )

  const handleFileSelection = (event: ChangeEvent<HTMLInputElement>) => {
    setUploadError(null)
    const files = event.target.files ? Array.from(event.target.files) : []
    setNewFiles(files)
  }

  const resetFileInput = () => {
    setNewFiles([])
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  const handleAttachmentUpload = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setUploadError(null)

    if (newFiles.length === 0) {
      setUploadError('Selecciona al menos un archivo para subir.')
      return
    }

    setUploading(true)

    try {
      const formData = new FormData()
      newFiles.forEach(file => formData.append('attachments[]', file))

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

      const response = await fetch(route('order.attachments.store', order.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: formData
      })

      const data = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && data?.errors) {
          const messages = Object.values(data.errors as Record<string, string[]>).flat()
          throw new Error(messages[0] ?? 'No se pudieron subir los archivos.')
        }

        throw new Error(data?.message ?? 'No se pudieron subir los archivos.')
      }

      const attachmentList = Array.isArray(data?.attachments) ? data.attachments as Attachment[] : []
      setAttachments(attachmentList)
      setDeleteError(null)
      resetFileInput()
    } catch (error: any) {
      console.error('upload attachments error', error)
      setUploadError(error?.message ?? 'No se pudieron subir los archivos.')
    } finally {
      setUploading(false)
    }
  }

  const handleAttachmentDelete = async (attachmentId: number) => {
    setDeleteError(null)
    setDeletingIds(prev => [...prev, attachmentId])

    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

      const response = await fetch(route('order.drop_attachment', attachmentId), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': token
        }
      })

      const data = await response.json().catch(() => null)

      if (!response.ok) {
        throw new Error(data?.message ?? 'No se pudo eliminar el archivo.')
      }

      setAttachments(prev => prev.filter(attachment => attachment.id !== attachmentId))
    } catch (error: any) {
      console.error('delete attachment error', error)
      setDeleteError(error?.message ?? 'No se pudo eliminar el archivo.')
    } finally {
      setDeletingIds(prev => prev.filter(id => id !== attachmentId))
    }
  }

  const tabs: Array<{ key: TabKey, label: string, Icon: DetailIcon }> = [
    { key: 'home', label: 'Notes', Icon: EmailIcon },
    { key: 'profile', label: 'Profile', Icon: UserIcon },
    { key: 'contact', label: 'Contact', Icon: PhoneIcon },
    { key: 'sales', label: 'Sales Form', Icon: BookIcon },
    { key: 'attachments', label: 'Attachments', Icon: FolderIcon }
  ]

  const projectAmountNumber = Number(order.project_amount ?? 0)
  const showProjectAmount = Number.isFinite(projectAmountNumber) && projectAmountNumber > 1
  const formattedProjectAmount = showProjectAmount
    ? `$${projectAmountNumber.toLocaleString()}`
    : null

  const onKeyDown = (e: KeyboardEvent<HTMLUListElement>) => {
    const idx = tabs.findIndex((t) => t.key === tab)
    if (e.key === 'ArrowRight') {
      setTab(tabs[(idx + 1) % tabs.length].key)
    } else if (e.key === 'ArrowLeft') {
      setTab(tabs[(idx - 1 + tabs.length) % tabs.length].key)
    }
  }

  const { data, setData, processing, patch } = useForm<{ tags: TagItem[] }>({
    tags: safeTags
  })
  const selectedTagCount = data.tags?.length ?? 0
  const statusCount = safeOrderStatuses.length

  function submit (e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    // ruta PATCH para actualizar solo tags del pedido
    patch(route('frontdesk.tags_update', order.id), { preserveScroll: true })
  }
  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={`${order.name}${order.order_type ? ` (${order.order_type})` : ''}`}
    >
      <Head title="Order View" />

      <div className="px-4 pb-10 lg:px-6">
        <div className="space-y-6">
          <div className="panel flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div className="flex items-start gap-4">
              <span className="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                <CrownIcon className="h-6 w-6" />
              </span>
              <div className="space-y-2">
                <div className="space-y-1">
                  <h1 className="text-xl font-semibold text-slate-800">
                    {order.name}
                  </h1>
                  <div className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    {order.order_type && (
                      <span className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {order.order_type}
                      </span>
                    )}
                    {primaryOwnerDisplay && (
                      <span className="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                        <UserIcon className="h-4 w-4 text-slate-400" />
                        {primaryOwnerDisplay}
                      </span>
                    )}
                    {scheduleAppointmentLabel && (
                      <span className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">
                        <CalendarIcon className="h-3.5 w-3.5 text-slate-400" />
                        {scheduleAppointmentLabel}
                      </span>
                    )}
                  </div>
                </div>
                {/* {order.notes && (
                    <p className="text-sm text-slate-500">
                      {order.notes}
                    </p>
                  )} */}
              </div>
            </div>
            <div className="flex flex-col items-start gap-2 md:items-end">
              <span className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-4 py-1.5 text-sm font-semibold text-sky-600">
                <DotsIcon className="h-4 w-4" />
                {order.status ?? 'No status'}
              </span>
              {showProjectAmount && (
                <span className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 md:text-right">
                  <span>Project Amount</span>
                  <span className="text-emerald-800">{formattedProjectAmount}</span>
                </span>
              )}
            </div>
          </div>

          <div className="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside className="space-y-6">
              <div className="panel space-y-4">
                <div className="flex items-center justify-between">
                  <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Related Contact</h2>
                </div>
                <div className="space-y-3">
                  {contactDetails.map(({ label, value, fallback, Icon }) => (
                    <div
                      key={label}
                      className="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-3 shadow-sm"
                    >
                      <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-sky-500 shadow-sm">
                        <Icon className="h-4 w-4" />
                      </span>
                      <div className="flex-1 text-sm">
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                        <p className="font-medium text-slate-700">
                          {value ?? <span className="text-slate-400">{fallback}</span>}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>

              {order.order_type?.toLowerCase() === 'commercial' && companyContacts.length > 0 && (
                  <div className="mt-4 space-y-3 rounded-xl border border-slate-200/80 bg-slate-50 p-3 shadow-sm">
                    <div className="flex items-center justify-between">
                      <h3 className="text-xs font-semibold uppercase tracking-wide text-slate-400">Related Company</h3>
                      <span className="text-[10px] font-medium text-slate-400">{companyContacts.length} linked</span>
                    </div>
                    <div className="space-y-3">
                      {companyContacts.map((company, index) => (
                        <div key={company.id ?? index} className="space-y-2 rounded-lg bg-white/70 p-3 shadow">
                          <p className="text-sm font-semibold text-slate-700">{company.name}</p>
                          {company.bid_due_date && (
                            <div className="flex items-center justify-between rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                              <span>Bid Due Date</span>
                              <span className="text-slate-700 normal-case">
                                {new Date(company.bid_due_date).toLocaleDateString()}
                              </span>
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              <form onSubmit={submit} className="panel space-y-4">
                <div className="flex items-center justify-between">
                  <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Tags</h2>
                  <span className="text-xs text-slate-400">{selectedTagCount} selected</span>
                </div>
                <TagPicker
                  value={data.tags}
                  onChange={(t) => { setData('tags', t) }}
                  placeholder="Agregar tag"
                  suggestions={safeUsedTags}
                />
                <div className="flex justify-end">
                  <button
                    type="submit"
                    disabled={processing}
                    className="btn btn-sm btn-primary disabled:opacity-60"
                  >
                    {processing
                      ? (
                        <svg viewBox="0 0 24 24" className="h-4 w-4 animate-spin" fill="none">
                          <circle cx="12" cy="12" r="9" stroke="currentColor" strokeOpacity="0.2" strokeWidth="3" />
                          <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" strokeWidth="3" />
                        </svg>
                        )
                      : (
                        <span className="flex items-center gap-2">
                          Guardar
                          <svg viewBox="0 0 20 20" className="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M8.5 13.5 4.9 10l1.2-1.2 2.4 2.3 5-5L15.7 7l-6 6.5z" />
                          </svg>
                        </span>
                      )}
                  </button>
                </div>
              </form>

              <div className="panel space-y-3">
                <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Description</h2>
                {descriptionText
                  ? <p className="text-sm leading-relaxed text-slate-600">{descriptionText}</p>
                  : <p className="text-sm text-slate-400">No description available.</p>}
              </div>

              <div className="panel space-y-3">
                <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Job Site</h2>
                {jobLocation
                  ? (
                    <div className="flex items-start gap-3 text-sm text-slate-600">
                      <span className="mt-1 text-sky-500">
                        <LocationIcon className="h-5 w-5" />
                      </span>
                      <span>{jobLocation}</span>
                    </div>
                    )
                  : (
                    <p className="text-sm text-slate-400">No job site information provided.</p>
                    )}
              </div>

              <div className="panel space-y-3">
                <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Source</h2>
                {sourceName
                  ? (
                    <div className="flex items-center gap-3 text-sm text-slate-600">
                      <span className="text-sky-500">
                        <ShareIcon className="h-5 w-5" />
                      </span>
                      <span>{sourceName}</span>
                    </div>
                    )
                  : (
                    <p className="text-sm text-slate-400">No source recorded.</p>
                    )}
              </div>

            </aside>

            <section className="panel flex h-full flex-col gap-5">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <h2 className="text-base font-semibold text-slate-800">Order Activity</h2>
                  <p className="text-sm text-slate-400">Notes, history, and key order details.</p>
                </div>
              </div>

              <ul
                role="tablist"
                aria-label="Order detail tabs"
                className="flex flex-wrap gap-2 rounded-xl bg-slate-50 p-1"
                onKeyDown={onKeyDown}
              >
                {tabs.map(({ key, label, Icon }) => {
                  const active = tab === key
                  return (
                    <li key={key}>
                      <button
                        id={`tab-${key}`}
                        role="tab"
                        aria-selected={active}
                        aria-controls={`panel-${key}`}
                        className={[
                          'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-150',
                          active
                            ? 'bg-white text-sky-600 shadow-sm'
                            : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'
                        ].join(' ')}
                        onClick={() => { setTab(key) }}
                      >
                        <Icon className="h-4 w-4" aria-hidden="true" />
                        {label}
                      </button>
                    </li>
                  )
                })}
              </ul>

              <div className="flex-1 overflow-hidden rounded-xl border border-slate-200/70 bg-white">
                {tab === 'home' && (
                  <div id="panel-home" role="tabpanel" aria-labelledby="tab-home" className="h-full">
                    <OrderNotesForOrder orderId={order.id} canCreate />
                  </div>
                )}

                {tab === 'profile' && (
                  <div id="panel-profile" role="tabpanel" aria-labelledby="tab-profile" className="space-y-6 p-6 text-sm text-slate-600">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div className="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Current Status</p>
                        <p className="mt-1 text-sm font-medium text-slate-700">{order.status ?? 'No status'}</p>
                      </div>
                      <div className="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Order Type</p>
                        <p className="mt-1 text-sm font-medium text-slate-700">{order.order_type ?? '—'}</p>
                      </div>
                      <div className="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Method of Payment</p>
                        <p className="mt-1 text-sm font-medium text-slate-700">{order.method_of_payment ?? '—'}</p>
                      </div>
                    </div>

                    <div>
                      <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Status History</h3>
                      {statusCount > 0
                        ? (
                          <ul className="mt-4 space-y-3">
                            {safeOrderStatuses.map((status) => (
                              <li key={status.id} className="rounded-xl border border-slate-200/70 bg-white px-4 py-3 shadow-sm">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                  <span className="text-sm font-semibold text-slate-700">{status.status}</span>
                                  <span className="text-xs text-slate-400">{status.created_at_formatted}</span>
                                </div>
                              </li>
                            ))}
                          </ul>
                          )
                        : (
                          <p className="mt-3 text-sm text-slate-400">No status history recorded for this order.</p>
                          )}
                    </div>
                  </div>
                )}

                {tab === 'contact' && (
                  <div id="panel-contact" role="tabpanel" aria-labelledby="tab-contact" className="space-y-5 p-6 text-sm text-slate-600">
                    <p className="text-sm text-slate-500">
                      Mantén a tu equipo sincronizado. Aquí tienes los datos de contacto clave para esta orden.
                    </p>
                    <div className="grid gap-4 sm:grid-cols-2">
                      {contactDetails.map(({ label, value, fallback, Icon }) => (
                        <div key={label} className="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                          <div className="flex items-center gap-3">
                            <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-sky-500 shadow-sm">
                              <Icon className="h-4 w-4" />
                            </span>
                            <div>
                              <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                              <p className="text-sm font-medium text-slate-700">{value ?? <span className="text-slate-400">{fallback}</span>}</p>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {tab === 'sales' && (
                  <div id="panel-sales" role="tabpanel" aria-labelledby="tab-sales" className="space-y-5 p-6 text-sm text-slate-600">
                    {order.sale_form
                      ? (
                        <>
                          <div className="flex items-center justify-between">
                            <p className="text-sm text-slate-500">View and download the sales form generated for this order.</p>
                            <a
                              href={route('frontdesk.order.sale_form', { order: order.id, download: 1 })}
                              className="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-sky-700"
                              target="_blank"
                              rel="noopener noreferrer"
                            >
                              Download PDF
                            </a>
                          </div>
                          <div className="overflow-hidden rounded-lg border border-slate-200">
                            <object
                              data={route('frontdesk.order.sale_form', { order: order.id })}
                              type="application/pdf"
                              className="h-[600px] w-full"
                            >
                              <p className="p-4 text-sm text-slate-500">
                                No pudimos mostrar el PDF incrustado. Puedes <a className="text-sky-600 underline" href={route('frontdesk.order.sale_form', { order: order.id, download: 1 })} target="_blank" rel="noopener noreferrer">descargarlo aquí</a>.
                              </p>
                            </object>
                          </div>
                        </>
                        )
                      : (
                        <p className="text-sm text-slate-400">No sale form attachments available for this order.</p>
                    )}
                  </div>
                )}

                {tab === 'attachments' && (
                  <div id="panel-attachments" role="tabpanel" aria-labelledby="tab-attachments" className="space-y-5 p-6 text-sm text-slate-600">
                    <form onSubmit={handleAttachmentUpload} className="space-y-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                      <div>
                        <label htmlFor="order-attachments" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Agregar archivos</label>
                        <input
                          id="order-attachments"
                          ref={fileInputRef}
                          type="file"
                          multiple
                          onChange={handleFileSelection}
                          className="mt-2 block w-full cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-4 file:cursor-pointer file:rounded-md file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-sky-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed"
                          disabled={uploading}
                        />
                      </div>

                      {newFiles.length > 0 && (
                        <ul className="space-y-1 text-xs text-slate-500">
                          {newFiles.map(file => (
                            <li key={`${file.name}-${file.lastModified}`}>{file.name}</li>
                          ))}
                        </ul>
                      )}

                      {uploadError && (
                        <div className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600">
                          {uploadError}
                        </div>
                      )}

                      <div className="flex justify-end">
                        <button
                          type="submit"
                          className="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                          disabled={uploading}
                        >
                          {uploading ? 'Subiendo…' : 'Subir archivos'}
                        </button>
                      </div>
                    </form>

                    {deleteError && (
                      <div className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600">
                        {deleteError}
                      </div>
                    )}

                    {attachments.length > 0
                      ? (
                        <ul className="space-y-3">
                          {attachments.map((attachment) => {
                            const isDeleting = deletingIds.includes(attachment.id)
                            const createdAtValue = attachment.created_at ?? (attachment as any)?.created_at
                            const createdAtLabel = createdAtValue
                              ? attachmentDateFormatter.current?.format(new Date(createdAtValue)) ?? new Date(createdAtValue).toLocaleString()
                              : null
                            const uploaderName = attachment.uploaded_by ?? (attachment as any)?.user?.name ?? null
                            const uploadedByLabel = uploaderName ?? 'Usuario desconocido'
                            const canDeleteAttachment = authUserId !== null && attachment.user_id === authUserId
                            return (
                              <li key={attachment.id} className="flex items-center justify-between gap-3 rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 shadow-sm">
                                <div>
                                  <p className="text-sm font-semibold text-slate-700">{attachment.filename}</p>
                                  {(createdAtLabel || uploaderName) && (
                                    <div className="mt-1 text-xs text-slate-400">
                                      {createdAtLabel && (
                                        <div>
                                          <span>{createdAtLabel}</span>
                                          {uploadedByLabel && (
                                            <>
                                              <span> By</span>
                                              <br />
                                              <span className="text-slate-500">{uploadedByLabel}</span>
                                            </>
                                          )}
                                        </div>
                                      )}
                                      {!createdAtLabel && uploadedByLabel && (
                                        <div>
                                          <span>By</span>
                                          <br />
                                          <span className="text-slate-500">{uploadedByLabel}</span>
                                        </div>
                                      )}
                                    </div>
                                  )}
                                </div>
                                <div className="flex items-center gap-2">
                                  <a
                                    href={route('download.file', { id: attachment.id })}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-sky-600 hover:bg-sky-200"
                                  >
                                    <ExportIcon className="h-4 w-4" />
                                  </a>
                                  <button
                                    type="button"
                                    onClick={() => { handleAttachmentDelete(attachment.id) }}
                                    className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    disabled={isDeleting || !canDeleteAttachment}
                                    aria-label="Eliminar adjunto"
                                    title={!canDeleteAttachment ? 'Solo puedes eliminar archivos que subiste' : 'Eliminar adjunto'}
                                  >
                                    <DeleteIcon className="h-4 w-4" />
                                  </button>
                                </div>
                              </li>
                            )
                          })}
                        </ul>
                        )
                      : (
                        <p className="text-sm text-slate-400">No hay archivos adjuntos para esta orden.</p>
                        )}
                  </div>
                )}
              </div>
            </section>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
