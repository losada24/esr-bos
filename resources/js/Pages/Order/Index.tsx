import React, { useState, useEffect } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type PageProps, type Order, type PaginatorLink, type Role, type Status, Product } from '@/types'
import Pagination from '@/Components/Pagination'
import EyeIcon from '@/Components/Icons/EyeIcon'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { isAdmin, isAccounting, isShipping, isProduction, isSubDealer, isAccountManager, isPlantManager } from '@/Utils/user'
import OrderFilter from './OrderFilter'
import OrderUpdateStatusModal from './OrderUpdateStatusModal'
import CheckIcon from '@/Components/Icons/CheckIcon'
import OrderShowStatusModal from './OrderShowStatusModal'
import { PRODUCT_SYSTEMS, ROLES } from '@/Utils/constants'
import { formatPrice, getGrandTotalByRole } from '@/Utils/price'

type IndexOrderProps = PageProps & {
  orders: {
    data: Order[]
    meta: {
      links: PaginatorLink[]
    }
  }
}

export default function Index ({ auth, orders }: IndexOrderProps) {
  console.log(orders)
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNTING = isAccounting(auth.user.roles.map((role: Role) => role.name))
  const IS_SHIPPING = isShipping(auth.user.roles.map((role: Role) => role.name))
  const IS_PRODUCTION = isProduction(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const IS_PLANT_MANAGER = isPlantManager(auth.user.roles.map((role: Role) => role.name))

  const [showOrderModal, setShowOrderModal] = useState<boolean>(false)
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [showStatusModal, setShowStatusModal] = useState<boolean>(false)
  const [selectedStatusOrder, setSelectedStatusOrder] = useState<Order | null>(null)
  const [statuses, setStatuses] = useState<Status[]>([])

  useEffect(() => {
    fetch(route('order.status.filter', { })).then(async (response) => { return await response.json() }).then((data) => {
      setStatuses(data)
    })
  }, [])

  const getProductCount = (products: Product[]) => {
    return products.filter((product) =>
      product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS ||
      product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER ||
      product.system === PRODUCT_SYSTEMS.SINGLE_HUNG).reduce((acc, product) => {
      return acc + product.qty
    }, 0)
  }

  const getGlassCount = (products: Product[]) => {
    return products.filter((product) =>
      product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS ||
      product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER ||
      product.system === PRODUCT_SYSTEMS.SINGLE_HUNG).reduce((acc, product) => {
      let glassCount = 1
      if (product.system !== PRODUCT_SYSTEMS.FIXED_WINDOWS) {
        glassCount = 2
      }
      return acc + (product.qty * glassCount)
    }, 0)
  }

  const getTotals = () => {
    return orders.data.reduce((acc, order) => {
      return acc + getGrandTotalByRole(order, [ROLES.DEALER])
    }, 0)
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Orders'
      >
        <Head title="Orders" />
        <OrderFilter statuses={statuses} />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Quote #</th>
                <th className="px-6 pt-5 pb-4">Project</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Status</th>
                <th className="px-6 pt-5 pb-4">Counts</th>
                {(IS_ADMIN || IS_ACCOUNTING) && (
                  <th className="px-6 pt-5 pb-4 text-right">Price</th>
                )}
                <th className="px-6 pt-5 pb-4">Created At</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.data.map((order) => {
                const { id, name, project_name, glass_type, created_at, status } = order
                return (
                  <tr
                    key={id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {createMarkWithLeadingZero(id, 6)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {project_name} ({glass_type})
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      <button onClick={() => {
                        if (!isSubDealer(auth.user.roles.map((role: Role) => role.name))) {
                          setSelectedStatusOrder(order)
                          setShowStatusModal(true)
                        }
                      }} title='Show Status History' className="btn btn-outline-primary">{status?.toUpperCase()}</button>
                    </td>
                    <td className="border-t px-6 py-4 align-top text-right">
                      <ul>
                        <li className='flex justify-between mb-1'>
                          <span className='font-semibold text-left'>Product count:</span>
                          <span className="badge my-0 bg-white-light text-black ltr:ml-4 rtl:mr-4">{getProductCount(order.products ?? [])}</span>
                        </li>
                        <li className='flex justify-between'>
                          <span className='font-semibold text-left'>Glass count:</span>
                          <span className="badge my-0 bg-white-light text-black ltr:ml-4 rtl:mr-4">{getGlassCount(order.products ?? [])}</span>
                        </li>
                      </ul>
                    </td>
                    {(IS_ADMIN || IS_ACCOUNTING) && (
                      <td className="border-t px-6 py-4 align-top text-right">
                        {`${formatPrice(getGrandTotalByRole(order, [ROLES.DEALER]))}`}
                      </td>
                    )}
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
                          (IS_ADMIN || IS_ACCOUNTING || IS_ACCOUNT_MANAGER || IS_PRODUCTION || IS_SHIPPING || IS_PLANT_MANAGER) && (
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
            {(IS_ACCOUNTING || IS_ADMIN) && (
              <tfoot>
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={5}>&nbsp;</td>
                  <td className='px-6 py-4 border-t text-right'>
                    {formatPrice(getTotals())}
                  </td>
                  <td className="px-6 py-4 border-t" colSpan={2}>&nbsp;</td>
                </tr>
              </tfoot>
            )}
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
        <OrderShowStatusModal
          showModal={showStatusModal}
          onClose={() => {
            setShowStatusModal(false)
            setSelectedStatusOrder(null)
          }}
          order={selectedStatusOrder}
        />
      </AuthenticatedLayout>
  )
}
