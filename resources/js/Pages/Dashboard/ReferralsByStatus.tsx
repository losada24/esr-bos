import PerfectScrollbar from 'react-perfect-scrollbar'
import { type ReferralsByStatusCount } from '@/types'
import { STATUS } from '@/Utils/constants'

const ReferalStatus = ({ status }: { status: ReferralsByStatusCount }) => {
  const selectedStatus = STATUS.find((s) => s.id === status.status)
  if (selectedStatus) {
    return (
      <div className="flex items-center">
          <div className="shrink-0 ltr:mr-2 rtl:ml-2 relative z-10">
              <div className={`${selectedStatus.color} w-8 h-8 rounded-full flex items-center justify-center text-white`}>
                <p className="text-white text-xs">{status.count}</p>
              </div>
          </div>
          <div>
              <h5 className="font-semibold dark:text-white-light">{selectedStatus.label}</h5>
          </div>
      </div>
    )
  }

  return (
    <>Status Not Found</>
  )
}

const ReferralsByStatus = ({ referralsByStatus }: { referralsByStatus: ReferralsByStatusCount[] }) => {
  return (
    <div className="panel h-full">
      <div className="flex items-start justify-between dark:text-white-light mb-5 -mx-5 p-5 pt-0 border-b  border-white-light dark:border-[#1b2e4b]">
          <h5 className="font-semibold text-lg ">Referrals By Status</h5>
      </div>
      <PerfectScrollbar className="perfect-scrollbar relative h-[360px] ltr:pr-3 rtl:pl-3 ltr:-mr-3 rtl:-ml-3">
          <div className="space-y-7">
            {referralsByStatus.map((status, index) => {
              return (
                <ReferalStatus key={`status_${index}`} status={status} />
              )
            })}
          </div>
      </PerfectScrollbar>
    </div>
  )
}

export default ReferralsByStatus
