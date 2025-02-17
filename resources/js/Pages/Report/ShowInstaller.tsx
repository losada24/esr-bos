import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type Role, type InstallationTeam, type PageProps, type User, PaymentExtraFields, InstallationPayment } from '@/types'
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
  owners: User[]
  amount: number
  initial_payment_percentage: number
  supervisor: string
  payment_extra_fields: PaymentExtraFields
  installation_date: string
  final_installation_date: string
  inspection_installation_date: string
  execution_planing_date: number
  payment: InstallationPayment []
  project_amount: string
  supervisor_payment_percentage: string
  supervisor_commissions: string
  supervisor_payment_status: string
  supervisor_payment_date: string
  city_permits: boolean
  total_amount: number
}

type IndexUserProps = PageProps & {
  orders: OrderInstaller[]
  installer: User
  companyName: string
  // installation_teams: InstallationTeam[]
  // statuses: string[]
}

export default function ShowInstaller ({ auth, orders, installer, companyName }: IndexUserProps) {
  // console.log(supervisor.id)
  console.log(installer.id)
  const totalProjectAmount = orders.reduce((sum, order) => sum + Number(order.project_amount), 0)
  const totalCommissions = orders.reduce((sum, order) => sum + Number(order.supervisor_commissions), 0)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Project installed by ${companyName} Company and
          Installer Name: ${installer.name}`}
      >
        <Head title={`Project installed by ${companyName}`} />
        <ShowInstallerFilter id={String(installer.id)} />
            <div className='table-responsive'>
          <table className="table-auto w-full">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Start Date</th>
              <th className="px-6 pt-5 pb-4">Inspection Date</th>
              <th className="px-6 pt-5 pb-4">End Date</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Owners</th>
                <th className="px-6 pt-5 pb-4">Supervisor</th>
                <th className="px-6 pt-5 pb-4">City Permit</th>
                <th className="px-6 pt-5 pb-4">Total Project Payment</th>
                <th className="px-6 pt-5 pb-4">% Project</th>
                <th className="px-6 pt-5 pb-4">Payment Processed</th>
                <th className="px-6 pt-5 pb-4">Pending Pay</th>
                <th className="px-6 pt-5 pb-4">Extra Work</th>
                <th className="px-6 pt-5 pb-4">Responsible Extra Work</th>
                <th className="px-6 pt-5 pb-4">Extra Discount</th>
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
                    {order.city_permits ? 'YES' : ''}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {formatPrice(Number(order.amount))}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.initial_payment_percentage} %
                    </td>
                    <td className="px-6 py-4 border-t">
                    </td>
                    <td className = "px-6 py-4 border-t">
                    </td>
                    <td className="px-6 py-4 border-t">
                      {formatPrice(Number(order.payment_extra_fields.extra_work) || 0)}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {order.payment_extra_fields.responsible_extra_work}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {formatPrice(Number(order.payment_extra_fields.extra_discount) || 0)}
                    </td>
                    <td className="px-6 py-4 border-t">
                    <td className="px-6 py-4 border-t">
                    </td>
                    </td>
                    <td className="px-6 py-4 border-t">
                    {order.payment_extra_fields.collected_payment ? 'YES' : 'NO'}
                    </td>
                    <td> {order.payment_extra_fields.notes}</td>
                    <td>{order.payment_extra_fields.documents_submitted}</td>
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
            <td colSpan={10} className="px-6 py-4 border-t"></td>
            <td className="px-6 py-4 border-t font-bold text-left">
              {formatPrice(totalProjectAmount)}
            </td>
            {/* Espacios vacíos hasta la columna "Commissions" */}
            <td className="px-6 py-4 border-t"></td>
            <td className="px-6 py-4 border-t font-bold text-left">
              {formatPrice(totalCommissions)}
            </td>
            {/* Espacios vacíos después de las columnas */}
            <td colSpan={2} className="px-6 py-4 border-t"></td>
          </tr>
            </tfoot>
          </table>
        </div>
      </AuthenticatedLayout>
  )
}
