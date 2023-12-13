import React from 'react'
import { type Product } from '@/types'

const SingleHuntMeasurements = ({ product }: { product: Product }) => {
  const { width, height } = product
  const JAMB = height - 1.37
  const FRAME_HEAD = width
  const GLASS_HEIGHT = (height / 2) - (5.25 / 2) - 0.0625
  const GLASS_WIDTH = width - 4.312
  return (
    <>
      <tr className="font-bold">
        <th className="px-6 pt-5 pb-4 text-left" colSpan={2}>Part</th>
        <th className="px-6 pt-5 pb-4 text-left">Raw Material</th>
        <th className="px-6 pt-5 pb-4 text-right">Qty</th>
        <th className="px-6 pt-5 pb-4 text-right">Size</th>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Jamb</td>
        <td className="border-t px-6 py-4 align-top">103</td>
        <td className="border-t px-6 py-4 align-top text-right">
          <div className='text-right'>{product.qty * 2}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{JAMB}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Frame Head/Sill</td>
        <td className="border-t px-6 py-4 align-top">101</td>
        <td className="border-t px-6 py-4 align-top ">
          <div className='text-right'>{product.qty * 2}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{FRAME_HEAD}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Vertical Glazing Bead</td>
        <td className="border-t px-6 py-4 align-top">108</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>
            {product.qty * 2}
          </div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>
            {GLASS_HEIGHT - 0.87}
          </div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Horizontal Glazing Bead</td>
        <td className="border-t px-6 py-4 align-top">108</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{product.qty * 2}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{GLASS_WIDTH}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Glass</td>
        <td className="border-t px-6 py-4 align-top">{product.glass_type}</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{product.qty * 2}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{GLASS_WIDTH}x{GLASS_HEIGHT}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Vent Jamb</td>
        <td className="border-t px-6 py-4 align-top">107</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{product.qty * 2}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{GLASS_HEIGHT + 2.188}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Vent Bottom</td>
        <td className="border-t px-6 py-4 align-top">106</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{product.qty}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{width - 3.938}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Vent Top</td>
        <td className="border-t px-6 py-4 align-top">110</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{product.qty}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{width - 3.938}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Frame Jamb</td>
        <td className="border-t px-6 py-4 align-top">103</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{product.qty * 2}</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{height - 1.562}</div>
        </td>
      </tr>
      <tr>
        <td className="border-t px-6 py-4 align-top" colSpan={2}>Punch M.R</td>
        <td className="border-t px-6 py-4 align-top">&nbsp;</td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>&nbsp;</div>
        </td>
        <td className="border-t px-6 py-4 align-top">
          <div className='text-right'>{GLASS_HEIGHT - 0.44}</div>
        </td>
      </tr>
    </>
  )
}

export default SingleHuntMeasurements
