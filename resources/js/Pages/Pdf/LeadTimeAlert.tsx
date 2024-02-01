import { View, Text } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'
import { GLASS_TYPE } from '@/Utils/constants'

const tw = createTw({
  theme: {
    extend: {
      fontFamily: {
        regular: 'NunitoRegular',
        bold: 'NunitoBold'
      },
      colors: {
        custom: '#bada55'
      }
    }
  }
})

const LeadTimeAlert = ({ glass_type }: { glass_type: string }) => {
  const getLeadTime = () => {
    let leadTime = '2-3 WEEKS'
    if (glass_type === GLASS_TYPE.EXPRESS) {
      leadTime = '3-4 WEEKS'
    } else if (glass_type === GLASS_TYPE.REGULAR) {
      leadTime = '6-8 WEEKS'
    }

    return leadTime
  }

  return (
    <View style={tw('flex flex-row justify-between bg-gray-200 p-3 border border-gray-400')}>
      <Text style={tw('text-xs font-bold')}>ESTIMATED DELIVERY</Text>
      <Text style={tw('text-xs')}>{getLeadTime()}</Text>
    </View>
  )
}

export default LeadTimeAlert
