import React, { useEffect } from 'react'
import { type Product } from '@/types'
import { getNumberWithFraction } from '@/Utils/numbers'
import { useStore } from '@/Store/materialSummary'
import { type ProductOrderFields } from './OrderCommon'
import { SQFT, FOOT, UNIT } from '@/Utils/constants'

const FixedWindowsMeasurements = ({ product }: { product: Product }) => {
  const store = useStore()
  const { width, height } = product
  const JAMB = height - 1.37
  const FRAME_HEAD = width
  const GLASS_HEIGHT = parseFloat((height - 1.875).toFixed(3))
  const GLASS_WIDTH = parseFloat((width - 4.312 - 0.065).toFixed(3))
  const GLAZING_BEAD_VERTICAL = parseFloat((GLASS_HEIGHT - 0.87 + 0.3775).toFixed(3))
  const GLAZING_BEAD_HORIZONTAL = parseFloat((GLASS_WIDTH + 0.1875).toFixed(3))
  const T_SLOT_SEAL_GLAZING_BEAT = parseFloat(((GLAZING_BEAD_VERTICAL * 2) + (GLAZING_BEAD_HORIZONTAL * 2)).toFixed(3))

  const products: ProductOrderFields[] = [
    {
      part: `${GLASS_WIDTH}x${GLASS_HEIGHT}`,
      rawMaterial: product.glass_type,
      qty: product.qty,
      size: GLASS_WIDTH * GLASS_HEIGHT,
      unit: SQFT
    },
    {
      part: 'Jamb',
      rawMaterial: '103',
      qty: product.qty * 2,
      size: JAMB,
      unit: FOOT
    },
    {
      part: 'Frame Head/Sill',
      rawMaterial: '101',
      qty: product.qty * 2,
      size: FRAME_HEAD,
      unit: FOOT
    },
    {
      part: 'Vertical Glazing Bead',
      rawMaterial: '108',
      qty: product.qty * 2,
      size: GLAZING_BEAD_VERTICAL,
      unit: FOOT
    },
    {
      part: 'Horizontal Glazing Bead',
      rawMaterial: '108',
      qty: product.qty * 2,
      size: GLAZING_BEAD_HORIZONTAL,
      unit: FOOT
    },
    {
      part: 'Screw Cover',
      rawMaterial: `SC 001 ${product.frame_color.charAt(0).toUpperCase()}`,
      qty: product.qty * 2,
      size: GLASS_WIDTH,
      unit: FOOT
    },
    {
      part: 'T Slot Seal Glazing Beat 7/16',
      rawMaterial: 'TSG 0003',
      qty: product.qty,
      size: T_SLOT_SEAL_GLAZING_BEAT,
      unit: FOOT
    },
    {
      part: 'Stop Sash',
      rawMaterial: `STS 0001 ${product.frame_color.charAt(0).toUpperCase()}`,
      qty: product.qty,
      size: GLASS_HEIGHT,
      unit: FOOT
    },
    {
      part: 'Setting Block',
      rawMaterial: 'NE850062',
      qty: 8 * product.qty,
      size: 0,
      unit: UNIT
    },
    {
      part: 'Screws',
      rawMaterial: 'Screws 8x1',
      qty: 12 * product.qty,
      size: 0,
      unit: UNIT
    }
  ]

  useEffect(() => {
    products.forEach((productOrderField) => {
      store.addMaterial({
        material: productOrderField.rawMaterial,
        quantity: productOrderField.qty ?? 0,
        size: productOrderField.size,
        unit: productOrderField.unit,
        part: productOrderField.part
      })
    })
  }, [])

  return (
    <>
      <tr className="font-bold">
        <th className="px-6 pt-5 pb-4 text-left" colSpan={2}>Part</th>
        <th className="px-6 pt-5 pb-4 text-left">Raw Material</th>
        <th className="px-6 pt-5 pb-4 text-right">Qty</th>
        <th className="px-6 pt-5 pb-4 text-right">Size</th>
      </tr>
      {products.map((productOrderField, index) => {
        return <tr key={`${product.id}_${index}`}>
          {productOrderField.unit === SQFT
            ? <>
                <td className="border-t px-6 py-4 align-top" colSpan={2}>Glass</td>
                <td className="border-t px-6 py-4 align-top">{productOrderField.rawMaterial}</td>
                <td className="border-t px-6 py-4 align-top">
                  <div className='text-right'>{productOrderField.qty}</div>
                </td>
                <td className="border-t px-6 py-4 align-top">
                  <div className='text-right'>{productOrderField.part}</div>
                </td>
              </>
            : <>
                <td className="border-t px-6 py-4 align-top" colSpan={2}>{productOrderField.part}</td>
                <td className="border-t px-6 py-4 align-top">{productOrderField.rawMaterial}</td>
                <td className="border-t px-6 py-4 align-top">
                  <div className='text-right'>{productOrderField.qty}</div>
                </td>
                <td className="border-t px-6 py-4 align-top">
                  <div className='text-right'>{getNumberWithFraction(productOrderField.size ?? 0)}</div>
                </td>
              </>
          }
        </tr>
      })}
    </>
  )
}

export default FixedWindowsMeasurements
