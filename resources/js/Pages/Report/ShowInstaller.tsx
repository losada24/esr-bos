import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type Role, type InstallationTeam, type PageProps, type User, type PaymentExtraFields, type InstallationPayment, type BiweeklyInstaller } from '@/types'
import { formatPrice } from '@/Utils/price'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { isAccountManager, isAdmin, isSupervisor } from '@/Utils/user'
import { useState } from 'react'
import ShowInstallerFilter from './ShowInstallerFilter'
import Supervisor from './Supervisor'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { Console } from 'console'
import Select from 'react-select'
import Index from '../Biweekly/Index'
import { setDefaultResultOrder } from 'dns'

interface OrderInstaller {
  id: number
  name: string
  city: string
  installation_team: InstallationTeam[]
  installation_payments: InstallationPayment[]
  owners: User[]
  amount: number
  initial_payment_percentage: number
  supervisor: string
  payment_extra_fields: PaymentExtraFields
  installation_date: string
  final_installation_date: string
  inspection_installation_date: string
  payment: InstallationPayment []
  project_amount: string
  supervisor_payment_percentage: string
  supervisor_commissions: string
  supervisor_payment_status: string
  supervisor_payment_date: string
  city_permits: boolean
  total_amount: number
  pre_inspection: boolean
  inspection: boolean
  walk_trough: boolean
  partial_payment_installation: boolean
  final_payment_installation: boolean
  status: string
}

type IndexUserProps = PageProps & {
  orders: OrderInstaller[]
  installer: User
  companyName: string
  // installation_teams: InstallationTeam[]
  statuses: string[]
  orderStatuses: string[]
  biweeklys: BiweeklyInstaller[]
}

