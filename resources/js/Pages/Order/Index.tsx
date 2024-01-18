import React, { useState } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type PageProps, type Order, type PaginatorLink, type Role } from '@/types'
import Pagination from '@/Components/Pagination'
import EyeIcon from '@/Components/Icons/EyeIcon'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { isAdmin, isAccounting, isShipping, isProduction } from '@/Utils/user'
import OrderFilter from './OrderFilter'
import OrderUpdateStatusModal from './OrderUpdateStatusModal'
import CheckIcon from '@/Components/Icons/CheckIcon'

type IndexOrderProps = PageProps & {
  orders: {
    data: Order[]
    meta: {
      links: PaginatorLink[]
    }
  }
}

export default function Index ({ auth, orders }: IndexOrderProps) {
  const [showOrderModal, setShowOrderModal] = useState<boolean>(false)
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Orders'
      >
        <Head title="Orders" />
        <OrderFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Quote #</th>
                <th className="px-6 pt-5 pb-4">Project</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Status</th>
                <th className="px-6 pt-5 pb-4">Created At</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.data.map((order) => {
                const { id, name, project_name, created_at, status } = order
                return (
                  <tr
                    key={id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {createMarkWithLeadingZero(id, 6)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {project_name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {status?.toUpperCase()}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {created_at?.toString()}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('order.show', id)}
                          title='View Order'
                          className='mr-2'
                        >
                          <EyeIcon />
                        </Link>
                        {
                          (isAdmin(auth.user.roles.map((role: Role) => role.name)) ||
                          isAccounting(auth.user.roles.map((role: Role) => role.name)) ||
                          isProduction(auth.user.roles.map((role: Role) => role.name)) ||
                          isShipping(auth.user.roles.map((role: Role) => role.name))) && (
                          <button title='Change Order Status' onClick={() => {
                            setSelectedOrder(order)
                            setShowOrderModal(true)
                          }}>
                            <CheckIcon />
                          </button>
                          )}
                    </td>
                  </tr>
                )
              })}
              {orders.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={6}>
                    No Orders found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={orders.meta.links} />
        <OrderUpdateStatusModal
          showModal={showOrderModal}
          onClose={() => {
            setShowOrderModal(false)
            setSelectedOrder(null)
          }}
          order={selectedOrder}
          // statuses={statuses}
        />
      </AuthenticatedLayout>
  )
}
