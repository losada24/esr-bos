
import { type Order, type InstallationPayment } from '@/types'
import { formatPrice } from '@/Utils/price'
import React from 'react'
import { PAYMENT_METHODS, SERVICES } from '@/Utils/constants'
import { log } from 'console'
import { getValueIdNotNull } from './ReportInstallerCommon'

const PaymentInstallerTable = ({
  amount,
  values,
  order,
  payment

}: {
  amount: number
  values: InstallationPayment
  order: Order
  payment: InstallationPayment []
}) => {
  const getGrandTotal = () => {
    const extra_work = order.payment_extra_fields?.extra_work ?? 0
    const other_cost_installer = order.payment_extra_fields?.other_cost_installer ?? 0
    const extra_discount = order.payment_extra_fields?.extra_discount ?? 0
    const result = Number(extra_work) + Number(other_cost_installer) - Number(extra_discount)
    return amount + Number(result)
  }
  const getPaymentProcessed = () => {
    const totalInstallerPayments = payment.reduce((acc, p) => acc + Number(p.installer_payment || 0), 0)
    return totalInstallerPayments
  }


  return (
    <div className='table-responsive mt-3'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                  <th className="px-6 pt-5 pb-4">Paid</th>
                  <th className="px-6 pt-5 pb-4">Percentage Paid</th>
                  <th className="px-6 pt-5 pb-4">Date Paid</th>
              </tr>
            </thead>
            <tbody>
              {payment.map((p, index) => {
                return (
                  <tr
                    key={index}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {formatPrice(p.installer_payment)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {p.percentage_payment} %
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {p.payment_date ? new Date(p.payment_date).toISOString().slice(0, 10) : 'Fecha no disponible'}
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
                <tr>
                    <td colSpan={2} className="px-6 py-4 align-top text-right">Total</td>
                    <td className='px-6 py-4 align-top text-left'>{formatPrice(getGrandTotal())}</td>
                    <td>&nbsp;</td>
                </tr>
              <tr>
                    <td colSpan={2} className="px-6 py-4 align-top text-right">Payment Processed</td>
                    <td className='px-6 py-4 align-top text-left'>{formatPrice(getPaymentProcessed())}</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colSpan={2} className="px-6 py-4 align-top text-right">Pending Pay</td>
                    <td className='px-6 py-4 align-top text-left'>{formatPrice(getGrandTotal() - getPaymentProcessed())}</td>
                    <td>&nbsp;</td>
                </tr>
              </tfoot>
          </table>
        </div>
  )
}

export default PaymentInstallerTable
