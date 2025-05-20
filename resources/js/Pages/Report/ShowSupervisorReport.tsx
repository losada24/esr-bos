import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type Role, type InstallationTeam, type PageProps, type User } from '@/types'
import { formatPrice } from '@/Utils/price'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { isAccountManager, isAdmin, isSupervisor } from '@/Utils/user'
import { useState } from 'react'
import ShowSupervisorFilter from './ShowSupervisorFilter'
import ShowSupervisorReportFilter from './ShowSupervisorReportFilter'

interface OrderSupervisor {
  id: number
  name: string
  city: string
  installation_team: InstallationTeam[]
  owners: User[]
  month: string
  installation_date: string
  final_installation_date: string
  inspection_date: number
  status: string
  pre_inspection: string
  inspection: string
  walk_trough: string
  partial_payment_installation: string
  final_payment_installation: string
  city_permits: boolean
}

type IndexUserProps = PageProps & {
  orders: OrderSupervisor[]
  supervisor: User
  statuses: string[]
  filters: FilterState
}

interface FilterState {
  status: string
  name: string
  start_date: string
  end_date: string
}

export default function ShowSupervisor ({ auth, orders, supervisor, statuses, filters }: IndexUserProps) {
  // console.log(supervisor.id)
  // const totalProjectAmount = orders.reduce((sum, order) => sum + Number(order.project_amount), 0)
  // const totalCommissions = orders.reduce((sum, order) => sum + Number(order.supervisor_commissions), 0)
  const [tableOrders, setTableOrders] = useState<OrderSupervisor[]>(orders)
 // const [paymentDate, setPaymentDate] = useState<string>('')
  const [selectedRows, setSelectedRows] = useState<number[]>([]) // Estado para almacenar las filas seleccionadas
  const handleCheckboxChange = (orderId: number) => {
    setSelectedRows((prevSelected) =>
      prevSelected.includes(orderId)
        ? prevSelected.filter((id) => id !== orderId) // Desmarcar
        : [...prevSelected, orderId] // Marcar
    )
  }
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Projects supervisions ${supervisor.name}`}
              >
        <Head title={`Projects supervisions ${supervisor.name}`} />
        <ShowSupervisorReportFilter id={String(supervisor.id)} statuses={statuses} />
        <div className='table-responsive overflow-x-auto max-h-[700px]'>
          <table className="table-auto w-full border-collapse">
            <thead className="bg-white sticky top-0 z-10 shadow-md">
              <tr className="font-bold text-left">
              <th className="px-4 pt-5 pb-4">
                <input
                type="checkbox"
                onChange={(e) => {
                  if (e.target.checked) {
                    // Selecciona todos
                    setSelectedRows(orders.map((order) => order.id))
                  } else {
                    // Deselecciona todos
                    setSelectedRows([])
                  }
                }}
                checked={selectedRows.length === orders.length && orders.length > 0}
              /></th>
                <th className="px-6 pt-5 pb-4">Start Date</th>
                <th className="px-6 pt-5 pb-4">Inspection Date</th>
                <th className="px-6 pt-5 pb-4">End Date</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Order Status</th>
                <th className="px-6 pt-5 pb-4">City Permit</th>
                <th className="px-6 pt-5 pb-4">City</th>
                <th className="px-6 pt-5 pb-4">Owners</th>
                <th className="px-6 pt-5 pb-4">Installation Teams</th>
                <th className="px-6 pt-5 pb-4">Status Payment</th>
                <th className="px-6 pt-5 pb-4">Delivered Documents</th>
              </tr>
            </thead>
            <tbody>
              {tableOrders.map((order: OrderSupervisor, index) => {
                const isSelected = selectedRows.includes(order.id)
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
                    <td className="px-6 py-4 border-t">
                      {order.inspection_date}
                    </td>
                    <td className="px-6 py-4 border-t ">
                      {order.final_installation_date}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.name}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.status}
                    </td>
                    <td className="px-6 py-4 border-t">
                    {order.city_permits ? 'YES' : ''}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.city}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.owners.map((owner) => {
                        return owner.name
                      }).join(', ')}
                    </td>
                    <td className="px-6 py-4 border-t">
                      <ul>
                        {order.installation_team.map((team) => {
                          return <li key={team.id}>{team.company_name}</li>
                        })}
                      </ul>
                    </td>
                    <td className="px-6 py-4 border-t text-center align-middle">
                    {order.partial_payment_installation}<br />
                    {order.final_payment_installation}
                    </td>
                    <td className="px-6 py-4 border-t text-center align-middle">
                    {order.pre_inspection} <br/>
                    {order.inspection}<br/>
                    {order.walk_trough}
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
