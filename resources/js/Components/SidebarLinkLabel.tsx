import { type PropsWithChildren } from 'react'

const SidebarLinkLabel = ({ children }: PropsWithChildren) => {
  return (
    <span className='ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark'>{ children }</span>
  )
}

export default SidebarLinkLabel
