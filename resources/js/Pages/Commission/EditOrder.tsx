import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import { type PageProps } from '@/types'
import { type ReactNode, useState } from 'react'

interface BeneficiaryOption {
  id: number
  name: string
  email?: string | null
  phone?: string | null
  company_name?: string | null
}

interface CommissionPaymentRecord {
  id: number
  sequence: number
  status: string
  split_type: string
  split_value: number
  payment_base_amount: number
  other_cost_amount: number
  other_cost_notes?: string | null
  total_to_pay: number
  commission_period_id?: number | null
  paid_at?: string | null
  notes?: string | null
}

interface CommissionRecord {
  id: number
  beneficiary_source_type: string
  beneficiary_source_id: number
  beneficiary_relation: string
  beneficiary_name_snapshot: string
  beneficiary_email_snapshot?: string | null
  status: string
  calculation_type: string
  percentage_value?: number | null
  fixed_amount?: number | null
  project_amount_snapshot: number
  fee_amount_snapshot: number
  base_amount_snapshot: number
  commission_amount: number
  other_cost_amount: number
  other_cost_notes?: string | null
  total_amount: number
  paid_amount: number
  pending_amount: number
  next_payment_id?: number | null
  payments: CommissionPaymentRecord[]
}

type EditOrderCommissionProps = PageProps & {
  order: {
    id: number
    name: string
    status: string
    project_amount: number
    cost_city_fee: number
    owners: BeneficiaryOption[]
  }
  commissions: CommissionRecord[]
  activeUsers: BeneficiaryOption[]
  referrals: BeneficiaryOption[]
  externalBeneficiaries: BeneficiaryOption[]
  enums: {
    beneficiarySourceTypes: string[]
    beneficiaryRelations: string[]
    calculationTypes: string[]
    paymentStatuses: string[]
    splitTypes: string[]
    commissionStatuses: string[]
  }
}

type CreatePaymentDraft = {
  split_type: string
  split_value: number
  status: string
  other_cost_amount: number
  other_cost_notes: string
  notes: string
  paid_at: string
}

type PaymentFormData = {
  id: number
  split_type: string
  split_value: number
  status: string
  other_cost_amount: number
  other_cost_notes: string
  notes: string
  paid_at: string
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(value ?? 0))
}

function blankPaymentForm(paymentStatuses: string[], splitTypes: string[]): PaymentFormData {
  return {
    id: 0,
    split_type: splitTypes[0] ?? 'PERCENTAGE',
    split_value: 0,
    status: paymentStatuses[0] ?? 'OPEN',
    other_cost_amount: 0,
    other_cost_notes: '',
    notes: '',
    paid_at: ''
  }
}

function blankCreatePayment(paymentStatuses: string[], splitTypes: string[]): CreatePaymentDraft {
  return {
    split_type: splitTypes[0] ?? 'PERCENTAGE',
    split_value: 50,
    status: paymentStatuses[0] ?? 'OPEN',
    other_cost_amount: 0,
    other_cost_notes: '',
    notes: '',
    paid_at: ''
  }
}

function updateDraftPayment(
  payments: CreatePaymentDraft[],
  index: number,
  patch: Partial<CreatePaymentDraft>
): CreatePaymentDraft[] {
  return payments.map((item, itemIndex) => (
    itemIndex === index ? { ...item, ...patch } : item
  ))
}

function beneficiaryOptionsBySource(
  sourceType: string,
  relation: string,
  orderOwners: BeneficiaryOption[],
  activeUsers: BeneficiaryOption[],
  referrals: BeneficiaryOption[]
): BeneficiaryOption[] {
  if (sourceType === 'USER') {
    return relation === 'OWNER' ? orderOwners : activeUsers
  }

  if (sourceType === 'REFERRAL') {
    return referrals
  }

  return []
}

function relationOptionsBySource(sourceType: string, relations: string[]): string[] {
  if (sourceType === 'USER') {
    return relations.filter((item) => item === 'OWNER' || item === 'EMPLOYEE')
  }

  if (sourceType === 'REFERRAL') {
    return relations.filter((item) => item === 'REFERRAL')
  }

  if (sourceType === 'EXTERNAL') {
    return relations.filter((item) => item === 'EXTERNAL')
  }

  return relations
}

function normalizeRelationForSource(sourceType: string, currentRelation: string, relations: string[]): string {
  const availableRelations = relationOptionsBySource(sourceType, relations)

  if (availableRelations.includes(currentRelation)) {
    return currentRelation
  }

  return availableRelations[0] ?? currentRelation
}

function optionLabelForSource(sourceType: string): string {
  if (sourceType === 'REFERRAL') {
    return 'Referral'
  }

  if (sourceType === 'EXTERNAL') {
    return 'External'
  }

  return 'User'
}

function nextPaymentLabel(commission: CommissionRecord): string {
  const nextPayment = commission.payments.find((payment) => payment.id === commission.next_payment_id)

  if (!nextPayment) {
    return 'No next payment'
  }

  return `#${nextPayment.sequence} · ${nextPayment.status} · ${formatCurrency(nextPayment.total_to_pay)}`
}

