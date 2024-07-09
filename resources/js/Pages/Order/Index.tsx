import React, { useState, useEffect } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import { type PageProps, type Order, type PaginatorLink } from '@/types'
import Pagination from '@/Components/Pagination'
import OrderFilter from './OrderFilter'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'

type IndexOrderProps = PageProps & {
  orders: {
    data: Order[]
    meta: {
      links: PaginatorLink[]
    }
  },
  statuses: string[]
}

export default function Index ({ auth, orders, statuses }: IndexOrderProps) {
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Estimate?')) {
      router.delete(route('estimate.destroy', id))
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
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Job Address</th>
                <th className="px-6 pt-5 pb-4">Client</th>
                <th className="px-6 pt-5 pb-4 text-right">Installer</th>
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
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order.job_address}
                    </td>
                    <td className="border-t px-6 py-4 align-top text-right">
                      {order?.client?.name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order?.installer?.name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {order.created_at?.toString()}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                      <Link
                        href={route('estimate.edit', order.id)}
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
