import { type HTMLAttributes } from 'react'

export default function InputError ({
  message,
  className = '',
  ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
  return message !== undefined
    ? (
      <p {...props} className={'text-sm text-red-600 mt-1 ' + className}>
        {message}
      </p>
      )
    : null
}
