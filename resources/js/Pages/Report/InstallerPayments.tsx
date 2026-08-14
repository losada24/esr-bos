import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router, useForm } from '@inertiajs/react'
import TextInput from '@/Components/TextInput'
import PrimaryButton from '@/Components/PrimaryButton'
import { type PageProps, type User } from '@/types'
import { formatPrice } from '@/Utils/price'
import AngleIcon from '@/Components/Icons/AngleIcon'
import { Fragment, type SyntheticEvent, useState } from 'react'

interface InstallerPaymentRow {
  id: number
  installer_id: number
  installer_name: string
  percentage_payment: number
  installer_payment: number
  extra_work: number
  extra_discount: number
  other_cost_installer: number
  total_payment: number
  payment_status: string
  payment_date: string | null
  notes: string | null
  responsible_extra_work: string | null
  biweekly: {
    id: number
    start_biweekly_period: string | null
    end_biweekly_period: string | null
  } | null
}

interface InstallerPaymentOrder {
  id: number
  name: string
  order_number: number
  status: string
  amount: number
  owners: Array<{
    id: number
    name: string
  }>
  current_installers: Array<{
    id: number
    name: string
    company_name: string
  }>
  payments: InstallerPaymentRow[]
  other_installer_payments: InstallerPaymentRow[]
  total_paid: number
  total_extras: number
  total_discounts: number
  total_other_costs: number
  total_payment: number
  other_installers_total_payment: number
}

type InstallerPaymentsProps = PageProps & {
  installer: User
  companyName: string | null
  orders: InstallerPaymentOrder[]
  filters: {
    search: string
  }
}

const formatBiweekly = (payment: InstallerPaymentRow) => {
  if (!payment.biweekly) {
    return 'No biweekly'
  }

  const start = payment.biweekly.start_biweekly_period ?? 'Unknown'
  const end = payment.biweekly.end_biweekly_period ?? 'Unknown'
  return `${start} to ${end}`
}

const paymentStatusClass = (status: string) => {
  if (status === 'PAID') {
    return 'border-green-600 text-green-700 bg-green-50'
  }

  if (status === 'REVIEW') {
    return 'border-yellow-600 text-yellow-700 bg-yellow-50'
  }

  return 'border-gray-400 text-gray-600 bg-gray-50'
}

