import React from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page, Text, View, Image } from '@react-pdf/renderer'
import { type PageProps, type Order } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'
import PrintButton from '@/Pages/Order/PrintButton'
import POHeaders from './POHeaders'
import VisualId from './VisualId'
import Pagination from './Pagination'

import { PRODUCT_SYSTEMS } from '@/Utils/constants'
import cut103fw from '../../../assets/images/cut103fw.jpg'
import cut103sh from '../../../assets/images/cut103sh.jpg'
import cut111hroxxo from '../../../assets/images/cut111hrox-xo.jpg'

const FW_MATERIAL_WITH_IMAGES = ['VW 103 W', 'VW 103 BR']
const SH_MATERIAL_WITH_IMAGES = ['VW 103 W', 'VW 103 BR']
const HR_OXXO_MATERIAL_WITH_IMAGES = ['VW 111 W', 'VW 111 BR']

type IndexOrderProps = PageProps & {
  order: Order
}

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

interface CuttingListProps {
  material: string
  frame_color: string
  qty: number
  width: number
  height: number
  size: string
  part: string
  visual_id: number
  line_item_name: string
  system: string
}

const CuttingList = ({ order, auth }: IndexOrderProps) => {
  const orderCuttingList: CuttingListProps[] = []
  order?.orderCuttingList?.forEach((product) => {
    product.items.forEach((item) => {
      orderCuttingList.push({
        material: product.material,
        frame_color: item.frame_color,
        qty: item.qty,
        width: item.width,
        height: item.height,
        size: item.size,
        part: item.part,
        visual_id: item.visual_id,
        line_item_name: item.line_item_name,
        system: item.system
      })
    })
  })

  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle={`Cutting List ${order.name}`}
          actions={
            <PrintButton id={order.id} />
          }
      >
        <Head title={`Cutting List ${order.name}`} />
        <PrintLayout>
          <Page wrap size="LETTER" style={tw('p-6 font-regular')}>
            <Pagination />
            <POHeaders order={order} documentTitle='Cutting List' />
            <View style={tw('flex flex-row gap-4 justify-between border-b border-gray-200 mb-3 p-3')}>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12')}>Raw Material</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-3/12')}>Part</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-2/12')}>Mark</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-1/12 text-right')}>Qty</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-2/12 text-right')}>Size</Text>
              <Text style={tw('text-xs text-white-dark text-black font-bold w-1/12')}>Visual Id</Text>
            </View>
            {orderCuttingList.map((product, index) => {
              return <View style={tw('flex flex-row gap-4 items-center justify-between border-b border-gray-200 mb-3 p-3')} key={index}>
                <Text style={tw('text-xs text-gray-900 font-regular w-3/12')}>{product.material} ({product.frame_color})</Text>
                <View style={tw('w-3/12')}>
                  <View style={tw('flex flex-col')}>
                    <Text style={tw('text-xs text-gray-900 font-regular')}>
                      {product.part}
                    </Text>
                    {product.system === PRODUCT_SYSTEMS.FIXED_WINDOWS && FW_MATERIAL_WITH_IMAGES.findIndex(x => x === product.material) !== -1 && <Image src={cut103fw} style={tw('w-10 h-10')} />}
                    {product.system === PRODUCT_SYSTEMS.SINGLE_HUNG && SH_MATERIAL_WITH_IMAGES.findIndex(x => x === product.material) !== -1 && <Image src={cut103sh} style={tw('w-10 h-10')} />}
                    {product.system === PRODUCT_SYSTEMS.HORIZONTAL_ROLLER && HR_OXXO_MATERIAL_WITH_IMAGES.findIndex(x => x === product.material) !== -1 && <Image src={cut111hroxxo} style={tw('w-10 h-10')} />}
                  </View>
                </View>
                <Text style={tw('text-xs text-gray-900 font-regular w-2/12')}>{product.line_item_name}</Text>
                <Text style={tw('text-xs text-gray-900 font-regular w-1/12 text-right')}>{product.qty}</Text>
                <Text style={tw('text-xs text-gray-900 font-regular w-2/12 text-right')}>{product.size}</Text>
                <View style={tw('w-1/12')}>
                  <VisualId index={product.visual_id} />
                </View>
              </View>
            })}
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default CuttingList
