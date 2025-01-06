import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head } from '@inertiajs/react'
import { type Role, type InstallationTeam, type PageProps, type User } from '@/types'
import { formatPrice } from '@/Utils/price'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { isAccountManager, isAdmin, isSupervisor } from '@/Utils/user'
import { useState } from 'react'
import ShowSupervisorFilter from './ShowSupervisorFilter'

interface OrderSupervisor {
  id: number
  name: string
  city: string
  installation_team: InstallationTeam[]
  owners: User[]
  month: string
  installation_date: string
  final_installation_date: string
  execution_planing_date: number
  qty_days: number
  project_amount: string
  supervisor_payment_percentage: string
  supervisor_commissions: string
  supervisor_payment_status: string
  supervisor_payment_date: string
  city_permits: boolean
  total_amount: number
}

type IndexUserProps = PageProps & {
  orders: OrderSupervisor[]
  supervisor: User
  statuses: string[]
}

export default function ShowSupervisor ({ auth, orders, supervisor, statuses }: IndexUserProps) {
  // console.log(supervisor.id)
  const totalProjectAmount = orders.reduce((sum, order) => sum + Number(order.project_amount), 0)
  const totalCommissions = orders.reduce((sum, order) => sum + Number(order.supervisor_commissions), 0)
  const [tableOrders, setTableOrders] = useState<OrderSupervisor[]>(orders)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Projects supervisions ${supervisor.name}`}
      >
        <Head title={`Projects supervisions ${supervisor.name}`} />
        <ShowSupervisorFilter id={String(supervisor.id)} statuses={statuses}/>
        <div className='table-responsive'>
          <table className="table-auto w-full">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">City Permit</th>
                <th className="px-6 pt-5 pb-4">City</th>
                <th className="px-6 pt-5 pb-4">Owners</th>
                <th className="px-6 pt-5 pb-4">Installation Teams</th>
                <th className="px-6 pt-5 pb-4">Month</th>
                <th className="px-6 pt-5 pb-4">Start Date</th>
                <th className="px-6 pt-5 pb-4">End Date</th>
                <th className="px-6 pt-5 pb-4">Planning Date</th>
                <th className="px-6 pt-5 pb-4">Qty Date</th>
                <th className="px-6 pt-5 pb-4">Value Project</th>
                <th className="px-6 pt-5 pb-4">% Commissions</th>
                <th className="px-6 pt-5 pb-4">Commissions</th>
                <th className="px-6 pt-5 pb-4">Status</th>
                <th className="px-6 pt-5 pb-4 wide-column">Date Paid</th>
              </tr>
            </thead>
            <tbody>
              {tableOrders.map((order: OrderSupervisor, index) => {
                return (
                  <tr key={order.id}>
                    <td className="px-6 py-4 border-t">
                      {order.name}
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
                    <td className="px-6 py-4 border-t">
                      {order.month}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.installation_date}
                    </td>
                    <td className="px-6 py-4 border-t ">
                      {order.final_installation_date}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.execution_planing_date}
                    </td>
                    <td className={`px-6 py-4 border-t ${
                      order.qty_days > order.execution_planing_date ? 'text-red-500' : ''
                    }`}>
                      {order.qty_days}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {formatPrice(Number(order.project_amount))}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.supervisor_payment_percentage}%
                    </td>
                    <td className="px-6 py-4 border-t">
                      {formatPrice(Number(order.supervisor_commissions))}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.supervisor_payment_status}
                    </td>
                    <td className="px-6 pt-5 pb-4 wide-column">
                        <Flatpickr
                          options={{
                            mode: 'single',
                            dateFormat: 'Y-m-d',
                            position: 'auto right'
                          }}
                          disabled={isSupervisor(auth.user.roles.map((role: Role) => role.name))}
                          name="supervisor_payment_date"
                          value={order.supervisor_payment_date}
                          className="form-input"
                          onChange={([date]) => {
                            if (date) {
                              fetch(route('order.update_date_paid'), {
                                method: 'POST',
                                headers: {
                                  'Content-Type': 'application/json',
                                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
                                },
                                body: JSON.stringify({
                                  order_id: order.id,
                                  date_paid: date.toISOString().slice(0, 10)
                                })
                              }).then((response) => {
                                if (response.ok) {
                                  const newOrders = [...orders]
                                  newOrders[index].supervisor_payment_status = 'CLOSED'
                                  setTableOrders([...newOrders])
                                }
                              })
                              // Manejar la fecha seleccionada
                              // handleInputChange('supervisor_payment_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                            }
                          }}
                        />
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
