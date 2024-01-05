export interface PaymentInfo {
  id?: number
  order_id: number
  method: string
  terms_and_conditions_agreed: boolean
  first_name: string
  last_name: string
  country: string
  street_address: string
  city: string
  state: string
  zip_code: string
  phone_number: string
  email: string
  notes: string
  amount: number
  created_at?: string
}
