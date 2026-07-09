import { type User } from '@/types/interfaces/user'

export interface ServiceControlOrderSummary {
  id?: number | null
  name: string
  order_number?: string | number | null
  parent_order_id?: number | null
  parent_order?: {
    id?: number | null
    name?: string | null
    order_number?: string | number | null
  } | null
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
  seller?: {
    id?: number | null
    name?: string | null
  } | null
  owners?: Array<{
    id: number
    name: string
  }>
  service_controls?: Array<{
    id: number
    service_name?: string | null
    service_id?: string | null
    external_order_id?: string | number | null
    service_source?: 'ESR' | 'ESW' | string | null
    creation_source?: 'EXTERNAL' | 'MANUAL' | string | null
    request_origin?: 'OWNER' | 'SERVICE' | string | null
    service_type: string[]
    service_status: string
    priority: string
    is_bm?: boolean
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
  client_id?: number | string | null
  service_name?: string | null
  service_id?: string | null
  external_order_id?: string | number | null
  is_bm?: boolean
  service_source?: 'ESR' | 'ESW' | string | null
  creation_source?: 'EXTERNAL' | 'MANUAL' | string | null
  request_origin?: 'OWNER' | 'SERVICE' | string | null
  service_type: string[]
  description?: string | null
  requires_part: boolean
  requested_parts: boolean
  parts_available: boolean
  service_status: string
  priority: string
  cost?: string | number | null
  area?: string | null
  requester_type?: string | null
  requester_id?: number | string | null
  requester_role?: string | null
  assignee_type?: string | null
  assignee_id?: number | string | null
  assignee_role?: string | null
  target_date?: string | null
  service_created_date?: string | null
  service_id_requested_date?: string | null
  eta_date?: string | null
  parts_received_date?: string | null
  urgency_status?: string | null
  production_output_overdue_days?: number | null
  production_output_overdue_resolved_at?: string | null
  part_delivered_date?: string | null
  scheduled_date?: string | null
  executed_date?: string | null
  opened_at?: string | null
  closed_at?: string | null
  open_days?: number
  closure_result?: string | null
  observations?: string | null
  bm_quantity?: string | number | null
  bm_requested_date?: string | null
  bm_picked_up_by?: string | null
  bm_pickup_date?: string | null
  bm_invoice_number?: string | null
  bm_invoice_status?: string | null
  created_at?: string | null
  updated_at?: string | null
  is_missing_service_id_overdue?: boolean
  is_missing_eta_overdue?: boolean
  creator?: User | null
  updater?: User | null
  order?: ServiceControlOrderSummary | null
  client?: ServiceControlOrderSummary['client'] | null
  histories?: ServiceControlHistory[]
}
