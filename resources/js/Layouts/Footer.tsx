import { COMPANY_NAME } from '@/Utils/constants'

const Footer = () => {
  return <div className="dark:text-white-dark text-center ltr:sm:text-left rtl:sm:text-right p-6 mt-auto">© {new Date().getFullYear()}. {COMPANY_NAME} All rights reserved.</div>
}

export default Footer
