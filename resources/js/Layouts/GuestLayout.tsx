import { type PropsWithChildren } from 'react'

import bgGradientImage from '../../assets/images/auth/bg-gradient.png'
import comingSoonObject1 from '../../assets/images/auth/coming-soon-object1.png'
import comingSoonObject2 from '../../assets/images/auth/coming-soon-object2.png'
import comingSoonObject3 from '../../assets/images/auth/coming-soon-object3.png'
import polygonObject from '../../assets/images/auth/polygon-object.svg'
import logoWhite from '../../assets/images/vector-reylosglass.svg'
import logo from '../../assets/images/logo-reylosglass.png'
import map from '../../assets/images/auth/map.png'
import FlashMessages from '@/Components/FlashMessages'

const APP_NAME = import.meta.env.VITE_COMPANY_NAME
const CUSTOM_TAILWIND_CLASS = {
  bgMap: `bg-[url(${map})]`
}

type PropsWithChildrenAndImage = {
  featuredImage?: string
  formTitle: string
  formDescription: string
} & PropsWithChildren

export default function Guest ({
  children,
  featuredImage,
  formTitle,
  formDescription
}: PropsWithChildrenAndImage) {
  return (
    <div className="relative overflow-x-hidden font-nunito text-sm font-normal antialiased">
      <div className="main-container min-h-screen text-black dark:text-white-dark">
        <div>
          <div className="absolute inset-0">
            <img
              src={bgGradientImage}
              alt="image"
              className="h-full w-full object-cover"
            />
          </div>
          <div
            className={`relative flex min-h-screen items-center justify-center ${CUSTOM_TAILWIND_CLASS.bgMap} bg-cover bg-center bg-no-repeat px-6 py-10 dark:bg-[#060818] sm:px-16`}
          >
            <img
              src={comingSoonObject1}
              alt="image"
              className="absolute left-0 top-1/2 h-full max-h-[893px] -translate-y-1/2"
            />
            <img
              src={comingSoonObject2}
              alt="image"
              className="absolute left-24 top-0 h-40 md:left-[30%]"
            />
            <img
              src={comingSoonObject3}
              alt="image"
              className="absolute right-0 top-0 h-[300px]"
            />
            <img
              src={polygonObject}
              alt="image"
              className="absolute bottom-0 end-[28%]"
            />
            <div className="relative flex w-full max-w-[1502px] flex-col justify-between overflow-hidden rounded-md bg-white/60 backdrop-blur-lg dark:bg-black/50 lg:min-h-[758px] lg:flex-row lg:gap-10 xl:gap-0">
              <div className="relative hidden w-full items-center justify-center bg-[linear-gradient(225deg,rgba(239,18,98,1)_0%,rgba(67,97,238,1)_100%)] p-5 lg:inline-flex lg:max-w-[835px] xl:-ms-28 ltr:xl:skew-x-[14deg] rtl:xl:skew-x-[-14deg]">
                <div className="absolute inset-y-0 w-8 from-primary/10 via-transparent to-transparent ltr:-right-10 ltr:bg-gradient-to-r rtl:-left-10 rtl:bg-gradient-to-l xl:w-16 ltr:xl:-right-20 rtl:xl:-left-20"></div>
                <div className="ltr:xl:-skew-x-[14deg] rtl:xl:skew-x-[14deg]">
                  <a href={route('dashboard')} className="w-48 block lg:w-72 ms-10">
                    <img src={logoWhite} alt="Logo" className="w-full" />
                  </a>
                  {featuredImage != null && (
                    <div className="mt-24 hidden w-full max-w-[430px] lg:block">
                      <img
                        src={featuredImage}
                        alt="Featured Image"
                        className="w-full"
                      />
                    </div>
                  )}
                </div>
              </div>
              <div className="relative flex w-full flex-col items-center justify-center gap-6 px-4 pb-16 pt-6 sm:px-6 lg:max-w-[667px]">
                <div className="flex w-full max-w-[440px] items-center gap-2 lg:absolute lg:end-6 lg:top-6 lg:max-w-full">
                  <a href={route('dashboard')} className="w-8 block lg:hidden">
                    <img src={logo} alt="Logo" className="mx-auto w-10" />
                  </a>
                </div>
                <div className="w-full max-w-[440px] lg:mt-16">
                  <div className="mb-10">
                    <h1 className="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">
                      {formTitle}
                    </h1>
                    <p className="text-base font-bold leading-normal text-white-dark">
                      {formDescription}
                    </p>
                  </div>
                  <FlashMessages />
                  {children}
                </div>
                <p className="absolute bottom-6 w-full text-center dark:text-white">
                  © {new Date().getFullYear()}. {APP_NAME} All Rights Reserved.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
