import * as Yup from 'yup'
import { type OptionType, type InstallationTeam } from '@/types'
import { type MultiValue } from 'react-select'

export const installationTeamSchema = Yup.object({
  id: Yup.number(),
  // user_id: Yup.object().required('User is required'),
  number_of_member: Yup.number().required('Number of member is required'),
  worker_compensation_expiration_date: Yup.date().required('Worker compensation expiration date is required'),
  liability_expiration_date: Yup.date().required('Liability expiration date is required')
})

export type InstallationTeamFormValues = Omit<InstallationTeam, 'user_id' | 'type_housing' | 'travel_costs'> & {
  worker_compensation_attach: string
  worker_compensation_exception_attach: string
  liability_expiration_attach: string
  installer_agrement_attach: string
  annual_w9_attach: string
  user_id: OptionType
  type_housing: MultiValue<OptionType>
  travel_costs: MultiValue<OptionType>
}
