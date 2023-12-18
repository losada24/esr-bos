import React, { Fragment } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type PageProps, type Order, type Client } from '@/types'
import PrintIcon from '@/Components/Icons/PrintIcon'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import VisualId from '@/Components/VisualId'
import MaterialConsumption from './MaterialConsumption'
import ShowCuttingList from './ShowCuttingList'

export default function WorkOrder ({ auth, order }: PageProps & {
  clients: Client[]
  order: Order
}) {
  console.log(order)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Work Order ${order.name}`}
          actions={
            <Link
              className="btn btn-primary"
              href='#'
            >
              <PrintIcon />
              Print Work Order
            </Link>
          }
      >
          <Head title={`Work Order ${order.name}`} />
          <div className='pb-0 grid grid-cols-4 gap-6 mb-2'>
            <div className='flex justify-start items-center gap-3'>
              <div className="">Quote #</div>
              <div className="text-xs text-white-dark dark:text-gray-500">{createMarkWithLeadingZero(order.id, 6)}</div>
            </div>
            <div className='flex justify-start items-center gap-3'>
              <div className="">Client</div>
              <div className="text-xs text-white-dark dark:text-gray-500">{order.client?.name}</div>
            </div>
            <div className='flex justify-start items-center gap-3'>
              <div className="">Project</div>
              <div className="text-xs text-white-dark dark:text-gray-500">
                {order.project_name ? order.project_name : 'No Project'}
              </div>
            </div>
            <div className='flex justify-start items-center gap-3'>
              <div className="">Created At</div>
              <div className="text-xs text-white-dark dark:text-gray-500">
                {order.created_at ? order.created_at.toString() : 'No Date'}
              </div>
            </div>
          </div>
          <table>
            <tbody>
              {order?.products?.map((product, index) => {
                return <Fragment key={index}>
                  <tr className='bg-gray-200'>
                    <td><span className='font-semibold'>Mark:</span> {product.line_item_name}</td>
                    <td><span className='font-semibold'>Qty:</span> {product.qty}</td>
                    <td><span className='font-semibold'>System Product:</span> {product.system}</td>
                    <td><span className='font-semibold'>Size:</span> {product.width} x {product.height}</td>
                    <td className='flex items-center justify-between'><span className='font-semibold'>Visual ID:</span> <VisualId index={index} /></td>
                  </tr>
                  <ShowCuttingList cuttingList={product?.cutting_list ?? []} productId={product.id} />
                </Fragment>
              })}
            </tbody>
          </table>
          <MaterialConsumption />
      </AuthenticatedLayout>
  )
}
