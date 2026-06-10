import Modal from '@/Components/Modal'

interface CalendarEventDetails {
  order_name?: string
  appointment_date?: string
  appointment_time?: string
  owner_names?: string
  client_name?: string
  client_phone?: string
  client_email?: string
  order_type?: string
  is_supply?: boolean
  vip_client?: boolean
  company_name?: string
  job_address?: string
  job_city?: string
  job_state?: string
  job_zip?: string
  city?: string
}

interface CalendarEventModalProps {
  show: boolean
  onClose: () => void
  event: CalendarEventDetails | null
}

const CalendarEventModal = ({ show, onClose, event }: CalendarEventModalProps) => {
  const normalizedOrderType = (event?.order_type ?? '').toUpperCase()
  const isCommercial = normalizedOrderType === 'COMMERCIAL'
  const isSupply = normalizedOrderType === 'SUPPLY' || !!event?.is_supply
  const isVip = !!event?.vip_client
  const addressCity = event?.job_city ?? event?.city ?? ''
  const hasAddress = !!event?.job_address
  const addressParts = hasAddress
    ? [event?.job_address, addressCity, event?.job_state, event?.job_zip].filter(Boolean)
    : []
  const addressText = addressParts.join(', ')
  const mapsUrl = addressText
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(addressText)}`
    : ''
  const addressValue = addressText
    ? (
        <a
          href={mapsUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="text-blue-600 underline"
        >
          {addressText}
        </a>
      )
    : null

  const rows = [
    { label: 'Order Name', value: event?.order_name },
    { label: 'Appointment Date', value: event?.appointment_date },
    { label: 'Appointment Time', value: event?.appointment_time },
    { label: 'Owner', value: event?.owner_names },
    { label: 'Client Name', value: event?.client_name },
    { label: 'Address', value: addressValue },
    { label: 'Client Phone', value: event?.client_phone },
    { label: 'Client Email', value: event?.client_email },
    ...(isCommercial ? [{ label: 'Company Name', value: event?.company_name }] : []),
    { label: 'Order Type', value: event?.order_type }
  ]

  return (
    <Modal show={show} maxWidth="2xl" onClose={onClose}>
      <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
        <div className="flex items-center gap-2">
          <h3 className="text-lg font-semibold text-gray-900">Appointment Details</h3>
          {isSupply && (
            <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
              SUPPLY
            </span>
          )}
          {isVip && (
            <span className="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
              VIP
            </span>
          )}
        </div>
        <button
          type="button"
          onClick={onClose}
          className="rounded-md px-2 py-1 text-sm text-gray-500 hover:bg-gray-100"
        >
          Close
        </button>
      </div>
      <div className="space-y-4 px-6 py-5">
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          {rows.map((row) => (
            <div key={row.label} className="rounded-md border border-gray-200 px-3 py-2">
              <div className="text-[11px] uppercase tracking-wide text-gray-500">{row.label}</div>
              <div className="text-sm font-medium text-gray-900">{row.value || '—'}</div>
            </div>
          ))}
        </div>
      </div>
    </Modal>
  )
}

export default CalendarEventModal
