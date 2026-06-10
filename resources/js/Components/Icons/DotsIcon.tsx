import React from 'react'

type IconProps = React.SVGProps<SVGSVGElement>

const DotsIcon: React.FC<IconProps> = ({ className, ...props }) => {
  return (
    <svg
      className={className ?? 'w-3 h-3 text-blue-600'}
      viewBox="0 0 24 24"
      fill="currentColor"
      xmlns="http://www.w3.org/2000/svg"
      {...props}
    >
      <circle cx="12" cy="12" r="7" />
    </svg>
  )
}

export default DotsIcon
