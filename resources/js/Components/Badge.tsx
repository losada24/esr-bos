import React, { type ReactNode } from 'react'

const Badge = ({ className, children }: { className?: string, children: ReactNode }) => {
  return (
    <span className={`badge ${className}`}>{ children }</span>
  )
}

export default Badge
