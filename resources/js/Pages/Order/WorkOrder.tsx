import React, { Fragment, useState } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router, Link } from '@inertiajs/react'
import { type PageProps, type Order, type Client } from '@/types'
import PrintIcon from '@/Components/Icons/PrintIcon'
import Panel from '@/Components/Panel'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import FixedWindowsDrawing from '@/Pages/FixedWindows/FixedWindowsDrawing'
import FixedWindowsMeasurements from './FixedWindowsMeasurements'

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
              href={route('estimate.create')}
            >
              <PrintIcon />
              Print Work Order
            </Link>
          }
      >
          <Head title={`Work Order ${order.name}`} />
          <div className='pb-0 grid grid-cols-5 gap-6 mb-2'>
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
              <div className="">Markup</div>
              <div className="text-xs text-white-dark dark:text-gray-500">
                {order.markup} %
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
                    <td colSpan={2}><span className='font-semibold'>Mark:</span> {product.line_item_name}</td>
                    <td><span className='font-semibold'>Qty:</span> {product.qty}</td>
                    <td><span className='font-semibold'>System Product:</span> {product.system}</td>
                    <td><span className='font-semibold'>Size:</span> {product.width} x {product.height}</td>
                  </tr>
                  {/* <tr>
                    <td colSpan={4} className='text-center'>
                      <FixedWindowsDrawing width={product.width} height={product.height} />
                    </td>
                  </tr> */}
                  <FixedWindowsMeasurements product={product} />
                </Fragment>
              })}
            </tbody>
          </table>
      </AuthenticatedLayout>
  )
}
