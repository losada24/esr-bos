import React from 'react'

const ReorderIcon = ({ className }: { className?: string }) => {
  return (
    <svg className={className} width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M19 10H5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
      <path d="M19 14H5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
      <path d="M19 6H5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
      <path d="M19 18H5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
    </svg>
  )
}

export default ReorderIcon
