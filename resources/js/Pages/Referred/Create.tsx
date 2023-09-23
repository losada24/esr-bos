import { Formik, type FormikHelpers } from 'formik'
import GuestLayout from '@/Layouts/GuestLayout'
import { Head, router } from '@inertiajs/react'
import { type PageProps } from '@/types'
import * as Yup from 'yup'
import featuredImage from '../../../assets/images/auth/contact-us.svg'
import ReferredCreateForm from './ReferredCreateForm'
import { PHONE_REG_EXP, RECAPTCHA_SITE_KEY } from '@/Utils/constants'

export type ContactProps = PageProps & {
  userId: number
}

export const referredCreateSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  phone: Yup.string().matches(PHONE_REG_EXP, 'Phone number is not valid'),
  notes: Yup.string().max(500, 'Must be 500 characters or less')
})

export interface ReferredCreateFormProps {
  user_id: number
  name: string
  email: string
  phone: string
  notes: string
  captcha_token: string
}

export default function Create ({ userId }: ContactProps) {
  const initialValues: ReferredCreateFormProps = {
    user_id: userId,
    name: '',
    email: '',
    phone: '',
    notes: '',
    captcha_token: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<ReferredCreateFormProps>) => {
    grecaptcha.ready(function () {
      grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'submit' }).then(function (token: string) {
        router.post(route('referred.store'), { ...values, captcha_token: token }, {
          onSuccess: () => {
            helpers.resetForm()
          },
          onError: (errors: any) => {
            helpers.setErrors(errors)
          }
        })
      })
    })
  }

  return (
    <GuestLayout
      featuredImage={featuredImage}
      formTitle='Contact Us'
      formDescription='Share the requested information below and become a valued member of the Reylos Glass family. Join us in making homes safer and more beautiful, one referral at a time!'
    >
      <Head title="Contact Us" />
      <Formik<ReferredCreateFormProps>
        initialValues={initialValues}
        validationSchema={referredCreateSchema}
        onSubmit={handleSubmit}
      >
        {({ errors, submitCount }) => (
          <ReferredCreateForm
            errors={errors}
            submitCount={submitCount}
          />
        )}
      </Formik>
    </GuestLayout>
  )
}
