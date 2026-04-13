import { type Order } from '@/types/interfaces/order'
import { type User } from '@/types/interfaces/user'

export interface ServiceControlOrderSummary {
  id: number
  name: string
  order_number?: string | number | null
  order_type?: string | null
  job_address?: string | null
  city?: string | null
  job_state?: string | null
  job_zip?: string | null
  address_label?: string | null
  today_date?: string | null
  client?: {
    id?: number | null
    name?: string | null
    phone?: string | null
    email?: string | null
    other_phone?: string | null
    secondary_email?: string | null
    contact_type?: string | null
    vip_clients?: boolean
    vip_notes?: string | null
  } | null
  company?: {
    id?: number | null
    name?: string | null
    email?: string | null
    phone?: string | null
  } | null
  supervisor?: {
    id?: number | null
    name?: string | null
  } | null
  service_controls?: Array<{
    id: number
    service_name?: string | null
    service_id?: string | null
    service_type: string
    service_status: string
    priority: string
    opened_at?: string | null
    open_days?: number
  }>
}

export interface ServiceControlHistory {
  id: number
  event_type: string
  summary?: string | null
  comment?: string | null
  old_values?: Record<string, unknown> | null
  new_values?: Record<string, unknown> | null
  created_at?: string | null
  created_at_label?: string | null
  user?: User | null
}

export interface ServiceControl {
  id: number
  service_name?: string | null
  service_id?: string | null
  service_type: string
  description?: string | null
  requires_part: boolean
  requested_parts: boolean
  parts_available: boolean
  service_status: string
  priority: string
  target_date?: string | null
  scheduled_date?: string | null
  executed_date?: string | null
  opened_at?: string | null
  closed_at?: string | null
  open_days?: number
  closure_result?: string | null
  observations?: string | null
  created_at?: string | null
  updated_at?: string | null
  creator?: User | null
  updater?: User | null
  order?: ServiceControlOrderSummary | Order | null
  histories?: ServiceControlHistory[]
}
