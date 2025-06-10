import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import { type PageProps, type Order, type PaginatorLink } from '@/types'
import Pagination from '@/Components/Pagination'
import OrderFilter from './OrderFilter'
import { useEffect } from 'react'
import { type OrderStatus } from '@/types/interfaces/order'

type IndexOrderProps = PageProps & {
  orderStatuses: OrderStatus[]
  order: Order
}

export default function ShowStatusOrder ({ auth, orderStatuses, order }: IndexOrderProps) {
  useEffect(() => {
    /* fetch(route('order.status.filter', { })).then(async (response) => { return await response.json() }).then((data) => {
      setStatuses(data)
    }) */
  }, [])
  console.log(orderStatuses)
  return (
       <AuthenticatedLayout
          auth={auth}
          pageTitle={`Order History : ${order.name}`}
      >
         <div className='mb-3 w-64'>
                <div className='flex flex-row justify-start'>
                 <div className='badge badge-outline-dark'>{order.service}</div>
                </div>
        </div>

        <Head title="Order History" />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Date </th>
                <th className="px-6 pt-5 pb-4">Status</th>
                <th className="px-6 pt-5 pb-4">Usuario</th>
                <th className="px-6 pt-5 pb-4">Delivery/Pickup Date</th>
                <th className="px-6 pt-5 pb-4">Installation Date</th>
                <th className="px-6 pt-5 pb-4"> Installation End Date</th>
                <th className="px-6 pt-5 pb-4">Materials Received Date</th>
                <th className="px-6 pt-5 pb-4">Inspection Date</th>
                <th className="px-6 pt-5 pb-4">Finish Date</th>
                <th className="px-6 pt-5 pb-4">Service Date</th>
                <th className="px-6 pt-5 pb-4">Final Inspection Date</th>
                <th className="px-6 pt-5 pb-4">Complete Date</th>
              </tr>
            </thead>
            <tbody>
              {orderStatuses.map((order) => {
                return (
                  <tr
                    key={order.id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                    { order.created_at_formatted}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order.status}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order?.user?.name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {['PLANNED', 'CONFIRMED', 'DELIVERY CONFIRMED'].includes(order.status) ? order.pickup_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {['PLANNED', 'CONFIRMED', 'RESCHEDULED', 'SUPERVISION'].includes(order.status) ? order.start_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {['PLANNED', 'CONFIRMED', 'RESCHEDULED', 'SUPERVISION'].includes(order.status) ? order.end_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {order.status === 'MATERIALS RECEIVED' ? order.material_received_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {order.status === 'INSPECTION' ? order.inspection_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {order.status === 'FINISH' ? order.finish_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {order.status === 'SERVICE' ? order.service_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {order.status === 'FINAL INSPECTION' ? order.final_inspection_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    { order.status === 'COMPLETE' ? order.complete_date?.toString() : ''}
                    </td>
                  </tr>
                )
              })}
              {orderStatuses.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={7}>
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
