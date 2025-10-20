import React from 'react'

type IconProps = React.SVGProps<SVGSVGElement>

const ExportIcon: React.FC<IconProps> = ({ className, width = 24, height = 24, ...props }) => {
  return (
    <svg
      className={className}
      width={width}
      height={height}
      viewBox='0 0 24 24'
      fill='none'
      xmlns='http://www.w3.org/2000/svg'
      {...props}
    >
      <path d='M12 20C7.58172 20 4 16.4183 4 12M20 12C20 14.5264 18.8289 16.7792 17 18.2454' stroke='#1C274C' strokeWidth='1.5' strokeLinecap='round' />
      <path d='M12 14L12 4M12 4L15 7M12 4L9 7' stroke='#1C274C' strokeWidth='1.5' strokeLinecap='round' strokeLinejoin='round' />
    </svg>
  )
}

export default ExportIcon
