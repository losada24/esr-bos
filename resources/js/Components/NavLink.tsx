import { Link, type InertiaLinkProps } from '@inertiajs/react'

export default function NavLink ({
  active = false,
  className = '',
  children,
  ...props
}: InertiaLinkProps & { active?: boolean }) {
  return (
    <Link
      {...props}
      className={ className }
    >
      {children}
    </Link>
  )
}