function percentageBase(projectAmount: number, feeAmount: number): number {
  return Math.max(Number(projectAmount ?? 0) - Number(feeAmount ?? 0), 0)
}

function FormSection({
  title,
  description,
  children
}: {
  title: string
  description?: string
  children: ReactNode
}) {
  return (
    <div className="rounded border bg-slate-50 p-4 md:col-span-4">
      <div className="mb-4">
        <h3 className="text-base font-semibold">{title}</h3>
        {description && <p className="text-sm text-slate-500">{description}</p>}
      </div>
      {children}
    </div>
  )
}

function PaymentSplitPreview({
  payments
}: {
  payments: CreatePaymentDraft[]
}) {
  if (payments.length === 0) {
    return <span className="text-slate-500">No payments yet</span>
  }

  return (
    <div className="flex flex-wrap gap-2 text-xs">
      {payments.map((payment, index) => (
        <span key={`preview-${index}`} className="rounded-full border px-2 py-1">
          #{index + 1} {payment.split_type === 'PERCENTAGE' ? `${payment.split_value}%` : formatCurrency(payment.split_value)}
        </span>
      ))}
    </div>
  )
}

function CommissionCard ({
  commission,
  order,
  activeUsers,
  referrals,
  externalBeneficiaries,
  enums
}: {
  commission: CommissionRecord
  order: EditOrderCommissionProps['order']
  activeUsers: BeneficiaryOption[]
  referrals: BeneficiaryOption[]
  externalBeneficiaries: BeneficiaryOption[]
  enums: EditOrderCommissionProps['enums']
}) {
  const [activePanel, setActivePanel] = useState<'details' | 'payments' | null>(null)
  const [editingPaymentId, setEditingPaymentId] = useState<number>(0)

  const commissionForm = useForm({
    order_id: order.id,
    beneficiary_source_type: commission.beneficiary_source_type,
    beneficiary_source_id: commission.beneficiary_source_type === 'EXTERNAL' ? 0 : commission.beneficiary_source_id,
    beneficiary_relation: commission.beneficiary_relation,
    calculation_type: commission.calculation_type,
    fee_amount_snapshot: commission.fee_amount_snapshot,
    percentage_value: commission.percentage_value ?? '',
    fixed_amount: commission.fixed_amount ?? '',
    other_cost_amount: commission.other_cost_amount,
    other_cost_notes: commission.other_cost_notes ?? '',
    status: commission.status,
    external_beneficiary_id: commission.beneficiary_source_type === 'EXTERNAL' ? String(commission.beneficiary_source_id) : '',
    external_name: commission.beneficiary_source_type === 'EXTERNAL' ? commission.beneficiary_name_snapshot : '',
    external_email: commission.beneficiary_source_type === 'EXTERNAL' ? (commission.beneficiary_email_snapshot ?? '') : '',
    external_phone: '',
    external_company_name: ''
  })

  const paymentForm = useForm(blankPaymentForm(enums.paymentStatuses, enums.splitTypes))

  const currentBeneficiaryOptions = beneficiaryOptionsBySource(
    commissionForm.data.beneficiary_source_type,
    commissionForm.data.beneficiary_relation,
    order.owners,
    activeUsers,
    referrals
  )
  const currentRelationOptions = relationOptionsBySource(commissionForm.data.beneficiary_source_type, enums.beneficiaryRelations)
  const currentBasePreview = percentageBase(order.project_amount, Number(commissionForm.data.fee_amount_snapshot))

  return (
    <div className="rounded border bg-white">
      <div className="border-b p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 className="text-lg font-semibold">{commission.beneficiary_name_snapshot}</h2>
            <p className="text-sm text-slate-500">
              {commission.beneficiary_relation} · {commission.beneficiary_source_type} · {commission.status}
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              className="btn btn-sm btn-outline-primary"
              onClick={() => { setActivePanel(activePanel === 'details' ? null : 'details') }}
            >
              {activePanel === 'details' ? 'Hide Details' : 'Edit Commission'}
            </button>
            <button
              type="button"
              className="btn btn-sm btn-outline-primary"
              onClick={() => {
                setActivePanel(activePanel === 'payments' ? null : 'payments')
                setEditingPaymentId(0)
                paymentForm.setData(blankPaymentForm(enums.paymentStatuses, enums.splitTypes))
              }}
            >
              {activePanel === 'payments' ? 'Hide Payments' : 'Manage Payments'}
            </button>
          </div>
        </div>

        <div className="mt-4 grid gap-3 md:grid-cols-6">
          <div className="rounded border p-3">
            <span className="block text-xs uppercase text-slate-500">Fee</span>
            <span className="text-base font-semibold">{formatCurrency(commission.fee_amount_snapshot)}</span>
          </div>
          <div className="rounded border p-3">
            <span className="block text-xs uppercase text-slate-500">Base</span>
            <span className="text-base font-semibold">{formatCurrency(commission.base_amount_snapshot)}</span>
          </div>
          <div className="rounded border p-3">
            <span className="block text-xs uppercase text-slate-500">Commission</span>
            <span className="text-base font-semibold">{formatCurrency(commission.commission_amount)}</span>
          </div>
          <div className="rounded border p-3">
            <span className="block text-xs uppercase text-slate-500">Paid</span>
            <span className="text-base font-semibold">{formatCurrency(commission.paid_amount)}</span>
          </div>
          <div className="rounded border p-3">
            <span className="block text-xs uppercase text-slate-500">Pending</span>
            <span className="text-base font-semibold">{formatCurrency(commission.pending_amount)}</span>
          </div>
          <div className="rounded border p-3">
            <span className="block text-xs uppercase text-slate-500">Next Payment</span>
            <span className="text-sm font-semibold">{nextPaymentLabel(commission)}</span>
          </div>
        </div>
      </div>

      {activePanel === 'details' && (
        <form
          className="grid gap-4 p-4 md:grid-cols-4"
          onSubmit={(event) => {
            event.preventDefault()
            commissionForm.patch(route('report.commissions.update', commission.id))
          }}
        >
          <FormSection
            title="Beneficiary"
            description="Choose who gets this commission. If the relation is OWNER, only the owners assigned to this order should be selected."
          >
            <div className="grid gap-4 md:grid-cols-4">
              <div>
                <label className="mb-1 block font-semibold">Source Type</label>
                <select
                  className="form-select"
                  value={commissionForm.data.beneficiary_source_type}
                  onChange={(event) => {
                    const value = event.target.value
                    const nextRelation = normalizeRelationForSource(value, commissionForm.data.beneficiary_relation, enums.beneficiaryRelations)
                    commissionForm.setData((data) => ({
                      ...data,
                      beneficiary_source_type: value,
                      beneficiary_relation: nextRelation,
                      beneficiary_source_id: 0,
                      external_beneficiary_id: '',
                      external_name: '',
                      external_email: '',
                      external_phone: '',
                      external_company_name: ''
                    }))
                  }}
                >
                  {enums.beneficiarySourceTypes.map((item) => (
                    <option key={item} value={item}>{item}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="mb-1 block font-semibold">Relation</label>
                <select
                  className="form-select"
                  value={commissionForm.data.beneficiary_relation}
                  onChange={(event) => {
                    commissionForm.setData((data) => ({
                      ...data,
                      beneficiary_relation: event.target.value,
                      beneficiary_source_id: 0
                    }))
                  }}
                >
                  {currentRelationOptions.map((item) => (
                    <option key={item} value={item}>{item}</option>
                  ))}
                </select>
              </div>

              {commissionForm.data.beneficiary_source_type !== 'EXTERNAL' && (
                <div>
                  <label className="mb-1 block font-semibold">
                    {optionLabelForSource(commissionForm.data.beneficiary_source_type)}
                  </label>
                  <select
                    className="form-select"
                    value={commissionForm.data.beneficiary_source_id}
                    onChange={(event) => { commissionForm.setData('beneficiary_source_id', Number(event.target.value)) }}
                  >
                    <option value={0}>Select</option>
                    {currentBeneficiaryOptions.map((item) => (
                      <option key={item.id} value={item.id}>{item.name}</option>
                    ))}
                  </select>
                </div>
              )}

              {commissionForm.data.beneficiary_source_type === 'EXTERNAL' && (
                <>
                  <div>
                    <label className="mb-1 block font-semibold">Existing External</label>
                    <select
                      className="form-select"
                      value={commissionForm.data.external_beneficiary_id}
                      onChange={(event) => { commissionForm.setData('external_beneficiary_id', event.target.value) }}
                    >
                      <option value="">Create new</option>
                      {externalBeneficiaries.map((item) => (
                        <option key={item.id} value={item.id}>{item.name}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="mb-1 block font-semibold">External Name</label>
                    <input className="form-input" value={commissionForm.data.external_name} onChange={(event) => { commissionForm.setData('external_name', event.target.value) }} />
                  </div>
                  <div>
                    <label className="mb-1 block font-semibold">External Email</label>
                    <input className="form-input" value={commissionForm.data.external_email} onChange={(event) => { commissionForm.setData('external_email', event.target.value) }} />
                  </div>
                  <div>
                    <label className="mb-1 block font-semibold">External Phone</label>
                    <input className="form-input" value={commissionForm.data.external_phone} onChange={(event) => { commissionForm.setData('external_phone', event.target.value) }} />
                  </div>
                  <div>
                    <label className="mb-1 block font-semibold">Company Name</label>
                    <input className="form-input" value={commissionForm.data.external_company_name} onChange={(event) => { commissionForm.setData('external_company_name', event.target.value) }} />
                  </div>
                </>
              )}
            </div>
          </FormSection>

          <FormSection
            title="Commission Amount"
            description="Define whether the commission is based on percentage or fixed amount, then apply any commission-level other cost."
          >
            <div className="grid gap-4 md:grid-cols-4">
              <div>
                <label className="mb-1 block font-semibold">Calculation</label>
                <select
                  className="form-select"
                  value={commissionForm.data.calculation_type}
                  onChange={(event) => { commissionForm.setData('calculation_type', event.target.value) }}
                >
                  {enums.calculationTypes.map((item) => (
                    <option key={item} value={item}>{item}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="mb-1 block font-semibold">Commission Fee</label>
                <input
                  className="form-input"
                  type="number"
                  value={commissionForm.data.fee_amount_snapshot}
                  onChange={(event) => { commissionForm.setData('fee_amount_snapshot', Number(event.target.value)) }}
                />
                <p className="mt-1 text-xs text-slate-500">This fee is subtracted from the order amount before calculating the commission.</p>
              </div>

              {commissionForm.data.calculation_type === 'PERCENTAGE' && (
                <div>
                  <label className="mb-1 block font-semibold">Percentage</label>
                  <input
                    className="form-input"
                    type="number"
                    value={commissionForm.data.percentage_value}
                    onChange={(event) => { commissionForm.setData('percentage_value', event.target.value) }}
                  />
                </div>
              )}

              {commissionForm.data.calculation_type === 'FIXED' && (
                <div>
                  <label className="mb-1 block font-semibold">Fixed Amount</label>
                  <input
                    className="form-input"
                    type="number"
                    value={commissionForm.data.fixed_amount}
                    onChange={(event) => { commissionForm.setData('fixed_amount', event.target.value) }}
                  />
                </div>
              )}

              <div>
                <label className="mb-1 block font-semibold">Other Cost</label>
                <input
                  className="form-input"
                  type="number"
                  value={commissionForm.data.other_cost_amount}
                  onChange={(event) => { commissionForm.setData('other_cost_amount', Number(event.target.value)) }}
                />
              </div>

              <div>
                <label className="mb-1 block font-semibold">Status</label>
                <select
                  className="form-select"
                  value={commissionForm.data.status}
                  onChange={(event) => { commissionForm.setData('status', event.target.value) }}
                >
                  {enums.commissionStatuses.map((item) => (
                    <option key={item} value={item}>{item}</option>
                  ))}
                </select>
              </div>

              <div className="md:col-span-4">
                <div className="mb-3 rounded border bg-white p-3 text-sm">
                  <div className="grid gap-3 md:grid-cols-3">
                    <div>
                      <span className="block text-xs uppercase text-slate-500">Order Amount</span>
                      <span className="font-semibold">{formatCurrency(order.project_amount)}</span>
                    </div>
                    <div>
                      <span className="block text-xs uppercase text-slate-500">Commission Fee</span>
                      <span className="font-semibold">{formatCurrency(Number(commissionForm.data.fee_amount_snapshot))}</span>
                    </div>
                    <div>
                      <span className="block text-xs uppercase text-slate-500">Percentage Base</span>
                      <span className="font-semibold">{formatCurrency(currentBasePreview)}</span>
                    </div>
                  </div>
                </div>
                <label className="mb-1 block font-semibold">Other Cost Notes</label>
                <textarea
                  className="form-textarea"
                  rows={2}
                  value={commissionForm.data.other_cost_notes}
                  onChange={(event) => { commissionForm.setData('other_cost_notes', event.target.value) }}
                />
              </div>
            </div>
          </FormSection>

          <div className="md:col-span-4">
            <button type="submit" className="btn btn-primary">Save Commission</button>
          </div>
        </form>
      )}

      {activePanel === 'payments' && (
        <div className="p-4">
          <div className="mb-4 grid gap-3 md:grid-cols-4">
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Payments</span>
              <span className="text-base font-semibold">{commission.payments.length}</span>
            </div>
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Open / Review</span>
              <span className="text-base font-semibold">
                {commission.payments.filter((payment) => payment.status === 'OPEN' || payment.status === 'REVIEW').length}
              </span>
            </div>
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Paid</span>
              <span className="text-base font-semibold">
                {commission.payments.filter((payment) => payment.status === 'PAID').length}
              </span>
            </div>
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Canceled</span>
              <span className="text-base font-semibold">
                {commission.payments.filter((payment) => payment.status === 'CANCELED').length}
              </span>
            </div>
          </div>

          <div className="table-responsive rounded border">
            <table className="table-auto w-full border-collapse">
              <thead className="bg-gray-100">
                <tr className="text-left">
                  <th className="px-4 py-3">#</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Split</th>
                  <th className="px-4 py-3">Base</th>
                  <th className="px-4 py-3">Other Cost</th>
                  <th className="px-4 py-3">Total</th>
                  <th className="px-4 py-3">Paid At</th>
                  <th className="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                {commission.payments.map((payment) => (
                  <tr key={payment.id}>
                    <td className="border-t px-4 py-3">{payment.sequence}</td>
                    <td className="border-t px-4 py-3">{payment.status}</td>
                    <td className="border-t px-4 py-3">
                      {payment.split_type === 'PERCENTAGE' ? `${payment.split_value}%` : formatCurrency(payment.split_value)}
                    </td>
                    <td className="border-t px-4 py-3">{formatCurrency(payment.payment_base_amount)}</td>
                    <td className="border-t px-4 py-3">{formatCurrency(payment.other_cost_amount)}</td>
                    <td className="border-t px-4 py-3">{formatCurrency(payment.total_to_pay)}</td>
                    <td className="border-t px-4 py-3">{payment.paid_at ?? '-'}</td>
                    <td className="border-t px-4 py-3">
                      <button
                        type="button"
                        className="btn btn-sm btn-primary"
                        onClick={() => {
                          setEditingPaymentId(payment.id)
                          paymentForm.setData({
                            id: payment.id,
                            split_type: payment.split_type,
                            split_value: payment.split_value,
                            status: payment.status,
                            other_cost_amount: payment.other_cost_amount,
                            other_cost_notes: payment.other_cost_notes ?? '',
                            notes: payment.notes ?? '',
                            paid_at: payment.paid_at ?? ''
                          })
                        }}
                      >
                        Edit
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <form
            className="mt-4 grid gap-4 rounded border bg-slate-50 p-4 md:grid-cols-4"
            onSubmit={(event) => {
              event.preventDefault()
              if (editingPaymentId > 0) {
                paymentForm.patch(route('report.commissions.payments.update', editingPaymentId))
                return
              }

              paymentForm.post(route('report.commissions.payments.store', commission.id))
            }}
          >
            <div className="md:col-span-4 flex items-center justify-between">
              <div>
                <h3 className="text-base font-semibold">{editingPaymentId > 0 ? 'Edit Payment' : 'Add Payment'}</h3>
                <p className="text-sm text-slate-500">Use OPEN when the payment exists but should not enter the current quincena yet.</p>
              </div>
              {editingPaymentId > 0 && (
                <button
                  type="button"
                  className="btn btn-outline-primary"
                  onClick={() => {
                    setEditingPaymentId(0)
                    paymentForm.setData(blankPaymentForm(enums.paymentStatuses, enums.splitTypes))
                  }}
                >
                  Cancel Edit
                </button>
              )}
            </div>

            <div>
              <label className="mb-1 block font-semibold">Split Type</label>
              <select className="form-select" value={paymentForm.data.split_type} onChange={(event) => { paymentForm.setData('split_type', event.target.value) }}>
                {enums.splitTypes.map((item) => (
                  <option key={item} value={item}>{item}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="mb-1 block font-semibold">Split Value</label>
              <input className="form-input" type="number" value={paymentForm.data.split_value} onChange={(event) => { paymentForm.setData('split_value', Number(event.target.value)) }} />
            </div>

            <div>
              <label className="mb-1 block font-semibold">Status</label>
              <select className="form-select" value={paymentForm.data.status} onChange={(event) => { paymentForm.setData('status', event.target.value) }}>
                {enums.paymentStatuses.map((item) => (
                  <option key={item} value={item}>{item}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="mb-1 block font-semibold">Paid At</label>
              <input className="form-input" type="date" value={paymentForm.data.paid_at} onChange={(event) => { paymentForm.setData('paid_at', event.target.value) }} />
            </div>

            <div>
              <label className="mb-1 block font-semibold">Other Cost</label>
              <input className="form-input" type="number" value={paymentForm.data.other_cost_amount} onChange={(event) => { paymentForm.setData('other_cost_amount', Number(event.target.value)) }} />
            </div>

            <div className="md:col-span-3">
              <label className="mb-1 block font-semibold">Other Cost Notes</label>
              <input className="form-input" value={paymentForm.data.other_cost_notes} onChange={(event) => { paymentForm.setData('other_cost_notes', event.target.value) }} />
            </div>

            <div className="md:col-span-4">
              <label className="mb-1 block font-semibold">Notes</label>
              <textarea className="form-textarea" rows={2} value={paymentForm.data.notes} onChange={(event) => { paymentForm.setData('notes', event.target.value) }} />
            </div>

            <div className="md:col-span-4">
              <button type="submit" className="btn btn-primary">{editingPaymentId > 0 ? 'Save Payment' : 'Add Payment'}</button>
            </div>
          </form>
        </div>
      )}
    </div>
  )
}

export default function EditOrder ({
  auth,
  order,
  commissions,
  activeUsers,
  referrals,
  externalBeneficiaries,
  enums
}: EditOrderCommissionProps) {
  const [showCreateForm, setShowCreateForm] = useState(commissions.length === 0)

  const createForm = useForm({
    order_id: order.id,
    beneficiary_source_type: 'USER',
    beneficiary_source_id: 0,
    beneficiary_relation: 'OWNER',
    calculation_type: 'PERCENTAGE',
    fee_amount_snapshot: order.cost_city_fee,
    percentage_value: '',
    fixed_amount: '',
    other_cost_amount: 0,
    other_cost_notes: '',
    status: 'OPEN',
    external_beneficiary_id: '',
    external_name: '',
    external_email: '',
    external_phone: '',
    external_company_name: '',
    payments: [
      blankCreatePayment(enums.paymentStatuses, enums.splitTypes),
      blankCreatePayment(enums.paymentStatuses, enums.splitTypes)
    ] as CreatePaymentDraft[]
  })

  const createBeneficiaryOptions = beneficiaryOptionsBySource(
    createForm.data.beneficiary_source_type,
    createForm.data.beneficiary_relation,
    order.owners,
    activeUsers,
    referrals
  )
  const createRelationOptions = relationOptionsBySource(createForm.data.beneficiary_source_type, enums.beneficiaryRelations)
  const createBasePreview = percentageBase(order.project_amount, Number(createForm.data.fee_amount_snapshot))

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={`Commissions · ${order.name}`}
      actions={
        <div className="flex gap-2">
          <Link className="btn btn-outline-primary" href={route('report.commissions')}>
            Back To Commissions
          </Link>
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => { setShowCreateForm(!showCreateForm) }}
          >
            {showCreateForm ? 'Hide New Commission' : 'New Commission'}
          </button>
        </div>
      }
    >
      <Head title={`Commissions · ${order.name}`} />

      <div className="space-y-6">
        <div className="rounded border bg-white p-4">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h1 className="text-xl font-semibold">{order.name}</h1>
              <p className="text-sm text-slate-500">Manage commissions for this order from one place.</p>
            </div>
            <div className="rounded border bg-slate-50 px-3 py-2 text-sm">
              <span className="font-semibold">Owners:</span> {order.owners.map((owner) => owner.name).join(', ') || '-'}
            </div>
          </div>

          <div className="mt-4 grid gap-3 md:grid-cols-4">
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Order Status</span>
              <span className="text-base font-semibold">{order.status}</span>
            </div>
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Project Amount</span>
              <span className="text-base font-semibold">{formatCurrency(order.project_amount)}</span>
            </div>
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Fee</span>
              <span className="text-base font-semibold">{formatCurrency(order.cost_city_fee)}</span>
            </div>
            <div className="rounded border p-3">
              <span className="block text-xs uppercase text-slate-500">Base For Percentage</span>
              <span className="text-base font-semibold">{formatCurrency(order.project_amount - order.cost_city_fee)}</span>
            </div>
          </div>
        </div>

        {showCreateForm && (
          <div className="rounded border bg-white p-4">
            <div className="mb-4 flex flex-wrap items-start justify-between gap-4">
              <div>
                <h2 className="text-lg font-semibold">New Commission</h2>
                <p className="text-sm text-slate-500">First choose the beneficiary, then define the amount, then define the initial payment plan.</p>
              </div>
              <div className="rounded border bg-slate-50 px-3 py-2 text-sm">
                <div className="mb-2 font-semibold">Payment preview</div>
                <PaymentSplitPreview payments={createForm.data.payments} />
              </div>
            </div>

            <form
              className="grid gap-4 md:grid-cols-4"
              onSubmit={(event) => {
                event.preventDefault()
                createForm.post(route('report.commissions.store'))
              }}
            >
              <FormSection
                title="1. Beneficiary"
                description="Use OWNER for one of the owners already assigned to the order. Use USER, REFERRAL, or EXTERNAL for everybody else."
              >
                <div className="grid gap-4 md:grid-cols-4">
                  <div>
                    <label className="mb-1 block font-semibold">Source Type</label>
                    <select
                      className="form-select"
                      value={createForm.data.beneficiary_source_type}
                      onChange={(event) => {
                        const value = event.target.value
                        const nextRelation = normalizeRelationForSource(value, createForm.data.beneficiary_relation, enums.beneficiaryRelations)
                        createForm.setData((data) => ({
                          ...data,
                          beneficiary_source_type: value,
                          beneficiary_relation: nextRelation,
                          beneficiary_source_id: 0,
                          external_beneficiary_id: '',
                          external_name: '',
                          external_email: '',
                          external_phone: '',
                          external_company_name: ''
                        }))
                      }}
                    >
                      {enums.beneficiarySourceTypes.map((item) => (
                        <option key={item} value={item}>{item}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="mb-1 block font-semibold">Relation</label>
                    <select
                      className="form-select"
                      value={createForm.data.beneficiary_relation}
                      onChange={(event) => {
                        createForm.setData((data) => ({
                          ...data,
                          beneficiary_relation: event.target.value,
                          beneficiary_source_id: 0
                        }))
                      }}
                    >
                      {createRelationOptions.map((item) => (
                        <option key={item} value={item}>{item}</option>
                      ))}
                    </select>
                  </div>

                  {createForm.data.beneficiary_source_type !== 'EXTERNAL' && (
                    <div>
                      <label className="mb-1 block font-semibold">
                        {optionLabelForSource(createForm.data.beneficiary_source_type)}
                      </label>
                      <select
                        className="form-select"
                        value={createForm.data.beneficiary_source_id}
                        onChange={(event) => { createForm.setData('beneficiary_source_id', Number(event.target.value)) }}
                      >
                        <option value={0}>Select</option>
                        {createBeneficiaryOptions.map((item) => (
                          <option key={item.id} value={item.id}>{item.name}</option>
                        ))}
                      </select>
                    </div>
                  )}

                  {createForm.data.beneficiary_source_type === 'EXTERNAL' && (
                    <>
                      <div>
                        <label className="mb-1 block font-semibold">Existing External</label>
                        <select
                          className="form-select"
                          value={createForm.data.external_beneficiary_id}
                          onChange={(event) => { createForm.setData('external_beneficiary_id', event.target.value) }}
                        >
                          <option value="">Create new</option>
                          {externalBeneficiaries.map((item) => (
                            <option key={item.id} value={item.id}>{item.name}</option>
                          ))}
                        </select>
                      </div>
                      <div>
                        <label className="mb-1 block font-semibold">External Name</label>
                        <input className="form-input" value={createForm.data.external_name} onChange={(event) => { createForm.setData('external_name', event.target.value) }} />
                      </div>
                      <div>
                        <label className="mb-1 block font-semibold">External Email</label>
                        <input className="form-input" value={createForm.data.external_email} onChange={(event) => { createForm.setData('external_email', event.target.value) }} />
                      </div>
                      <div>
                        <label className="mb-1 block font-semibold">External Phone</label>
                        <input className="form-input" value={createForm.data.external_phone} onChange={(event) => { createForm.setData('external_phone', event.target.value) }} />
                      </div>
                      <div>
                        <label className="mb-1 block font-semibold">Company Name</label>
                        <input className="form-input" value={createForm.data.external_company_name} onChange={(event) => { createForm.setData('external_company_name', event.target.value) }} />
                      </div>
                    </>
                  )}
                </div>
              </FormSection>

              <FormSection
                title="2. Commission Amount"
                description="The percentage is calculated from the order base. Use Other Cost if you need to add or subtract value from the commission total."
              >
                <div className="grid gap-4 md:grid-cols-4">
                  <div>
                    <label className="mb-1 block font-semibold">Calculation</label>
                    <select className="form-select" value={createForm.data.calculation_type} onChange={(event) => { createForm.setData('calculation_type', event.target.value) }}>
                      {enums.calculationTypes.map((item) => (
                        <option key={item} value={item}>{item}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="mb-1 block font-semibold">Commission Fee</label>
                    <input
                      className="form-input"
                      type="number"
                      value={createForm.data.fee_amount_snapshot}
                      onChange={(event) => { createForm.setData('fee_amount_snapshot', Number(event.target.value)) }}
                    />
                    <p className="mt-1 text-xs text-slate-500">This amount is subtracted from the order before calculating the commission base.</p>
                  </div>

                  {createForm.data.calculation_type === 'PERCENTAGE' && (
                    <div>
                      <label className="mb-1 block font-semibold">Percentage</label>
                      <input className="form-input" type="number" value={createForm.data.percentage_value} onChange={(event) => { createForm.setData('percentage_value', event.target.value) }} />
                    </div>
                  )}

                  {createForm.data.calculation_type === 'FIXED' && (
                    <div>
                      <label className="mb-1 block font-semibold">Fixed Amount</label>
                      <input className="form-input" type="number" value={createForm.data.fixed_amount} onChange={(event) => { createForm.setData('fixed_amount', event.target.value) }} />
                    </div>
                  )}

                  <div>
                    <label className="mb-1 block font-semibold">Other Cost</label>
                    <input className="form-input" type="number" value={createForm.data.other_cost_amount} onChange={(event) => { createForm.setData('other_cost_amount', Number(event.target.value)) }} />
                  </div>

                  <div>
                    <label className="mb-1 block font-semibold">Status</label>
                    <select className="form-select" value={createForm.data.status} onChange={(event) => { createForm.setData('status', event.target.value) }}>
                      {enums.commissionStatuses.map((item) => (
                        <option key={item} value={item}>{item}</option>
                      ))}
                    </select>
                  </div>

                  <div className="md:col-span-4">
                    <div className="mb-3 rounded border bg-white p-3 text-sm">
                      <div className="grid gap-3 md:grid-cols-3">
                        <div>
                          <span className="block text-xs uppercase text-slate-500">Order Amount</span>
                          <span className="font-semibold">{formatCurrency(order.project_amount)}</span>
                        </div>
                        <div>
                          <span className="block text-xs uppercase text-slate-500">Commission Fee</span>
                          <span className="font-semibold">{formatCurrency(Number(createForm.data.fee_amount_snapshot))}</span>
                        </div>
                        <div>
                          <span className="block text-xs uppercase text-slate-500">Percentage Base</span>
                          <span className="font-semibold">{formatCurrency(createBasePreview)}</span>
                        </div>
                      </div>
                    </div>
                    <label className="mb-1 block font-semibold">Other Cost Notes</label>
                    <textarea className="form-textarea" rows={2} value={createForm.data.other_cost_notes} onChange={(event) => { createForm.setData('other_cost_notes', event.target.value) }} />
                  </div>
                </div>
              </FormSection>

              <FormSection
                title="3. Initial Payments"
                description="Set how the commission starts. OPEN means the payment exists but is not in review for the current quincena."
              >
                <div className="space-y-4">
                  {createForm.data.payments.map((payment, index) => (
                    <div key={`create-payment-${index}`} className="rounded border bg-white p-4">
                      <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div>
                          <h4 className="font-semibold">Payment #{index + 1}</h4>
                          <p className="text-sm text-slate-500">Define split, status, date, and payment-level other cost.</p>
                        </div>
                        <button
                          type="button"
                          className="btn btn-sm btn-outline-primary"
                          disabled={createForm.data.payments.length <= 1}
                          onClick={() => {
                            createForm.setData('payments', createForm.data.payments.filter((_, itemIndex) => itemIndex !== index))
                          }}
                        >
                          Remove
                        </button>
                      </div>

                      <div className="grid gap-4 md:grid-cols-4">
                        <div>
                          <label className="mb-1 block font-semibold">Split Type</label>
                          <select
                            className="form-select"
                            value={payment.split_type}
                            onChange={(event) => {
                              createForm.setData('payments', updateDraftPayment(createForm.data.payments, index, { split_type: event.target.value }))
                            }}
                          >
                            {enums.splitTypes.map((item) => (
                              <option key={item} value={item}>{item}</option>
                            ))}
                          </select>
                        </div>

                        <div>
                          <label className="mb-1 block font-semibold">Split Value</label>
                          <input
                            className="form-input"
                            type="number"
                            value={payment.split_value}
                            onChange={(event) => {
                              createForm.setData('payments', updateDraftPayment(createForm.data.payments, index, { split_value: Number(event.target.value) }))
                            }}
                          />
                        </div>

                        <div>
                          <label className="mb-1 block font-semibold">Status</label>
                          <select
                            className="form-select"
                            value={payment.status}
                            onChange={(event) => {
                              createForm.setData('payments', updateDraftPayment(createForm.data.payments, index, { status: event.target.value }))
                            }}
                          >
                            {enums.paymentStatuses.map((item) => (
                              <option key={item} value={item}>{item}</option>
                            ))}
                          </select>
                        </div>

                        <div>
                          <label className="mb-1 block font-semibold">Paid At</label>
                          <input
                            className="form-input"
                            type="date"
                            value={payment.paid_at}
                            onChange={(event) => {
                              createForm.setData('payments', updateDraftPayment(createForm.data.payments, index, { paid_at: event.target.value }))
                            }}
                          />
                        </div>

                        <div>
                          <label className="mb-1 block font-semibold">Other Cost</label>
                          <input
                            className="form-input"
                            type="number"
                            value={payment.other_cost_amount}
                            onChange={(event) => {
                              createForm.setData('payments', updateDraftPayment(createForm.data.payments, index, { other_cost_amount: Number(event.target.value) }))
                            }}
                          />
                        </div>

                        <div className="md:col-span-2">
                          <label className="mb-1 block font-semibold">Other Cost Notes</label>
                          <input
                            className="form-input"
                            value={payment.other_cost_notes}
                            onChange={(event) => {
                              createForm.setData('payments', updateDraftPayment(createForm.data.payments, index, { other_cost_notes: event.target.value }))
                            }}
                          />
                        </div>

                        <div>
                          <label className="mb-1 block font-semibold">Notes</label>
                          <input
                            className="form-input"
                            value={payment.notes}
                            onChange={(event) => {
                              createForm.setData('payments', updateDraftPayment(createForm.data.payments, index, { notes: event.target.value }))
                            }}
                          />
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="mt-4">
                  <button
                    type="button"
                    className="btn btn-outline-primary"
                    onClick={() => {
                      createForm.setData('payments', [
                        ...createForm.data.payments,
                        blankCreatePayment(enums.paymentStatuses, enums.splitTypes)
                      ])
                    }}
                  >
                    Add Payment
                  </button>
                </div>
              </FormSection>

              <div className="md:col-span-4">
                <button type="submit" className="btn btn-primary">Create Commission</button>
              </div>
            </form>
          </div>
        )}

        <div className="space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold">Existing Commissions</h2>
              <p className="text-sm text-slate-500">Each card shows the summary first. Open only the part you need: commission details or payments.</p>
            </div>
            <div className="rounded border bg-white px-3 py-2 text-sm">
              <span className="font-semibold">Total commissions:</span> {commissions.length}
            </div>
          </div>

          {commissions.length === 0 && (
            <div className="rounded border bg-white p-6 text-center text-slate-500">
              No commissions created for this order yet.
            </div>
          )}

          {commissions.map((commission) => (
            <CommissionCard
              key={commission.id}
              commission={commission}
              order={order}
              activeUsers={activeUsers}
              referrals={referrals}
              externalBeneficiaries={externalBeneficiaries}
              enums={enums}
            />
          ))}
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
