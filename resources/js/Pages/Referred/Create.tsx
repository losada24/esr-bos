import { useEffect, type FormEventHandler } from 'react'
import { Formik, type FormikHelpers } from 'formik'
import Checkbox from '@/Components/Checkbox'
import GuestLayout from '@/Layouts/GuestLayout'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import { Head, Link, useForm, router } from '@inertiajs/react'
import { type Referred, type PageProps } from '@/types'
import * as Yup from 'yup'

import featuredImage from '../../../assets/images/auth/contact-us.svg'
import ReferredCreateForm from './ReferredCreateForm'

export type ContactProps = PageProps & {
  userId: number
}

const phoneRegExp = /^((\\+[1-9]{1,4}[ \\-]*)|(\\([0-9]{2,3}\\)[ \\-]*)|([0-9]{2,4})[ \\-]*)*?[0-9]{3,4}?[ \\-]*[0-9]{3,4}?$/
export const referredCreateSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  phone: Yup.string().matches(phoneRegExp, 'Phone number is not valid'),
  notes: Yup.string().max(500, 'Must be 500 characters or less')
})

interface ReferredCreateFormProps {
  user_id: number
  name: string
  email: string
  phone: string
  notes: string
}

export default function Create ({ userId }: ContactProps) {
  const initialValues: ReferredCreateFormProps = {
    user_id: userId,
    name: '',
    email: '',
    phone: '',
    notes: ''
  }

  const handleSubmit = async (values: ReferredCreateFormProps, helpers: FormikHelpers<ReferredCreateFormProps>) => {
    router.post(route('referred.store'), values, {
      onSuccess: () => {
        console.log('onSuccess')
        helpers.resetForm()
      },
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
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
