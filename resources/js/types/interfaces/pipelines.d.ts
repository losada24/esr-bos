export interface Tasks {
  id: number
  title: string
  // description: string
  date: string
  date_edited: string
  status_created_at_iso?: string | null
  follow_up_started_at_iso?: string | null
  is_supply?: boolean
  is_parent_order?: boolean
  child_orders_count?: number
  tags: Array<{ name: string, color: string }>
  schedule_appointment: string | null
  schedule_appointment_iso?: string | null
  phone: string | null
  contact_email?: string | null
  client_email_selection?: string | null
  client_email_override?: string | null
  client_email_options?: Array<{
    value: string
    label: string
    is_primary?: boolean
  }>
  do_not_send_email?: boolean
  image?: string | null
  created_by?: string | null
  name_check?: boolean
  address_check?: boolean
  amount_check?: boolean
  email_check?: boolean
  city_permits?: boolean
  association_permits?: boolean
  project_amount?: number | string | null
  down_payment?: number | string | null
  job_address?: string | null
  city?: string | null
  job_state?: string | null
  job_zip?: string | null
  method_of_payment?: string | null
  type_of_financing?: string | null
  order_type?: string | null
  product_line?: string | null
  bid_due_date?: string | null
  vip_clients?: boolean
  order_company_contacts?: Array<{
    id: number
    company_name?: string | null
    company_email?: string | null
    client_id?: number | null
    client_name?: string | null
    client_email?: string | null
    client_secondary_email?: string | null
    is_selected?: boolean
    client_email_options?: Array<{
      value: string
      label: string
      is_primary?: boolean
    }>
  }>
  owner_ids?: number[]
  owners?: Array<{ id: number, name: string }>
  // status?: string
  // name: user
  // precio: number
}

export interface Pipelines {
  id: number
  title: string
  client_id: number
  total_tasks?: number
  total_project_amount?: number | string | null
  tasks: Tasks[]
}
