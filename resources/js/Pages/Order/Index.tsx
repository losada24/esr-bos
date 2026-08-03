import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import { type PageProps, type Order, type PaginatorLink } from '@/types'
import Pagination from '@/Components/Pagination'
import OrderFilter from './OrderFilter'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { useEffect, useState } from 'react'
import EyeIcon from '@/Components/Icons/EyeIcon'
import CopyIcon from '@/Components/Icons/CopyIcon'

type IndexOrderProps = PageProps & {
  orders: {
    data: Order[]
    meta: {
      links: PaginatorLink[]
    }
  }
  statuses: string[]
}

export default function Index ({ auth, orders, statuses }: IndexOrderProps) {
 const [selectedRows, setSelectedRows] = useState<number[]>([])
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Order?')) {
      router.delete(route('order.destroy', id))
    }
  }
  const handleCheckboxChange = (orderId: number) => {
    setSelectedRows((prevSelected) =>
      prevSelected.includes(orderId)
        ? prevSelected.filter((id) => id !== orderId) // Desmarcar
        : [...prevSelected, orderId] // Marcar
    )
  }
  const duplicate = (id: number) => {
    if (confirm('Are you sure you want to duplicate this Order?')) {
      router.get(route('order.duplicate', id))
    }
  }

  useEffect(() => {
    /* fetch(route('order.status.filter', { })).then(async (response) => { return await response.json() }).then((data) => {
      setStatuses(data)
    }) */
  }, [])

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Orders'
          actions={
            <div className="flex gap-2">
              <Link
                className="btn btn-primary"
                href={route('order.create')}
              >
                <span>Create Order</span>
              </Link>
              <Link
                className="btn btn-secondary"
                href={route('order.create_service')}
              >
                <span>Create Service</span>
              </Link>
            </div>
          }
      >
        <Head title="Orders" />
        <OrderFilter statuses={statuses} />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-4 pt-5 pb-4">Select</th>
                <th className="px-6 pt-5 pb-4">Order Number</th>
                <th className="px-6 pt-5 pb-4">Name / Service</th>
                <th className="px-6 pt-5 pb-4">Job Address</th>
                <th className="px-6 pt-5 pb-4">Client</th>
                <th className="px-6 pt-5 pb-4">Installer</th>
                <th className="px-6 pt-5 pb-4">Supervisor</th>
                <th className="px-6 pt-5 pb-4">Dates</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.data.map((order) => {
                const isSelected = selectedRows.includes(order.id)
                return (
                  // console.log(order),
                  <tr
                    key={order.id}
                    className={isSelected ? 'bg-blue-200' : 'hover:bg-gray-100 focus-within:bg-gray-100'}
                  >
                    <td className="px-4 py-4 border-t">
                    <input
                      type="checkbox"
                      onChange={() => { handleCheckboxChange(order.id) }}
                      checked={isSelected}
                    />
                  </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order.order_number}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      <div className='font-bold'>{order.name}</div>
                      <div>{order.service}</div>
                      {order.is_supply && (
                        <div className="mt-1 inline-block rounded-md border border-emerald-300 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                          SUPPLY
                        </div>
                      )}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {`${order.job_address ?? ''}${order.city ? `, ${order.city}` : ''}${order.job_state ? `, ${order.job_state}` : ''}${order.job_zip ? `, ${order.job_zip}` : ''}`}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order?.client?.name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order.installation_teams.map((team) => {
                        return (
                          <span key={team.id} className="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">
                            {team.user?.name}
                          </span>
                        )
                      })}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order?.supervisor?.name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      <ul>
                        <li><strong>Entry Date:</strong> {order.entry_date?.toString()}</li>
                        <li><strong>Contract Signing Date:</strong> {order.contract_signing_date?.toString()}</li>
                        <li><strong>Payment Factory Date:</strong> {order.payment_factory_date?.toString()}</li>
                        <li><strong>Delivery Date:</strong> {order.delivery_date?.toString()}</li>
                        <li><strong>Installation Date:</strong> {order.installation_date?.toString()}</li>
                        {order.install_by_phases && (
                          <li>
                            <strong>Phases:</strong> {order.phases_completed_count ?? 0}/{order.phases_count ?? 0} complete
                            {order.next_phase ? ` · Next: ${order.next_phase.name} (${order.next_phase.status}) ${order.next_phase.installation_date ?? ''}` : ''}
                          </li>
                        )}
                      </ul>
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                      <Link
                        href={route('order.edit', order.id)}
                        title='Edit Order'
                         className='mr-2'
                      >
                        <EditIcon />
                      </Link>
                      <Link
                        href={route('order.status_order', order.id)}
                        title='Order History'
                         className='mr-2'
                      >
                        <EyeIcon />
                      </Link>
                      <button
                            onClick={() => { duplicate(order.id) }}
                            title='Duplicate Order'
                          >
                            <CopyIcon className='mr-2'/>
                        </button>
                      <button
                        onClick={() => { destroy(order.id) }}
                        title='Delete Order'
                      >
                        <DeleteIcon />
                      </button>
                    </td>
                  </tr>
                )
              })}
              {orders.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={7}>
                    No Orders found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={orders.meta.links} />
      </AuthenticatedLayout>
  )
}
