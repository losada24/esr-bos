import { type OptionType, type Order } from '@/types'
import { type MultiValue } from 'react-select'

export type OrdenEvent = Omit<Order, 'name' | 'installation_team_id' | 'entry_date' | 'status' | 'eta_date' | 'contract_signing_date' | 'payment_factory_date' | 'delivery_date' | 'installation_end_date' | 'order_number' | 'installation_date' | 'job_address' | 'job_city' | 'job_state' | 'job_zip' | 'city_permits' | 'association_permits' | 'equipment_rental'> & {
  entry_date: null | Date
  installation_date: null | Date
  eta_date: null | Date
  contract_signing_date: null | Date
  payment_factory_date: null | Date
  delivery_date: null | Date
  installation_end_date: null | Date
  status: null | string
  hide_on_weekends: boolean
  // installation_team_id: MultiValue<OptionType>
}
