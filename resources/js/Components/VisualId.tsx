import React from 'react'
import { COLORS, SHAPES } from '@/Utils/constants'

interface VisualIDShape {
  color: string
  shape: string
}

const styles = (shape: string, color: string) => {
  const shapeStyles = {
    SQUARE: {
      height: '36px',
      width: '36px',
      backgroundColor: color
    },
    RECTANGLE: {
      height: '13px',
      width: '36px',
      backgroundColor: color
    },
    CIRCLE: {
      height: '36px',
      width: '36px',
      borderRadius: '50%',
      backgroundColor: color
    },
    TRIANGLE: {
      height: '0px',
      width: '36px',
      borderBottom: `20px solid ${color}`,
      borderLeft: '20px solid transparent',
      borderRight: '20px solid transparent'
    },
    TRAPEZOID: {
      width: '36px',
      height: '0px',
      borderRight: '10px solid transparent',
      borderLeft: '10px solid transparent',
      borderBottom: `15px solid ${color}`
    },
    PARALLELOGRAM: {
      width: '36px',
      height: '20px',
      border: `3px solid ${color}`,
      background: `${color}`,
      webkitTransform: 'skew(20deg)',
      mozTransform: 'skew(20deg)',
      msTransform: 'skew(20deg)',
      oTransform: 'skew(20deg)',
      transform: 'skew(20deg)'
    }
  }

  return shapeStyles[shape as keyof typeof shapeStyles]
}

const GetShapeInColor = ({ color, shape }: VisualIDShape) => {
  return (
    <div style={styles(shape, color)} />
  )
}

const VisualId = ({ index }: { index: number }) => {
  const cartesianProduct: VisualIDShape[] = []
  for (let i = 0; i < SHAPES.length; i++) {
    for (let j = 0; j < COLORS.length; j++) {
      cartesianProduct.push({
        shape: SHAPES[i],
        color: COLORS[j]
      })
    }
  }

  return <GetShapeInColor color={cartesianProduct[index].color} shape={cartesianProduct[index].shape} />
}

export const AllVisualIds = () => {
  const cartesianProduct: VisualIDShape[] = []
  for (let i = 0; i < SHAPES.length; i++) {
    for (let j = 0; j < COLORS.length; j++) {
      cartesianProduct.push({
        shape: SHAPES[i],
        color: COLORS[j]
      })
    }
  }

  return (
    <div className='flex flex-wrap gap-4 mt-4'>
      {cartesianProduct.map((item, index) => {
        return <GetShapeInColor key={index} color={item.color} shape={item.shape} />
      })}
    </div>
  )
}

export default VisualId
