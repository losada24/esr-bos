import { type PropsWithChildren } from 'react'

const Alert = ({ children, className }: PropsWithChildren<{
  className?: string
}>) => {
  return (
    <div className={className}>
      <span className="ltr:pr-2 rtl:pl-2">
        {children}
      </span>
    </div>
  )
}

export default Alert
