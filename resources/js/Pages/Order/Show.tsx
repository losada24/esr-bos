import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type PageProps, type Order, type Client, type Role } from '@/types'
import Panel from '@/Components/Panel'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import HammerIcon from '@/Components/Icons/HammerIcon'
import { isAccounting, isAdmin } from '@/Utils/user'
import { PRODUCTION_STATUS } from '@/Utils/constants'

export default function Show ({ auth, order }: PageProps & {
  clients: Client[]
  order: Order
}) {
  const IS_ACCOUNTING = isAccounting(auth.user.roles.map((role: Role) => role.name))
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const getSubtotal = () => {
    const subtotal: number | undefined = order.products?.reduce((acc, product) => {
      return acc + Number(product.total_price)
    }, 0)

    return Math.round(subtotal ?? 0)
  }

  const getGrandTotal = () => {
    const subtotal: number = getSubtotal() ?? 0
    return Math.round(Number(subtotal))
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Order ${createMarkWithLeadingZero(order.id, 6)}`}
      >
          <Head title={`Order ${createMarkWithLeadingZero(order.id, 6)}`} />
          <div className='grid gap-6 grid-cols-12'>
            <div className='col-span-2'>
              <Panel className='pb-0'>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Quote #</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">{createMarkWithLeadingZero(order.id, 6)}</div>
                </div>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Client</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">{order.client?.name}</div>
                </div>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Project</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">
                    {order.project_name ? order.project_name : 'No Project'}
                  </div>
                </div>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Created At</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">
                    {order.created_at ? order.created_at.toString() : 'No Date'}
                  </div>
                </div>
                <div className="flex flex-col gap-y-2 border-t border-white-light dark:border-white/10 py-2">
                    {order.status === PRODUCTION_STATUS && (
                      <Link href={route('order.workOrder', order.id)} className="btn btn-secondary w-full gap-2">
                          <HammerIcon color="#fff" />
                          Work Order
                      </Link>
                    )}
                </div>
              </Panel>
            </div>
            <div className='col-span-10'>
              <div className='table-responsive'>
                <table className="w-full whitespace-nowrap">
                  <thead>
                    <tr className="font-bold text-left">
                      <th className="px-6 pt-5 pb-4">System</th>
                      <th className="px-6 pt-5 pb-4">Mark</th>
                      <th className="px-6 pt-5 pb-4 text-right">Qty</th>
                      <th className="px-6 pt-5 pb-4">Size</th>
                      <th className="px-6 pt-5 pb-4">Frame Color</th>
                      <th className="px-6 pt-5 pb-4">Glass</th>
                      {(IS_ACCOUNTING || IS_ADMIN) && (
                        <>
                          <th className="px-6 pt-5 pb-4">Price</th>
                          <th className="px-6 pt-5 pb-4">Amount</th>
                        </>
                      )}
                    </tr>
                  </thead>
                  <tbody>
                    {order.products?.map(({ id, system, line_item_name, qty, width, height, frame_color, glass_type, unit_price, total_price }) => (
                        <tr
                          key={id}
                          className="hover:bg-gray-100 focus-within:bg-gray-100"
                        >
                          <td className="border-t px-6 py-4 align-top">
                            {system}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {line_item_name}
                          </td>
                          <td className="border-t px-6 py-4 align-top text-right">
                            {qty}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {width} x {height}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {frame_color}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {glass_type}
                          </td>
                          {(IS_ACCOUNTING || IS_ADMIN) && (
                            <>
                              <td className="border-t px-6 py-4 align-top">
                                ${unit_price}
                              </td>
                              <td className="border-t px-6 py-4 align-top">
                                ${total_price}
                              </td>
                            </>
                          )}
                        </tr>
                    ))
                    }
                    {order.products?.length === 0 && (
                      <tr>
                        <td className="px-6 py-4 border-t" colSpan={IS_ACCOUNTING ? 8 : 6}>
                          No Products found.
                        </td>
                      </tr>
                    )}
                  </tbody>
                  {(IS_ACCOUNTING || IS_ADMIN) && (
                    <tfoot>
                      <tr>
                        <td className="border-t px-6 py-4 align-top text-right font-semibold" colSpan={7}>
                          Grand Total
                        </td>
                        <td className="border-t px-6 py-4 align-top">
                          {`$${getGrandTotal()}`}
                        </td>
                      </tr>
                    </tfoot>
                  )}
                </table>
              </div>
            </div>
          </div>
      </AuthenticatedLayout>
  )
}
