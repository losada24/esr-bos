import { type Order } from '@/types'

const PriceSummary = ({ estimate }: { estimate: Order }) => {
  const getSubtotal = () => {
    const subtotal: number | undefined = estimate.products?.reduce((acc, product) => {
      return acc + Number(product.total_price)
    }, 0)

    return subtotal ?? 0
  }

  const subtotal = getSubtotal()
  const getTaxAmount = () => {
    const tax_race: number = estimate.tax_rate ?? 0
    const subtotal: number = getSubtotal() ?? 0
    return subtotal * tax_race / 100
  }

  const getGrandTotal = () => {
    const subtotal: number = getSubtotal() ?? 0
    const tax_amount: number = getTaxAmount() ?? 0
    const installation: number = estimate.installation ?? 0
    const permit: number = estimate.permit ?? 0
    const other: number = estimate.other ?? 0
    return Number(subtotal) + Number(tax_amount) + Number(installation) + Number(permit) + Number(other)
  }

  return (
    <div className="mt-6 grid grid-cols-1 px-4 sm:grid-cols-2">
        <div>&nbsp;</div>
        <div className="space-y-2 ltr:text-right rtl:text-left">
            <div className="flex items-center">
                <div className="flex-1">Subtotal</div>
                <div className="w-[37%]">{`$${subtotal}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Tax Rate</div>
                <div className="w-[37%]">{`${estimate.tax_rate}%`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Tax Amount</div>
                <div className="w-[37%]">{`$${getTaxAmount()}`}</div>
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
                <div className="w-[37%]">{`$${getGrandTotal()}`}</div>
            </div>
        </div>
    </div>
  )
}

export default PriceSummary
