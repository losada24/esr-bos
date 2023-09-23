import ReactApexChart from 'react-apexcharts'
import { type ReferralsByMonth } from '@/types'

const ReferralsByMonths = ({ referralsByMonth }: { referralsByMonth: ReferralsByMonth }) => {
  const isDark = false
  const isRtl = false

  const uniqueVisitorSeries: any = {
    series: [
      {
        name: 'Referrals',
        data: referralsByMonth.counts
      }
    ],
    options: {
      chart: {
        height: 360,
        type: 'bar',
        fontFamily: 'Nunito, sans-serif',
        toolbar: {
          show: false
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        width: 2,
        colors: ['transparent']
      },
      colors: ['#5c1ac3', '#ffbb44'],
      dropShadow: {
        enabled: true,
        blur: 3,
        color: '#515365',
        opacity: 0.4
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '55%',
          borderRadius: 8,
          borderRadiusApplication: 'end'
        }
      },
      legend: {
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '14px',
        itemMargin: {
          horizontal: 8,
          vertical: 8
        }
      },
      grid: {
        borderColor: isDark ? '#191e3a' : '#e0e6ed',
        padding: {
          left: 20,
          right: 20
        },
        xaxis: {
          lines: {
            show: false
          }
        }
      },
      xaxis: {
        categories: referralsByMonth.months,
        axisBorder: {
          show: true,
          color: isDark ? '#3b3f5c' : '#e0e6ed'
        }
      },
      yaxis: {
        tickAmount: 6,
        opposite: !!isRtl,
        labels: {
          offsetX: isRtl ? -10 : 0
        }
      },
      fill: {
        type: 'gradient',
        gradient: {
          shade: isDark ? 'dark' : 'light',
          type: 'vertical',
          shadeIntensity: 0.3,
          inverseColors: false,
          opacityFrom: 1,
          opacityTo: 0.8,
          stops: [0, 100]
        }
      },
      tooltip: {
        marker: {
          show: true
        }
      }
    }
  }

  return (
    <div className="panel h-full p-0 lg:col-span-2">
        <div className="flex items-start justify-between dark:text-white-light mb-5 p-5 border-b  border-white-light dark:border-[#1b2e4b]">
            <h5 className="font-semibold text-lg ">Referrals By Months: {referralsByMonth.year}</h5>
        </div>

        <ReactApexChart options={uniqueVisitorSeries.options} series={uniqueVisitorSeries.series} type="bar" height={360} className="overflow-hidden"/>
    </div>
  )
}

export default ReferralsByMonths
