import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import { type PageProps, type Order, type PaginatorLink } from '@/types'
import Pagination from '@/Components/Pagination'
import OrderFilter from './OrderFilter'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { useEffect } from 'react'

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
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Order?')) {
      router.delete(route('order.destroy', id))
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
            <Link
              className="btn btn-primary"
              href={route('order.create')}
            >
              <span>Create Order</span>
            </Link>
          }
      >
        <Head title="Orders" />
        <OrderFilter statuses={statuses} />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
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
                return (
                  <tr
                    key={order.id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {order.order_number}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      <div className='font-bold'>{order.name}</div>
                      <div>{order.service}</div>
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order.job_address}
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
                      </ul>
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                      <Link
                        href={route('order.edit', order.id)}
                        title='Edit Order'
                      >
                        <EditIcon />
                      </Link>
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
