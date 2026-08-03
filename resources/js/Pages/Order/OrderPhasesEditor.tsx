import DeleteIcon from '@/Components/Icons/DeleteIcon'
import InputError from '@/Components/InputError'
import { type InstallationTeam, type OrderProduct, type ProductCategory, type ProductConfig, type TypeOfProduct, type User } from '@/types'
import { type OrderFormValues } from './OrderCommon'
import Flatpickr from 'react-flatpickr'
import Select from 'react-select'

const REPLANNED_REASON_OPTIONS = ['CLIENT', 'PERMIT', 'MATERIALS']
const BLOCKED_DELETE_STATUSES = ['CONFIRMED', 'EXECUTION', 'SUPERVISION', 'INSPECTION', 'FINISH', 'FINAL INSPECTION', 'PENDING COLLECT', 'COMPLETE']

type PhaseDraft = NonNullable<OrderFormValues['phases']>[number]

const normalizeTeamOptions = (teams: any[] = []) => teams.map((team) => ({
  value: Number(team.value ?? team.id),
  label: team.label ?? team.user?.name ?? team.company_name ?? `Team ${team.id}`
}))

const dateValue = (value: unknown) => value ? String(value).slice(0, 10) : ''

const productLabel = (
  product: OrderProduct,
  index: number,
  typeOfProducts: TypeOfProduct[],
  productCategories: ProductCategory[],
  productConfigs: ProductConfig[]
) => {
  const productName = product.typeOfProduct?.name ??
    typeOfProducts.find((item) => Number(item.id) === Number(product.type_of_product_id))?.name ??
    'Product'
  const categoryName = product.productCategory?.name ??
    productCategories.find((item) => Number(item.id) === Number(product.product_category_id))?.name ??
    ''
  const configName = product.productConfig?.name ??
    productConfigs.find((item) => Number(item.id) === Number(product.product_config_id))?.name ??
    ''
  const details = [categoryName, configName].filter((item) => item !== '').join(' - ')

  return `#${index + 1} ${productName}${details ? ` - ${details}` : ''} (${product.qty})`
}

