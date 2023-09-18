import React from 'react'

const UserIcon = ({ className }: { className?: string }) => {
  return (
    <svg className={`shrink-0 group-hover:!text-primary ${className}`} width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle opacity="0.5" cx="15" cy="6" r="3" fill="currentColor"></circle>
        <ellipse opacity="0.5" cx="16" cy="17" rx="5" ry="3" fill="currentColor"></ellipse>
        <circle cx="9.00098" cy="6" r="4" fill="currentColor"></circle>
        <ellipse cx="9.00098" cy="17.001" rx="7" ry="4" fill="currentColor"></ellipse>
    </svg>
  )
}

export default UserIcon
