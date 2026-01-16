export interface Tasks {
  id: number
  title: string
  // description: string
  date: string
  date_edited: string
  status_created_at_iso?: string | null
  follow_up_started_at_iso?: string | null
  is_supply?: boolean
  tags: Array<{ name: string, color: string }>
  schedule_appointment: string | null
  schedule_appointment_iso?: string | null
  phone: string | null
  contact_email?: string | null
  image?: string | null
  created_by?: string | null
  name_check?: boolean
  address_check?: boolean
  amount_check?: boolean
  email_check?: boolean
  project_amount?: number | string | null
  down_payment?: number | string | null
  job_address?: string | null
  city?: string | null
  job_state?: string | null
  job_zip?: string | null
  method_of_payment?: string | null
  type_of_financing?: string | null
  order_type?: string | null
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
  tasks: Tasks[]
}
