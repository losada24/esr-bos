import { type Product } from '@/types'
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

const Muntin = ({ product }: { product: Product }) => {
  return (
    <View style={tw('flex flex-col')}>
      <View style={tw('flex flex-col bg-gray-200')}>
        <View style={tw('flex flex-row p-3 justify-start')}>
          <Text style={tw('text-xs font-bold w-2/12')}>
            Muntin Panels
          </Text>
          <Text style={tw('text-xs font-bold w-2/12')}>
            Muntin Pattern
          </Text>
          <Text style={tw('text-xs font-bold w-1/12')}>
            Panels
          </Text>
          <Text style={tw('text-xs font-bold w-1/12')}>
            Lines
          </Text>
          <Text style={tw('text-xs font-bold w-3/12')}>
            Muntin Interior Style
          </Text>
          <Text style={tw('text-xs font-bold w-3/12')}>
            Muntin Exterior Style
          </Text>
        </View>
      </View>
      <View style={tw('flex flex-col')}>
        <View style={tw('flex flex-row p-3 justify-start')}>
          <Text style={tw('text-xs font-regular w-2/12')}>
            Yes
          </Text>
          <Text style={tw('text-xs font-regular w-2/12')}>
            {product.extras?.muntin_pattern}
          </Text>
          <View style={tw('flex flex-col w-1/12')}>
            <Text style={tw('text-xs font-regular')}>{product.extras?.panel_a && 'Panel A'}</Text>
            <Text style={tw('text-xs font-regular')}>{product.extras?.panel_b && 'Panel B'}</Text>
          </View>
          <Text style={tw('text-xs font-regular w-1/12')}>
            {product.extras?.horizontal_lines} x {product.extras?.vertical_lines}
          </Text>
          <Text style={tw('text-xs font-regular w-3/12')}>
            {product.extras?.muntin_interior_style}
          </Text>
          <Text style={tw('text-xs font-regular w-3/12')}>
            {product.extras?.muntin_exterior_style}
          </Text>
        </View>
      </View>
    </View>
  )
}

export default Muntin
