import { type Order } from '@/types'
import { ROLES } from '@/Utils/constants'
import { formatPrice, getSubTotalPriceByRole, getTaxAmountByRole, getGrandTotalByRole, getOrderPromotion } from '@/Utils/price'
import { isAccounting, isAccountManager, isAdmin, isDealer, isSubDealer } from '@/Utils/user'

const PriceSummary = ({ estimate, roles }: { estimate: Order, roles: string[] }) => {
  const orderPromotion = getOrderPromotion(estimate)
  return (
    <div className="mt-6 grid grid-cols-1 px-4 sm:grid-cols-2">
        <div className="space-y-2">&nbsp;</div>
        <div className="space-y-2 ltr:text-right rtl:text-left">
            <div className="flex items-center">
                <div className="flex-1 text-lg font-semibold">Project Price</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Subtotal</div>
                <div className="w-[37%]">{`${formatPrice(getSubTotalPriceByRole(estimate, roles) ?? 0)}`}</div>
            </div>
            {isDealer(roles) && (
              <div className="flex items-center">
                <div className="flex-1">Dealer Subtotal</div>
                <div className="w-[37%]">{`${formatPrice(getSubTotalPriceByRole(estimate, [ROLES.SUB_DEALER]) ?? 0)}`}</div>
              </div>
            )}
            <div className="flex items-center">
                <div className="flex-1">Proposal Subtotal</div>
                <div className="w-[37%]">{`${formatPrice(getSubTotalPriceByRole(estimate, []) ?? 0)}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Tax Rate</div>
                <div className="w-[37%]">{`${estimate.tax_rate}%`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Tax Amount</div>
                <div className="w-[37%]">{`${formatPrice(getTaxAmountByRole(estimate, []) ?? 0)}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Installation</div>
                <div className="w-[37%]">{`${formatPrice(estimate.installation ?? 0)}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Permit</div>
                <div className="w-[37%]">{`${formatPrice(estimate.permit ?? 0)}`}</div>
            </div>
            <div className="flex items-center">
                <div className="flex-1">Other</div>
                <div className="w-[37%]">{`${formatPrice(estimate.other ?? 0)}`}</div>
            </div>
            {(estimate?.rg_other_price ?? 0) > 0 && (
              <div className="flex items-center">
                  <div className="flex-1">RG Other</div>
                  <div className="w-[37%]">{`${formatPrice(estimate.rg_other_price ?? 0)}`}</div>
              </div>
            )}
            {((isSubDealer(roles) || isDealer(roles) || isAdmin(roles) || isAccountManager(roles) || isAccounting(roles)) && orderPromotion !== 0) && (
              <div className="flex items-center">
                  <div className="flex-1">Order Promotion</div>
                  <div className="w-[37%]">{`${formatPrice(getOrderPromotion(estimate))}`}</div>
              </div>
            )}
            <div className="flex items-center text-lg font-semibold">
                <div className="flex-1">Grand Total</div>
                <div className="w-[37%]">{`${formatPrice(getGrandTotalByRole(estimate, []) ?? 0)}`}</div>
            </div>
        </div>
    </div>
  )
}

export default PriceSummary