export default function OrderPhasesEditor ({
  values,
  setFieldValue,
  errors,
  installationTeams,
  supervisors,
  statuses,
  orderProducts,
  typeOfProducts,
  productCategories,
  productConfigs,
  isParentOrder
}: {
  values: OrderFormValues
  setFieldValue: (field: string, value: any) => void
  errors: any
  installationTeams: InstallationTeam[]
  supervisors: User[]
  statuses: string[]
  orderProducts: OrderProduct[]
  typeOfProducts: TypeOfProduct[]
  productCategories: ProductCategory[]
  productConfigs: ProductConfig[]
  isParentOrder: boolean
}) {
  const phases = values.phases ?? []
  const selectedOrderTeams = normalizeTeamOptions(values.installation_teams ?? [])
  const allTeamOptions = installationTeams.map((team) => ({
    value: team.id,
    label: team.user?.name ?? team.company_name ?? `Team ${team.id}`
  }))
  const supervisorOptions = supervisors.map((supervisor) => ({
    value: supervisor.id,
    label: supervisor.name
  }))

  const syncPhases = (nextPhases: PhaseDraft[]) => {
    setFieldValue('phases', nextPhases.map((phase, index) => ({ ...phase, position: index + 1 })))
  }

  const buildPhase = (position: number): PhaseDraft => ({
    position,
    name: `Phase ${position}`,
    status: typeof values.status === 'string' ? values.status : String((values.status as any)?.value ?? 'PLANNED'),
    delivery_date: position === 1 ? values.delivery_date : null,
    installation_date: position === 1 ? values.installation_date : null,
    installation_end_date: position === 1 ? values.installation_end_date : null,
    supervisor_id: Number((values.supervisor_id as any)?.value ?? values.supervisor_id ?? 0) || null,
    installation_teams: selectedOrderTeams,
    products: [],
    replanned_reasons: [],
    notes: ''
  })

  const toggleInstallByPhases = (checked: boolean) => {
    setFieldValue('install_by_phases', checked)
    if (checked && phases.length === 0) {
      syncPhases([buildPhase(1)])
    }
  }

  const updatePhase = (index: number, patch: Partial<PhaseDraft>) => {
    syncPhases(phases.map((phase, phaseIndex) => phaseIndex === index ? { ...phase, ...patch } : phase))
  }

  const addPhase = () => {
    syncPhases([...phases, buildPhase(phases.length + 1)])
  }

  const removePhase = (index: number) => {
    if (phases.length <= 1) return
    const phase = phases[index]
    if (BLOCKED_DELETE_STATUSES.includes(String(phase.status))) {
      updatePhase(index, { status: 'CANCELED' })
      return
    }
    syncPhases(phases.filter((_, phaseIndex) => phaseIndex !== index))
  }

  const productQty = (phase: PhaseDraft, orderProduct: OrderProduct, productIndex: number) => {
    const orderProductId = Number(orderProduct.id ?? 0)
    return Number((phase.products ?? []).find((item) => {
      if (orderProductId > 0) {
        return Number(item.order_product_id ?? 0) === orderProductId
      }

      return Number(item.product_index ?? -1) === productIndex
    })?.qty ?? 0)
  }

  const updateProductQty = (phaseIndex: number, orderProduct: OrderProduct, productIndex: number, qty: number) => {
    const phase = phases[phaseIndex]
    const products = [...(phase.products ?? [])]
    const orderProductId = Number(orderProduct.id ?? 0)
    const existingIndex = products.findIndex((item) => {
      if (orderProductId > 0) {
        return Number(item.order_product_id ?? 0) === orderProductId
      }

      return Number(item.product_index ?? -1) === productIndex
    })
    const nextProduct = { order_product_id: orderProductId > 0 ? orderProductId : undefined, product_index: productIndex, qty }

    if (qty <= 0) {
      if (existingIndex >= 0) products.splice(existingIndex, 1)
    } else if (existingIndex >= 0) {
      products[existingIndex] = nextProduct
    } else {
      products.push(nextProduct)
    }

    updatePhase(phaseIndex, { products })
  }

  const phaseError = (index: number, field: string) => {
    const directKey = `phases.${index}.${field}`
    const directError = errors?.[directKey]
    const nestedError = errors?.phases?.[index]?.[field]
    return directError ?? nestedError ?? null
  }

  return (
    <fieldset className='p-3 border rounded-xl'>
      <legend className='text-lg font-semibold px-3'>Installation Phases</legend>
      <label className='inline-flex items-center gap-2 font-semibold'>
        <input
          type='checkbox'
          className='form-checkbox'
          checked={Boolean(values.install_by_phases)}
          disabled={isParentOrder}
          onChange={(event) => { toggleInstallByPhases(event.target.checked) }}
        />
        Install by phases
      </label>
      {isParentOrder && <div className='mt-1 text-xs text-slate-500'>Parent orders cannot be installed by phases.</div>}
      {errors?.install_by_phases ? <InputError message={String(errors.install_by_phases)} className='mt-2' /> : null}
      {Boolean(values.install_by_phases) && (
        <div className='mt-4 space-y-4'>
          {phases.map((phase, index) => {
            const selectedTeams = normalizeTeamOptions(phase.installation_teams ?? [])
            const selectedSupervisorId = Number((phase.supervisor_id as any)?.value ?? phase.supervisor_id ?? 0)
            return (
              <div key={phase.id ?? index} className='rounded-md border border-slate-200 p-4'>
                <div className='mb-3 flex items-start justify-between gap-3'>
                  <div className='grid flex-1 grid-cols-1 gap-3 md:grid-cols-5'>
                    <div>
                      <label>Name</label>
                      <input className='form-input' value={phase.name ?? ''} onChange={(event) => { updatePhase(index, { name: event.target.value }) }} />
                    </div>
                    <div>
                      <label>Status</label>
                      <select className='form-select' value={phase.status ?? 'PLANNED'} onChange={(event) => { updatePhase(index, { status: event.target.value }) }}>
                        {statuses.map((status) => <option key={status} value={status}>{status}</option>)}
                      </select>
                    </div>
                    <div>
                      <label>Delivery Date</label>
                      <Flatpickr className='form-input' required value={dateValue(phase.delivery_date)} options={{ dateFormat: 'Y-m-d' }} onChange={([date]) => { updatePhase(index, { delivery_date: date?.toISOString().slice(0, 10) ?? null }) }} />
                      {phaseError(index, 'delivery_date') ? <InputError message={String(phaseError(index, 'delivery_date'))} className='mt-1' /> : null}
                    </div>
                    <div>
                      <label>Installation Start</label>
                      <Flatpickr className='form-input' required value={dateValue(phase.installation_date)} options={{ dateFormat: 'Y-m-d' }} onChange={([date]) => { updatePhase(index, { installation_date: date?.toISOString().slice(0, 10) ?? null }) }} />
                      {phaseError(index, 'installation_date') ? <InputError message={String(phaseError(index, 'installation_date'))} className='mt-1' /> : null}
                    </div>
                    <div>
                      <label>Installation End</label>
                      <Flatpickr className='form-input' required value={dateValue(phase.installation_end_date)} options={{ dateFormat: 'Y-m-d' }} onChange={([date]) => { updatePhase(index, { installation_end_date: date?.toISOString().slice(0, 10) ?? null }) }} />
                      {phaseError(index, 'installation_end_date') ? <InputError message={String(phaseError(index, 'installation_end_date'))} className='mt-1' /> : null}
                    </div>
                  </div>
                  <button type='button' className='mt-7 text-danger disabled:opacity-40' disabled={phases.length <= 1} onClick={() => { removePhase(index) }} title='Delete or cancel phase'>
                    <DeleteIcon />
                  </button>
                </div>
                <div className='grid grid-cols-1 gap-3 md:grid-cols-2'>
                  <div>
                    <label>Installers</label>
                    <Select isMulti value={selectedTeams} options={allTeamOptions} onChange={(value) => { updatePhase(index, { installation_teams: value as any[] }) }} />
                  </div>
                  <div>
                    <label>Supervisor</label>
                    <Select isClearable value={supervisorOptions.find((option) => option.value === selectedSupervisorId) ?? null} options={supervisorOptions} onChange={(value) => { updatePhase(index, { supervisor_id: value ? value.value : null }) }} />
                  </div>
                </div>
                {phase.status === 'REPLANNED' && (
                  <div className='mt-3 flex flex-wrap gap-4'>
                    {REPLANNED_REASON_OPTIONS.map((reason) => (
                      <label key={reason} className='inline-flex items-center gap-2'>
                        <input
                          type='checkbox'
                          className='form-checkbox'
                          checked={(phase.replanned_reasons ?? []).includes(reason)}
                          onChange={(event) => {
                            const current = phase.replanned_reasons ?? []
                            updatePhase(index, {
                              replanned_reasons: event.target.checked
                                ? Array.from(new Set([...current, reason]))
                                : current.filter((item) => item !== reason)
                            })
                          }}
                        />
                        {reason}
                      </label>
                    ))}
                  </div>
                )}
                <div className='mt-3'>
                  <label>Notes</label>
                  <textarea className='form-textarea' rows={2} value={phase.notes ?? ''} onChange={(event) => { updatePhase(index, { notes: event.target.value }) }} />
                </div>
                {orderProducts.length > 0 && (
                  <div className='mt-4 overflow-x-auto'>
                    <table className='w-full text-sm'>
                      <thead>
                        <tr className='text-left'>
                          <th className='border-b py-2'>Product</th>
                          <th className='border-b py-2 w-40'>Qty in phase</th>
                        </tr>
                      </thead>
                      <tbody>
                        {orderProducts.map((product, productIndex) => (
                          <tr key={product.id ?? productIndex}>
                            <td className='border-b py-2'>{productLabel(product, productIndex, typeOfProducts, productCategories, productConfigs)}</td>
                            <td className='border-b py-2'>
                              <input
                                className='form-input text-right'
                                type='number'
                                min='0'
                                max={product.qty}
                                step='0.01'
                                value={productQty(phase, product, productIndex)}
                                onChange={(event) => { updateProductQty(index, product, productIndex, Number(event.target.value)) }}
                              />
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
                {(phase.logs?.length ?? 0) > 0 && (
                  <details className='mt-4'>
                    <summary className='cursor-pointer text-sm font-semibold'>History</summary>
                    <div className='mt-2 space-y-2 text-xs text-slate-600'>
                      {(phase.logs ?? []).slice(0, 8).map((log: any) => (
                        <div key={log.id} className='border-l-2 border-slate-300 pl-3'>
                          <div className='font-semibold'>{log.action} {log.status ? `- ${log.status}` : ''}</div>
                          <div>{log.user?.name ?? 'System'} · {log.created_at ?? ''}</div>
                        </div>
                      ))}
                    </div>
                  </details>
                )}
              </div>
            )
          })}
          <button type='button' className='btn btn-outline-primary' onClick={() => { addPhase() }}>Add Phase</button>
        </div>
      )}
    </fieldset>
  )
}
