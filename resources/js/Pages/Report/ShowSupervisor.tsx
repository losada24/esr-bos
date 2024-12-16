import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head } from '@inertiajs/react'
import { type InstallationTeam, type PageProps, type User } from '@/types'
import { formatPrice } from '@/Utils/price'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'

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

}

type IndexUserProps = PageProps & {
  orders: OrderSupervisor[]
  supervisor: User
}

export default function ShowSupervisor ({ auth, orders, supervisor }: IndexUserProps) {
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Projects supervisions ${supervisor.name}`}
      >
        <Head title={`Projects supervisions ${supervisor.name}`} />
        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
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
                <th className="px-6 pt-5 pb-4">Date Paid</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((order: OrderSupervisor) => {
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
                      {order.installation_team.map((team) => {
                        return team.company_name
                      }).join(', ')}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.month}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.installation_date}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.final_installation_date}
                    </td>
                    <td className="px-6 py-4 border-t">
                      {order.execution_planing_date}
                    </td>
                    <td className="px-6 py-4 border-t">
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
                    <td className="px-6 py-4 border-t">
                      {order.supervisor_payment_date}
                          <Flatpickr
                          options={{
                            mode: 'single',
                            dateFormat: 'Y-m-d',
                            position: 'auto right'
                          }}
                          // disabled={values.supervisor_id === ''}
                          name="supervisor_payment_date"
                          value={order.supervisor_payment_date}
                          className="form-input"
                          onChange={([date]) => {
                            if (date) {
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
          </table>
        </div>
      </AuthenticatedLayout>
  )
}
