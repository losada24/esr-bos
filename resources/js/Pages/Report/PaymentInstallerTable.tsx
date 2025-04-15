import { type Order, type InstallationPayment } from '@/types'
import { formatPrice } from '@/Utils/price'
import React from 'react'
import { PAYMENT_METHODS, SERVICES } from '@/Utils/constants'
import { log } from 'console'
import { getValueIdNotNull } from './ReportInstallerCommon'
import EditIcon from '@/Components/Icons/EditIcon'
import { Link } from '@inertiajs/react'

const PaymentInstallerTable = ({
  amount,
  values,
  order,
  payment,
  loadPaymentData
}: {
  amount: number
  values: InstallationPayment
  order: Order
  payment: InstallationPayment []
  loadPaymentData: (p: InstallationPayment) => void
}) => {
  const getGrandTotal = () => {
    // const extra_work = values.extra_work ?? 0
    // const other_cost_installer = values.other_cost_installer ?? 0
    // const extra_discount = values.extra_discount ?? 0
    const percentage = Number(values.percentage_payment) || 0
    const calculatedPayment = (amount * percentage) / 100
    // const result = Number(extra_work) + Number(other_cost_installer) - Number(extra_discount)
    const result = 0
    return calculatedPayment + Number(result)
  }
  const getPaymentProcessed = () => {
    const totalInstallerPayments = payment.reduce((acc, p) => acc + Number(p.installer_payment || 0), 0)
    const totalExtraWork = payment.reduce((acc, p) => acc + Number(p.extra_work || 0), 0)
    const totalExtraDiscount = payment.reduce((acc, p) => acc + Number(p.extra_discount || 0), 0)
    const totalOtherCost = payment.reduce((acc, p) => acc + Number(p.other_cost_installer || 0), 0)
    return totalInstallerPayments + totalExtraWork - totalExtraDiscount + totalOtherCost
  }
  const totalPayment = payment.reduce((acc, p) => {
    let total = 0
    if (p.percentage_payment > 0) {
      total = Number(p.installer_payment || 0) + Number(p.extra_work || 0) - Number(p.extra_discount || 0) + Number(p.other_cost_installer || 0)}
    return acc + total
  }, 0)
  return (
    <div className='table-responsive mt-3'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                 <th className="px-6 pt-5 pb-4">Total Project Payment</th>
                  <th className="px-6 pt-5 pb-4">Percentage Paid</th>
                  <th className="px-6 pt-5 pb-4">Payment Processed</th>
                  <th className="px-6 pt-5 pb-4">Pending Pay</th>
                  <th className="px-6 pt-5 pb-4">Extra Work Cost</th>
                  <th className="px-6 pt-5 pb-4">Discount(-)</th>
                  <th className="px-6 pt-5 pb-4">Other Cost Installtion(+)</th>
                  <th className="px-6 pt-5 pb-4">Total Payment</th>
                  <th className="px-6 pt-5 pb-4">Payment Status</th>
                  <th className="px-6 pt-5 pb-4">Date Paid</th>
                  <th className="px-6 pt-5 pb-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              {payment.map((p, index) => {
                const totalProcessedPayments = payment
                  .slice(0, index + 1)
                  .reduce((acc, curr) => acc + Number(curr.installer_payment || 0), 0)
                // Calcular el pago pendiente actual
                const pendingPayment = amount - totalProcessedPayments
                console.log(p)
                return (
                  <tr
                    key={index}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                  <td className="border-t px-6 py-4 align-top">
                  {index === 0 ? formatPrice(amount) : ''}
                   </td>
                    <td className="border-t px-6 py-4 align-top">
                      {p.percentage_payment} %
                    </td> <td className="border-t px-6 py-4 align-top">
                      {formatPrice(p.installer_payment)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {formatPrice(pendingPayment)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {formatPrice(p.extra_work)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {formatPrice(p.extra_discount)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {formatPrice(p.other_cost_installer)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {p.percentage_payment > 0 ? formatPrice(Number(p.installer_payment) + Number(p.extra_work) - Number(p.extra_discount) + Number(p.other_cost_installer)) : 0}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {p.payment_status}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {p.payment_date ? new Date(p.payment_date).toISOString().slice(0, 10) : 'Fecha no disponible'}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      <button type='button'
                        onClick={() => { loadPaymentData(p) }}
                        title='Edit Biweekly'
                         className='mr-2'
                      >
                        <EditIcon />
                      </button>
                      </td>
                  </tr>
                )
              })}
              {payment.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={7}>
                    No Payments found.
                  </td>
                </tr>
              )}
            </tbody>
                <tfoot>
                <tr className="font-bold text-left bg-gray-100">
                <td className="px-6 pt-5 pb-4 text-right" colSpan={7}>Total:</td>
                <td className="px-6 pt-5 pb-4">{formatPrice(totalPayment)}</td>
                <td colSpan={3}></td>
              </tr>
              </tfoot>
          </table>
        </div>
  )
}

export default PaymentInstallerTable
