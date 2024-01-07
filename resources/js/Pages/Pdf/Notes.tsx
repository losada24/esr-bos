import { Text, View } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'

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

export const Notes = ({ notes }: { notes: string }) => {
  return (
    <View style={tw('p-4 mt-4 border border-gray-200')}>
      <Text style={tw('text-base text-gray-900 font-bold text-right text-justify')}>Notes:</Text>
      <Text style={tw('text-xs text-gray-900 font-regular text-right text-justify')}>{notes}</Text>
    </View>
  )
}
