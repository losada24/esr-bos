import React, { type PropsWithChildren } from 'react'

const Panel = ({ className, children, title }: PropsWithChildren<{
  className?: string
  title?: string
}>) => {
  return (
    <div className={`${className ?? className} panel h-full`}>
      {title && (
        <div className="flex items-center justify-between dark:text-white-light mb-5">
          <h5 className="font-semibold text-lg">{title}</h5>
        </div>
      )}
      <div>
          {children}
      </div>
  </div>
  )
}

export default Panel