export default function InstallerPayments ({ auth, installer, companyName, orders, filters }: InstallerPaymentsProps) {
  const [openOrders, setOpenOrders] = useState<number[]>([])
  const { data, setData } = useForm({
    search: filters.search ?? ''
  })

  const submit = (e: SyntheticEvent) => {
    e.preventDefault()
    router.get(route('report.installer-payments', installer.id), data, {
      replace: true,
      preserveState: true
    })
  }

  const reset = () => {
    setData('search', '')
    router.get(route('report.installer-payments', installer.id), {
      search: ''
    }, {
      replace: true,
      preserveState: false
    })
  }

  const grandTotal = orders.reduce((total, order) => total + Number(order.total_payment || 0), 0)
  const toggleOrder = (orderId: number) => {
    setOpenOrders((current) =>
      current.includes(orderId)
        ? current.filter((id) => id !== orderId)
        : [...current, orderId]
    )
  }

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={`Installer Payments: ${installer.name}`}
      actions={
        <Link
          href={route('report.installer')}
          className="btn btn-outline-primary"
        >
          Back
        </Link>
      }
    >
      <Head title={`Installer Payments: ${installer.name}`} />

      <div className="mb-4">
        <div className="font-semibold text-black">{companyName ?? ''}</div>
        <div className="text-sm text-gray-600">{installer.email}</div>
      </div>

      <form onSubmit={submit}>
        <div className="flex flex-row gap-3 grow">
          <div className="mb-3 w-80">
            <label htmlFor="search">Search</label>
            <TextInput
              id="search"
              name="search"
              value={data.search}
              className="form-input"
              onChange={(e) => { setData('search', e.target.value) }}
              type="text"
              placeholder="Search by order number or order name"
            />
          </div>
          <div className="flex items-end justify-between w-44 pb-3">
            <PrimaryButton className="btn btn-primary">
              Filter
            </PrimaryButton>
            <button
              onClick={reset}
              className="btn btn-outline-primary"
              type="button"
            >
              Reset
            </button>
          </div>
        </div>
      </form>

      <div className="table-responsive overflow-x-auto">
        <table className="table-auto w-full border-collapse">
          <thead className="bg-white sticky top-0 z-10 shadow-md">
            <tr className="font-bold text-left">
              <th className="px-4 pt-5 pb-4 w-10"></th>
              <th className="px-6 pt-5 pb-4">Order #</th>
              <th className="px-6 pt-5 pb-4">Order Name</th>
              <th className="px-6 pt-5 pb-4">Owners</th>
              <th className="px-6 pt-5 pb-4">Order Status</th>
              <th className="px-6 pt-5 pb-4">Current Installer</th>
              <th className="px-6 pt-5 pb-4 text-right">Project Total</th>
              <th className="px-6 pt-5 pb-4 text-right">Installer Payment</th>
              <th className="px-6 pt-5 pb-4 text-right">Total</th>
              <th className="px-6 pt-5 pb-4 text-right">Payments</th>
            </tr>
          </thead>
          <tbody>
            {orders.map((order, orderIndex) => {
              const orderBackground = orderIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50'
              const isHistoricalPayment = !order.current_installers.some((currentInstaller) => currentInstaller.id === installer.id)
              const isOpen = openOrders.includes(order.id)
              const hasOtherInstallerPayments = order.other_installer_payments.length > 0

              return (
                <Fragment key={order.id}>
                  <tr className={`${orderBackground} hover:bg-blue-50 focus-within:bg-blue-50 border-t-2 border-gray-300`}>
                    <td className="px-4 py-4 align-top">
                      <button
                        type="button"
                        onClick={() => { toggleOrder(order.id) }}
                        className={`transition-transform ${isOpen ? '' : '-rotate-90'}`}
                        title={isOpen ? 'Hide payments' : 'Show payments'}
                      >
                        <AngleIcon />
                      </button>
                    </td>
                    <td className="px-6 py-4 align-top">
                      <div className="font-semibold">{order.order_number}</div>
                      {isHistoricalPayment ? (
                        <span className="inline-flex mt-2 px-2 py-0.5 text-xs font-semibold text-indigo-700 border border-indigo-600 bg-indigo-50 rounded">
                          Historical
                        </span>
                      ) : null}
                      {hasOtherInstallerPayments ? (
                        <span className="inline-flex mt-2 ml-1 px-2 py-0.5 text-xs font-semibold text-orange-700 border border-orange-600 bg-orange-50 rounded">
                          Shared payments
                        </span>
                      ) : null}
                    </td>
                    <td className="px-6 py-4 align-top">
                      <div className="font-semibold text-black">{order.name}</div>
                    </td>
                    <td className="px-6 py-4 align-top">{order.owners.map((owner) => owner.name).join(', ')}</td>
                    <td className="px-6 py-4 align-top">{order.status}</td>
                    <td className="px-6 py-4 align-top">{order.current_installers.map((currentInstaller) => currentInstaller.name).join(', ')}</td>
                    <td className="px-6 py-4 align-top text-right">{formatPrice(order.amount)}</td>
                    <td className="px-6 py-4 align-top text-right">{formatPrice(order.total_paid)}</td>
                    <td className="px-6 py-4 align-top text-right font-semibold">{formatPrice(order.total_payment)}</td>
                    <td className="px-6 py-4 align-top text-right">{order.payments.length}</td>
                  </tr>
                  {isOpen ? (
                    <tr className={orderBackground}>
                      <td className="border-t px-4 py-4" colSpan={10}>
                        <table className="w-full">
                          <caption className="px-4 py-2 text-left font-semibold text-black">
                            Payments to {installer.name}
                          </caption>
                          <thead>
                            <tr className="font-semibold text-left">
                              <th className="px-4 py-3">Biweekly</th>
                              <th className="px-4 py-3 text-right">% Paid</th>
                              <th className="px-4 py-3 text-right">Installer Payment</th>
                              <th className="px-4 py-3 text-right">Extra Work</th>
                              <th className="px-4 py-3 text-right">Discount</th>
                              <th className="px-4 py-3 text-right">Other Cost</th>
                              <th className="px-4 py-3 text-right">Total</th>
                              <th className="px-4 py-3">Status</th>
                              <th className="px-4 py-3">Date</th>
                              <th className="px-4 py-3">Notes</th>
                            </tr>
                          </thead>
                          <tbody>
                            {order.payments.map((payment) => (
                              <tr key={`${order.id}-${payment.id}`} className="hover:bg-blue-50">
                                <td className={`border-t px-4 py-3 ${payment.biweekly ? '' : 'text-gray-400'}`}>{formatBiweekly(payment)}</td>
                                <td className="border-t px-4 py-3 text-right">{payment.percentage_payment} %</td>
                                <td className="border-t px-4 py-3 text-right">{formatPrice(payment.installer_payment)}</td>
                                <td className="border-t px-4 py-3 text-right">{formatPrice(payment.extra_work)}</td>
                                <td className="border-t px-4 py-3 text-right">{formatPrice(payment.extra_discount)}</td>
                                <td className="border-t px-4 py-3 text-right">{formatPrice(payment.other_cost_installer)}</td>
                                <td className="border-t px-4 py-3 text-right font-semibold">{formatPrice(payment.total_payment)}</td>
                                <td className="border-t px-4 py-3">
                                  <span className={`inline-flex px-2 py-0.5 text-xs font-semibold border rounded ${paymentStatusClass(payment.payment_status)}`}>
                                    {payment.payment_status}
                                  </span>
                                </td>
                                <td className="border-t px-4 py-3">{payment.payment_date ?? ''}</td>
                                <td className="border-t px-4 py-3">
                                  <div>{payment.notes ?? ''}</div>
                                  {payment.responsible_extra_work ? (
                                    <div className="text-xs text-gray-500">{payment.responsible_extra_work}</div>
                                  ) : null}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                          <tfoot>
                            <tr className="font-semibold bg-gray-100">
                              <td className="border-t px-4 py-3 text-right" colSpan={2}>Order subtotal:</td>
                              <td className="border-t px-4 py-3 text-right">{formatPrice(order.total_paid)}</td>
                              <td className="border-t px-4 py-3 text-right">{formatPrice(order.total_extras)}</td>
                              <td className="border-t px-4 py-3 text-right">{formatPrice(order.total_discounts)}</td>
                              <td className="border-t px-4 py-3 text-right">{formatPrice(order.total_other_costs)}</td>
                              <td className="border-t px-4 py-3 text-right">{formatPrice(order.total_payment)}</td>
                              <td className="border-t px-4 py-3" colSpan={3}></td>
                            </tr>
                          </tfoot>
                        </table>
                        {hasOtherInstallerPayments ? (
                          <table className="w-full mt-4">
                            <caption className="px-4 py-2 text-left font-semibold text-orange-700">
                              Payments to other installers
                            </caption>
                            <thead>
                              <tr className="font-semibold text-left">
                                <th className="px-4 py-3">Installer</th>
                                <th className="px-4 py-3">Biweekly</th>
                                <th className="px-4 py-3 text-right">% Paid</th>
                                <th className="px-4 py-3 text-right">Installer Payment</th>
                                <th className="px-4 py-3 text-right">Extra Work</th>
                                <th className="px-4 py-3 text-right">Discount</th>
                                <th className="px-4 py-3 text-right">Other Cost</th>
                                <th className="px-4 py-3 text-right">Total</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Date</th>
                              </tr>
                            </thead>
                            <tbody>
                              {order.other_installer_payments.map((payment) => (
                                <tr key={`${order.id}-other-${payment.id}`} className="hover:bg-orange-50">
                                  <td className="border-t px-4 py-3">{payment.installer_name}</td>
                                  <td className={`border-t px-4 py-3 ${payment.biweekly ? '' : 'text-gray-400'}`}>{formatBiweekly(payment)}</td>
                                  <td className="border-t px-4 py-3 text-right">{payment.percentage_payment} %</td>
                                  <td className="border-t px-4 py-3 text-right">{formatPrice(payment.installer_payment)}</td>
                                  <td className="border-t px-4 py-3 text-right">{formatPrice(payment.extra_work)}</td>
                                  <td className="border-t px-4 py-3 text-right">{formatPrice(payment.extra_discount)}</td>
                                  <td className="border-t px-4 py-3 text-right">{formatPrice(payment.other_cost_installer)}</td>
                                  <td className="border-t px-4 py-3 text-right font-semibold">{formatPrice(payment.total_payment)}</td>
                                  <td className="border-t px-4 py-3">
                                    <span className={`inline-flex px-2 py-0.5 text-xs font-semibold border rounded ${paymentStatusClass(payment.payment_status)}`}>
                                      {payment.payment_status}
                                    </span>
                                  </td>
                                  <td className="border-t px-4 py-3">{payment.payment_date ?? ''}</td>
                                </tr>
                              ))}
                            </tbody>
                            <tfoot>
                              <tr className="font-semibold bg-orange-50">
                                <td className="border-t px-4 py-3 text-right" colSpan={7}>Other installers total:</td>
                                <td className="border-t px-4 py-3 text-right">{formatPrice(order.other_installers_total_payment)}</td>
                                <td className="border-t px-4 py-3" colSpan={2}></td>
                              </tr>
                            </tfoot>
                          </table>
                        ) : null}
                      </td>
                    </tr>
                  ) : null}
                </Fragment>
              )
            })}
            {orders.length === 0 && (
              <tr>
                <td className="px-6 py-4 border-t" colSpan={10}>
                  No payments found.
                </td>
              </tr>
            )}
          </tbody>
          <tfoot>
            <tr className="font-bold bg-gray-100">
              <td className="px-6 py-4 border-t text-right" colSpan={8}>Grand Total:</td>
              <td className="px-6 py-4 border-t text-right">{formatPrice(grandTotal)}</td>
              <td className="px-6 py-4 border-t"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </AuthenticatedLayout>
  )
}
