import { type PaymentInfo } from '@/types'
import { PAYMENT_METHODS, ADDRESS_REQUIRED_AFTER_AMOUNT } from '@/Utils/constants'

const PaymentInformation = ({ paymentInfo }: { paymentInfo?: PaymentInfo[] }) => {
  return (
    <>
      {paymentInfo?.length ? <div className="text-lg font-semibold ltr:sm:text-left rtl:sm:text-right text-center">Payment Information</div> : ''}
      {paymentInfo?.map((payment: PaymentInfo) => {
        return (
          <div key={payment.id} className='border border-gray-200 p-4 mb-4'>
              <table className="w-full">
                <tbody>
                  <tr>
                    <td className="font-semibold w-52">Payment Method</td>
                    <td className='text-left'>{payment.method}</td>
                  </tr>
                  <tr>
                    <td className="font-semibold w-52">Created At</td>
                    <td className='text-left'>{payment.created_at}</td>
                  </tr>
                    {(payment.method === PAYMENT_METHODS.CREDIT || payment.amount >= ADDRESS_REQUIRED_AFTER_AMOUNT) && (
                      <>
                        <tr>
                          <td className="font-semibold">Address</td>
                          <td>{payment.street_address}</td>
                        </tr>
                        <tr>
                          <td className="font-semibold">City</td>
                          <td>{payment.city}</td>
                        </tr>
                        <tr>
                          <td className="font-semibold">State</td>
                          <td>{payment.state}</td>
                        </tr>
                        <tr>
                          <td className="font-semibold">Zip Code</td>
                          <td>{payment.state}</td>
                        </tr>
                        <tr>
                          <td className="font-semibold">Notes</td>
                          <td>{payment.notes}</td>
                        </tr>
                      </>
                    )}
                </tbody>
              </table>
          </div>
        )
      })}
    </>
  )
}

export default PaymentInformation
