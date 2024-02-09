import { type OrdersByStatusCount } from './Index'
import { STATUS } from '@/Utils/constants'
import ReactApexChart from 'react-apexcharts'

interface ReferralsByStatusGraph {
  series: number[]
  labels: string[]
  colors: string[]
}

const OrdersByStatus = ({ ordersByStatus }: { ordersByStatus: OrdersByStatusCount[] }) => {
  let total: number = 0
  const referralsByStatusGraph: ReferralsByStatusGraph = {
    series: [],
    labels: [],
    colors: []
  }

  ordersByStatus.forEach((status) => {
    const selectedStatus = STATUS.find((s) => s.id === status.status)
    if (selectedStatus) {
      total += status.count
      referralsByStatusGraph.series.push(status.count)
      referralsByStatusGraph.labels.push(selectedStatus.label)
      referralsByStatusGraph.colors.push(selectedStatus?.hex ?? '007bff')
    }
  })

  const pieChart: any = {
    series: referralsByStatusGraph.series,
    options: {
      chart: {
        height: 300,
        type: 'pie',
        zoom: {
          enabled: false
        },
        toolbar: {
          show: false
        }
      },
      labels: referralsByStatusGraph.labels,
      colors: referralsByStatusGraph.colors,
      responsive: [
        {
          breakpoint: 480,
          options: {
            chart: {
              width: 200
            }
          }
        }
      ],
      stroke: {
        show: false
      },
      legend: {
        position: 'bottom'
      }
    }
  }

  return (
    <div className="panel h-full">
      <div className="flex items-start justify-between dark:text-white-light mb-5 -mx-5 p-5 pt-0 border-b  border-white-light dark:border-[#1b2e4b]">
          <h5 className="font-semibold text-lg ">Orders By Status</h5>
      </div>

      <ReactApexChart series={pieChart.series} options={pieChart.options} className="rounded-lg bg-white dark:bg-black overflow-hidden" type="pie" height={300} />
      <p className='text-lg'><span className='font-semibold'>Total:</span>&nbsp;{total}</p>
    </div>
  )
}

export default OrdersByStatus
