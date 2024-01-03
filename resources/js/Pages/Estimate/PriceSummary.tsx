import { type Order } from '@/types'
import { getSubtotal, getTaxAmount, getGrandTotal } from '@/Utils/price'

const PriceSummary = ({ estimate }: { estimate: Order }) => {
  return (
    <div className="mt-6 grid grid-cols-1 px-4 sm:grid-cols-2">
        <div>&nbsp;</div>
        <div className="space-y-2 ltr:text-right rtl:text-left">
            <div className="flex items-center">
                <div className="flex-1">Subtotal</div>
                <div className="w-[37%]">{`$${getSubtotal(estimate)}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Tax Rate</div>
                <div className="w-[37%]">{`${estimate.tax_rate}%`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Tax Amount</div>
                <div className="w-[37%]">{`$${getTaxAmount(estimate)}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Installation</div>
                <div className="w-[37%]">{`$${estimate.installation}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Permit</div>
                <div className="w-[37%]">{`$${estimate.permit}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Other</div>
                <div className="w-[37%]">{`$${estimate.other}`}</div>
            </div>
            <div className="flex items-center text-lg font-semibold">
                <div className="flex-1">Grand Total</div>
                <div className="w-[37%]">{`$${getGrandTotal(estimate)}`}</div>
            </div>
        </div>
    </div>
  )
}

export default PriceSummary
