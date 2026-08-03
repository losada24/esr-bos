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
  phaseStatuses?: Array<{
    id: number
    name: string
    position: number
    status: string
    logs: PhaseStatusLog[]
  }>
}

type PhaseStatusLog = {
  id: number
  action: string
  status: string
  notes?: string | null
  created_at_formatted: string
  user?: { name?: string | null } | null
  delivery_date?: string | null
  installation_date?: string | null
  installation_end_date?: string | null
  inspection_date?: string | null
  finish_date?: string | null
  service_date?: string | null
  pending_collect?: string | null
  final_inspection_date?: string | null
  complete_date?: string | null
  replanned_reasons?: string[] | null
}

export default function ShowStatusOrder ({ auth, orderStatuses, order, phaseStatuses = [] }: IndexOrderProps) {
  const formatReplannedReasons = (reasons?: string[] | null) => {
    if (!Array.isArray(reasons) || reasons.length === 0) return ''
    return reasons
      .map((reason) => {
        const normalized = String(reason).trim().toLowerCase()
        return normalized.charAt(0).toUpperCase() + normalized.slice(1)
      })
      .join(', ')
  }

  useEffect(() => {
    /* fetch(route('order.status.filter', { })).then(async (response) => { return await response.json() }).then((data) => {
      setStatuses(data)
    }) */
  }, [])
  console.log(orderStatuses)
  const dateForStatus = (status: string, log: PhaseStatusLog) => {
    if (['PLANNED', 'REPLANNED', 'CONFIRMED', 'DELIVERY CONFIRMED'].includes(status)) return log.delivery_date ?? ''
    return ''
  }

  const installationStartForStatus = (status: string, log: PhaseStatusLog) => {
    if (['PLANNED', 'REPLANNED', 'CONFIRMED', 'RESCHEDULED', 'RESCHEDULE', 'SUPERVISION'].includes(status)) return log.installation_date ?? ''
    return ''
  }

  const installationEndForStatus = (status: string, log: PhaseStatusLog) => {
    if (['PLANNED', 'REPLANNED', 'CONFIRMED', 'RESCHEDULED', 'RESCHEDULE', 'SUPERVISION'].includes(status)) return log.installation_end_date ?? ''
    return ''
  }

  const showPhaseHistory = Boolean(order.install_by_phases)

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

        <h3 className='mb-2 text-lg font-semibold'>General Order Status</h3>
        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Date </th>
                <th className="px-6 pt-5 pb-4">Status</th>
                <th className="px-6 pt-5 pb-4">Usuario</th>
                <th className="px-6 pt-5 pb-4">Replanned Causes</th>
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
                      {order.status === 'REPLANNED' ? formatReplannedReasons(order.replanned_reasons) : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {['PLANNED', 'REPLANNED', 'CONFIRMED', 'DELIVERY CONFIRMED'].includes(order.status) ? order.pickup_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {['PLANNED', 'REPLANNED', 'CONFIRMED', 'RESCHEDULED', 'SUPERVISION'].includes(order.status) ? order.start_date?.toString() : ''}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {['PLANNED', 'REPLANNED', 'CONFIRMED', 'RESCHEDULED', 'SUPERVISION'].includes(order.status) ? order.end_date?.toString() : ''}
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
                  <td className="px-6 py-4 border-t" colSpan={13}>
                    No Orders found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {showPhaseHistory && (
          <div className='mt-8 space-y-6'>
            <h3 className='text-lg font-semibold'>Phase Statuses</h3>
            {phaseStatuses.map((phase) => (
              <div key={phase.id}>
                <div className='mb-2 flex items-center gap-3'>
                  <h3 className='text-lg font-semibold'>{phase.name}</h3>
                  <span className='badge badge-outline-primary'>{phase.status}</span>
                </div>
                <div className='table-responsive'>
                  <table className="w-full whitespace-nowrap">
                    <thead>
                      <tr className="font-bold text-left">
                        <th className="px-6 pt-5 pb-4">Date</th>
                        <th className="px-6 pt-5 pb-4">Status</th>
                        <th className="px-6 pt-5 pb-4">Usuario</th>
                        <th className="px-6 pt-5 pb-4">Action</th>
                        <th className="px-6 pt-5 pb-4">Replanned Causes</th>
                        <th className="px-6 pt-5 pb-4">Delivery/Pickup Date</th>
                        <th className="px-6 pt-5 pb-4">Installation Date</th>
                        <th className="px-6 pt-5 pb-4">Installation End Date</th>
                        <th className="px-6 pt-5 pb-4">Inspection Date</th>
                        <th className="px-6 pt-5 pb-4">Finish Date</th>
                        <th className="px-6 pt-5 pb-4">Service Date</th>
                        <th className="px-6 pt-5 pb-4">Pending Collect</th>
                        <th className="px-6 pt-5 pb-4">Final Inspection Date</th>
                        <th className="px-6 pt-5 pb-4">Complete Date</th>
                        <th className="px-6 pt-5 pb-4">Notes</th>
                      </tr>
                    </thead>
                    <tbody>
                      {phase.logs.map((log) => (
                        <tr key={log.id} className="hover:bg-gray-100 focus-within:bg-gray-100">
                          <td className="border-t px-6 py-4 align-top">{log.created_at_formatted}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status}</td>
                          <td className="border-t px-6 py-4 align-top">{log.user?.name}</td>
                          <td className="border-t px-6 py-4 align-top">{log.action}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status === 'REPLANNED' ? formatReplannedReasons(log.replanned_reasons) : ''}</td>
                          <td className="border-t px-6 py-4 align-top">{dateForStatus(log.status, log)}</td>
                          <td className="border-t px-6 py-4 align-top">{installationStartForStatus(log.status, log)}</td>
                          <td className="border-t px-6 py-4 align-top">{installationEndForStatus(log.status, log)}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status === 'INSPECTION' ? log.inspection_date : ''}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status === 'FINISH' ? log.finish_date : ''}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status === 'SERVICE' ? log.service_date : ''}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status === 'PENDING COLLECT' ? log.pending_collect : ''}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status === 'FINAL INSPECTION' ? log.final_inspection_date : ''}</td>
                          <td className="border-t px-6 py-4 align-top">{log.status === 'COMPLETE' ? log.complete_date : ''}</td>
                          <td className="border-t px-6 py-4 align-top">{log.notes ?? ''}</td>
                        </tr>
                      ))}
                      {phase.logs.length === 0 && (
                        <tr>
                          <td className="px-6 py-4 border-t" colSpan={15}>
                            No phase history found.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            ))}
            {phaseStatuses.length === 0 && (
              <div className="px-6 py-4 border">
                No phase history found.
              </div>
            )}
          </div>
        )}
      </AuthenticatedLayout>
  )
}
