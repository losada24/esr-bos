import { type HTMLAttributes } from 'react'

export default function InputError ({
  message,
  className = '',
  ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string | null }) {
  return message != null
    ? (
      <p {...props} className={'text-sm text-danger mt-1 ' + className}>
        {message}
      </p>
      )
    : null
}