export default function ShowInstaller ({ auth, orders, installer, companyName, statuses, orderStatuses, biweeklys }: IndexUserProps) {
  const [selectedBiweekly, setSelectedBiweekly] = useState(0)
  const [selectedRows, setSelectedRows] = useState<number[]>([]) // Estado para almacenar las filas seleccionadas
  const handleCheckboxChange = (orderId: number) => {
    setSelectedRows((prevSelected) =>
      prevSelected.includes(orderId)
        ? prevSelected.filter((id) => id !== orderId) // Desmarcar
        : [...prevSelected, orderId] // Marcar
    )
  }
  const grandTotal = orders.reduce((sum, order) => {
    const hasPayments = order.installation_payments && order.installation_payments.length > 0
    if (!hasPayments) return sum
    let totalPayment = 0

    const lastPayment = order.installation_payments[order.installation_payments.length - 1]
    const extraWork = Number(lastPayment.extra_work) || 0
    const extraDiscount = Number(lastPayment.extra_discount) || 0
    const otherCost = Number(lastPayment.other_cost_installer) || 0
    const installerPayment = Number(lastPayment.installer_payment) || 0
    const percentagePayment = Number(lastPayment.percentage_payment) || 0

    if (percentagePayment > 0) {
      totalPayment = Number(installerPayment) + Number(extraWork) - Number(extraDiscount) + Number(otherCost)
    }

    return sum + totalPayment
  }, 0)

  const getPendingTotal = orders.reduce((sum, order) => {
    const totalInstallerPayment = order.installation_payments
      ? order.installation_payments.reduce((total, payment) => total + Number(payment.installer_payment), 0)
      : 0
    return sum + (Number(order.amount) - totalInstallerPayment)
  }, 0)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Project installed by ${companyName} Company and
          Installer Name: ${installer.name}`}
          actions={
            <div className='flex flex-row gap-2 items-right justify-end'>
              <Link
                className="btn btn-primary"
                href={route('report.payment', { id: installer.id, biweekly: selectedBiweekly })}
                onClick={(e) => {
                  if (!window.confirm('Are you sure you want to proceed with the biweekly payment?')) {
                    e.preventDefault() // Evita la navegación si el usuario cancela
                  }
                }}
              >
                <span>Biweekly Payment</span>
              </Link>
              <Link
                className="btn btn-primary"
                href={route('report.send-paid-installer', { id: installer.id, biweekly: selectedBiweekly })}
              >
                <span>Send Email Payment</span>
              </Link>
              <button
                className="btn btn-primary"
                onClick={() => {
                  if (selectedBiweekly !== 0) {
                    const UsedBieekly = biweeklys.find((biweekly) => biweekly.id === selectedBiweekly)
                    window.open(route('report.get-payment-list-installer', { id: installer.id, biweekly: UsedBieekly }), '_blank')
                  } else {
                    alert('Please select a biweekly period')
                  }
                }
                }
              >
                <span>View PDF</span>
              </button>
              <button
                  className="btn btn-primary"
                  onClick={() => {
                    if (selectedBiweekly !== 0) {
                      const UsedBieekly = biweeklys.find((biweekly) => biweekly.id === selectedBiweekly)
                      window.open(route('report.excel-installer', { id: installer.id, biweekly: UsedBieekly }))
                    } else {
                      alert('Please select a biweekly period')
                    }
                  }}
                >
                <span>Export</span>
              </button>
              <Link
                className="btn btn-primary"
                href={route('report.send-payment-installer', { id: installer.id, biweekly: selectedBiweekly })}
              >
                <span>Send Email Review</span>
              </Link>
              <Select
                id='id'
                name='biweekly_id'
                onChange={(value) => { setSelectedBiweekly(value ? value.value : 0) }}
                options={biweeklys.map((biweekly) => {
                  return {
                    label: `${biweekly.start_biweekly_period ? new Date(biweekly.start_biweekly_period).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', day: 'numeric' }) : 'Unknown'} to ${biweekly.end_biweekly_period ? new Date(biweekly.end_biweekly_period).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', day: 'numeric' }) : 'Unknown'}`,
                    value: biweekly.id
                  }
                })}
              />

            </div>
          }
      >
        <Head title={`Project installed by ${companyName}`} />
        <ShowInstallerFilter id={String(installer.id)} statuses={statuses} orderStatuses={orderStatuses} />
            <div className='table-responsive overflow-x-auto max-h-[700px]'>
          <table className="table-auto w-full border-collapse">
            <thead className="bg-white sticky top-0 z-10 shadow-md">
              <tr className="font-bold text-left">
              <th className="px-4 pt-5 pb-4">Select</th>
              <th className="px-6 pt-5 pb-4">Start Date</th>
              <th className="px-6 pt-5 pb-4">Pre-Inspection Date</th>
              <th className="px-6 pt-5 pb-4">End Date</th>
              <th className="px-6 pt-5 pb-4">Order Status</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Owners</th>
                <th className="px-6 pt-5 pb-4">Supervisor</th>
                <th className="px-6 pt-5 pb-4">City Permit</th>
                <th className="px-6 pt-5 pb-4">Total Project Payment</th>
                <th className="px-6 pt-5 pb-4">% Project </th>
               <th className="px-6 pt-5 pb-4">Pending Pay </th>
              <th className="px-6 pt-5 pb-4">Payment Processed</th>
                {/* <th className="px-6 pt-5 pb-4">Pending Pay</th */}
              <th className="px-6 pt-5 pb-4">Extra Work</th>
              <th className="px-6 pt-5 pb-4">Responsible Extra Work</th>
              <th className="px-6 pt-5 pb-4">Extra Discount</th>
              <th className="px-6 pt-5 pb-4">Other Cost</th>
              <th className="px-6 pt-5 pb-4">Total Payment</th>
              <th className="px-6 pt-5 pb-4">Collected Payment</th>
              <th className="px-6 pt-5 pb-4">Remarks</th>
              <th className="px-6 pt-5 pb-4">Delivered Documents</th>
              <th className="px-6 pt-5 pb-4">Status Payment </th>

                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((order: OrderInstaller, index) => {
                const isSelected = selectedRows.includes(order.id)
                const hasPayments = order.installation_payments && order.installation_payments.length > 0
                const lastPayment = hasPayments
                  ? parseFloat(order.installation_payments[order.installation_payments.length - 1].percentage_payment as unknown as string)
                  : null
                const isZeroPayment = lastPayment === 0
                const totalPayment = () => {
                  let total = 0
                  // const hasPayments = order.installation_payments && order.installation_payments.length > 0
                  const extraWork = order.installation_payments && order.installation_payments.length > 0 ? Number(order.installation_payments[order.installation_payments.length - 1].extra_work) : 0
                  const extraDiscount = order.installation_payments && order.installation_payments.length > 0 ? Number(order.installation_payments[order.installation_payments.length - 1].extra_discount) : 0
                  const otherCost = order.installation_payments && order.installation_payments.length > 0 ? Number(order.installation_payments[order.installation_payments.length - 1].other_cost_installer) : 0
                  const installerPayment = order.installation_payments && order.installation_payments.length > 0 ? Number(order.installation_payments[order.installation_payments.length - 1].installer_payment) : 0
                  const percentagePayment = order.installation_payments && order.installation_payments.length > 0 ? Number(order.installation_payments[order.installation_payments.length - 1].percentage_payment) : 0
                  if (percentagePayment > 0) {
                    total = Number(installerPayment) + Number(extraWork) - Number(extraDiscount) + Number(otherCost)
                  }
                  return total }

                const getPendingAmount = () => {
                  const totalInstallerPayment = order.installation_payments
                    ? order.installation_payments.reduce((total, payment) => total + Number(payment.installer_payment), 0)
                    : 0
                  return Number(order.amount) - totalInstallerPayment
                }

                return (
                  <tr key={order.id} className={isSelected ? 'bg-blue-200' : ''}>
                     <td className="px-4 py-4 border-t">
                    <input
                      type="checkbox"
                      onChange={() => { handleCheckboxChange(order.id) }}
                      checked={isSelected}
                    />
                  </td>
                     <td className="px-6 py-4 border-t">
                      {order.installation_date}
                    </td>
                    <td className="px-6 py-4 border-t ">
                      {order.inspection_installation_date}
                    </td>
                    <td className="px-6 py-4 border-t ">
                      {order.final_installation_date}
                    </td>
                    <td className="px-6 py-4 border-t ">
                      {order.status}
                    </td>
                    <td className={`px-6 py-4 border-t ${isZeroPayment ? 'bg-yellow-200' : ''}`}>
                      {order.name}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.owners.map((owner) => {
                        return owner.name
                      }).join(', ')}
                    </td>
                    <td className="px-6 py-4 border-t">
                      <ul>
                        {order.supervisor}
                      </ul>
                    </td>
                    <td className="px-6 py-4 border-t">
                    {order.city_permits ? 'YES' : 'NO'}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {formatPrice(Number(order.amount))}
                    </td>
                   <td className="px-6 py-4 border-t">
                    { order.installation_payments && order.installation_payments.length > 0
                      ? `${order.installation_payments[order.installation_payments.length - 1].percentage_payment} %`
                      : 'N/A'}
                    </td>
                     <td className="px-6 py-4 border-t">
                    {formatPrice(Number(getPendingAmount()))}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {order.installation_payments && order.installation_payments.length > 0
                      ? formatPrice(order.installation_payments[order.installation_payments.length - 1].installer_payment)
                      : 'N/A'}
                    </td>
                   {/* <td className="px-6 py-4 border-t">
                      {formatPrice(Number(getPaymentProcessed()))}
                    </td> */}
                    {/* <td className = "px-6 py-4 border-t">
                      {formatPrice(getGrandTotal() - getPaymentProcessed())}
                    </td> */}
                    <td className="px-6 py-4 border-t">
                    {order.installation_payments && order.installation_payments.length > 0
                      ? formatPrice(order.installation_payments[order.installation_payments.length - 1].extra_work)
                      : 'N/A'}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {order.installation_payments && order.installation_payments.length > 0
                      ? order.installation_payments[order.installation_payments.length - 1].responsible_extra_work
                      : 'N/A'}
                    </td>
                    <td className="px-6 py-4 border-t">
                    { order.installation_payments && order.installation_payments.length > 0
                      ? formatPrice(Number(order.installation_payments[order.installation_payments.length - 1].extra_discount))
                      : 'N/A'}
                    </td>
                    <td className="px-6 py-4 border-t">
                    { order.installation_payments && order.installation_payments.length > 0
                      ? formatPrice(Number(order.installation_payments[order.installation_payments.length - 1].other_cost_installer))
                      : 'N/A'}
                    </td>
                   <td className = "px-6 py-4 border-t">
                      {formatPrice(Number(totalPayment()))}
                    </td>
                    <td className="px-6 py-4 border-t">
                      <ul>
                        <li> {order.partial_payment_installation ? 'PARTIAL' : ' '} </li>
                        <li> {order.final_payment_installation ? 'FINAL' : ' '} </li>
                    </ul>
                    </td>
                    <td> { order.installation_payments && order.installation_payments.length > 0
                      ? order.installation_payments[order.installation_payments.length - 1].notes
                      : 'N/A'}
                     </td>
                    <td>{[order.pre_inspection ? 'PI' : '',
                      order.walk_trough ? 'WT' : '',
                      order.inspection ? 'IN' : ''].filter(Boolean).join(' - ')}</td>
                    <td>{order.payment_extra_fields.installer_payment_status}</td>
                    <td className="border-t flex items-center px-6 py-4">
                      <Link
                        href={route('report.edit_report_installer', { id: order.id, installation_team: installer.id })}
                        title='Edit Order'
                         className='mr-2'
                      >
                        <EditIcon />
                      </Link>
                    </td>
                  </tr>
                )
              })}
              {orders.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Orders found.
                  </td>
                </tr>
              )}
            </tbody>
            <tfoot>
              <tr>
                {/* Espacios vacíos hasta la columna "Value Project" */}

                <td colSpan={11} className="px-2 py-4 border-t  font-bold text-right">Total Pending Pay:</td>
                <td className="px-2 py-4 border-t font-bold text-left">
                  { formatPrice(getPendingTotal)}
                </td>
                <td colSpan={5} className="px-2 py-4 border-t  font-bold text-right">Total Amount:</td>
                <td className="px-2 py-4 border-t font-bold text-left">
                  {formatPrice(grandTotal) }
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </AuthenticatedLayout>
  )
}
