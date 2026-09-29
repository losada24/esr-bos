import { PAYMENT_METHODS } from '@/Utils/constants'

export const FINANCING_JOBSITE_ADDRESS_MINIMUM = 10000

const toAmount = (value: unknown): number => {
  const parsed = Number(String(value ?? '').replace(/,/g, ''))

  return Number.isFinite(parsed) ? parsed : 0
}

export const requiresFinancingJobsiteAddress = ({
  status,
  methodOfPayment,
  projectAmount
}: {
  status?: unknown
  methodOfPayment?: unknown
  projectAmount?: unknown
}): boolean => {
  if (String(status ?? '').trim().toUpperCase() !== 'ACCOUNT RECEIPT') {
    return false
  }

  const method = String(methodOfPayment ?? '').trim().toUpperCase()
  const project = toAmount(projectAmount)

  return method === PAYMENT_METHODS.FINANCED && project >= FINANCING_JOBSITE_ADDRESS_MINIMUM
}
