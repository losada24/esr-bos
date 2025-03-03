import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type Role, type InstallationTeam, type PageProps, type User, type PaymentExtraFields, type InstallationPayment } from '@/types'
import { formatPrice } from '@/Utils/price'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { isAccountManager, isAdmin, isSupervisor } from '@/Utils/user'
import { useState } from 'react'
import ShowInstallerFilter from './ShowInstallerFilter'
import Supervisor from './Supervisor'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'

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
}

export default function ShowInstaller ({ auth, orders, installer, companyName, statuses }: IndexUserProps) {
  // console.log(supervisor.id)
  console.log(orders)
  // const totalProjectAmount = orders.reduce((sum, order) => sum + Number(order.project_amount), 0)
  // const totalCommissions = orders.reduce((sum, order) => sum + Number(order.supervisor_commissions), 0)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Project installed by ${companyName} Company and
          Installer Name: ${installer.name}`}
      >
        <Head title={`Project installed by ${companyName}`} />
        <ShowInstallerFilter id={String(installer.id)} statuses={statuses} />
            <div className='table-responsive'>
          <table className="table-auto w-full">
            <thead>
              <tr className="font-bold text-left">
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
               {/* <th className="px-6 pt-5 pb-4">%  Payment Project </th> */}
               {/* <th className="px-6 pt-5 pb-4">Payment Processed</th> */}
                {/* <th className="px-6 pt-5 pb-4">Pending Pay</th */}
                <th className="px-6 pt-5 pb-4">Extra Work</th>
                <th className="px-6 pt-5 pb-4">Responsible Extra Work</th>
                <th className="px-6 pt-5 pb-4">Extra Discount</th>
                 <th className="px-6 pt-5 pb-4">Other Cost</th>
                <th className="px-6 pt-5 pb-4">Collected Payment</th>
                <th className="px-6 pt-5 pb-4">Remarks</th>
                <th className="px-6 pt-5 pb-4">Delivered Documents</th>
                <th className="px-6 pt-5 pb-4">Status Payment </th>

                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((order: OrderInstaller, index) => {
                const extra_work = order.installation_payments?.reduce((sum, payment) => {
                  return Number(sum) + Number(payment.extra_work ?? 0)
                }, 0) ?? 0

                const other_cost_installer = order.installation_payments?.reduce((sum, payment) => {
                  return Number(sum) + Number(payment.other_cost_installer ?? 0)
                }, 0) ?? 0

                const extra_discount = order.installation_payments?.reduce((sum, payment) => {
                  return Number(sum) + Number(payment.extra_discount ?? 0)
                }, 0) ?? 0
                /* const getGrandTotal = () => {
                  const extra_work = order.installation_payments?.extra_work ?? 0
                  const other_cost_installer = order.payment_extra_fields?.other_cost_installer ?? 0
                  const extra_discount = order.payment_extra_fields?.extra_discount ?? 0
                  const result = Number(extra_work) + Number(other_cost_installer) - Number(extra_discount)
                         return order.amount + Number(result)
                    } */
                /* const getPaymentProcessed = () => {     if (!order.installation_payments || order.installation_payments.length === 0) return 0
                      return order.installation_payments.reduce((acc, p) => acc + Number(p.installer_payment || 0), 0)
                     } */

                return (
                  <tr key={order.id}>
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
                    <td className="px-6 py-4 border-t">
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
                      {order.initial_payment_percentage} %
                    </td>
                   {/* <td className="px-6 py-4 border-t">
                    {order.installation_payments.map((payment) => {
                      return payment.percentage_payment
                    }).join(', ')} %
                    </td> */}
                   {/* <td className="px-6 py-4 border-t">
                      {formatPrice(Number(getPaymentProcessed()))}
                    </td> */}
                    {/* <td className = "px-6 py-4 border-t">
                      {formatPrice(getGrandTotal() - getPaymentProcessed())}
                    </td> */}
                    <td className="px-6 py-4 border-t">
                      {formatPrice(extra_work)}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {order.payment_extra_fields.responsible_extra_work}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {formatPrice(Number(extra_discount))}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {formatPrice(Number(other_cost_installer))}
                    </td>
                    <td className="px-6 py-4 border-t">
                      <ul>
                        <li> {order.partial_payment_installation ? 'PARTIAL' : ' '} </li>
                        <li> {order.final_payment_installation ? 'FINAL' : ' '} </li>
                    </ul>
                    </td>
                    <td> {order.payment_extra_fields.notes}</td>
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
            </tfoot>
          </table>
        </div>
      </AuthenticatedLayout>
  )
}
