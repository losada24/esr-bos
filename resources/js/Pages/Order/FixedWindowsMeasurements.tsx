import React from 'react'
import { type Product } from '@/types'

const JAMB_COLOR: string = '#991b1b'
const FRAME_HEAD_COLOR: string = '#9a3412'
const VERTICAL_GLASSING_BEAD_COLOR: string = '#3f6212'
const HORIZONTAL_GLASSING_BEAD_COLOR: string = '#1e40af'

const FixedWindowsMeasurements = ({ product }: { product: Product }) => {
  const { width, height } = product
  const JAMB = height - 1.37
  const FRAME_HEAD = width
  const GLASS_HEIGHT = height - 1.875
  const GLASS_WIDTH = width - 4.312
  return (
    <>
      <tr className="font-bold">
        <th className="px-6 pt-5 pb-4 text-left">Part</th>
        <th className="px-6 pt-5 pb-4 text-left">Raw Material</th>
        <th className="px-6 pt-5 pb-4 text-left">Qty</th>
        <th className="px-6 pt-5 pb-4 text-left">Size</th>
        <th className="px-6 pt-5 pb-4 text-left">COLOR</th>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top">Jamb</td>
        <td className="border-t px-6 py-4 align-top">103</td>
        <td className="border-t px-6 py-4 align-top text-left">{product.qty * 2}</td>
        <td className="border-t px-6 py-4 align-top text-left">{JAMB}</td>
        <td className="border-t px-6 py-4 align-top text-left">
          <div className='w-12 h-12 rounded-full' style={{ backgroundColor: JAMB_COLOR }} />
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top">Frame Head/Sill</td>
        <td className="border-t px-6 py-4 align-top">101</td>
        <td className="border-t px-6 py-4 align-top text-left">{product.qty * 2}</td>
        <td className="border-t px-6 py-4 align-top text-left">{FRAME_HEAD}</td>
        <td className="border-t px-6 py-4 align-top text-left">
          <div className='w-5 h-5 rounded-full' style={{ backgroundColor: FRAME_HEAD_COLOR }} />
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top">Vertical Glazing Bead</td>
        <td className="border-t px-6 py-4 align-top">108</td>
        <td className="border-t px-6 py-4 align-top text-left">{product.qty * 2}</td>
        <td className="border-t px-6 py-4 align-top text-left">{GLASS_HEIGHT - 0.87}</td>
        <td className="border-t px-6 py-4 align-top text-left">
          <div className='w-5 h-5 rounded-full' style={{ backgroundColor: VERTICAL_GLASSING_BEAD_COLOR }} />
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top">Horizontal Glazing Bead</td>
        <td className="border-t px-6 py-4 align-top">108</td>
        <td className="border-t px-6 py-4 align-top text-left">{product.qty * 2}</td>
        <td className="border-t px-6 py-4 align-top text-left">{GLASS_WIDTH}</td>
        <td className="border-t px-6 py-4 align-top text-left">
          <div className='w-5 h-5 rounded-full' style={{ backgroundColor: HORIZONTAL_GLASSING_BEAD_COLOR }} />
        </td>
      </tr>
    </>
  )
}

export default FixedWindowsMeasurements
