import React, { useState, useEffect } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { companySchema, type Company } from './CompanyCommon'
import CompanyForm from './CompanyForm'
import { type PageProps, type ModalProps } from '@/types'

export default function Profile ({ auth, states, company }: PageProps & { states: string[], company: Company }) {
  const [modalProps, setModalProps] = useState<ModalProps | null>(null)
  const initialValues: Company = {
    id: company.id ?? 0,
    name: company.name ?? '',
    address: company.address ?? '',
    city: company.city ?? '',
    state: company.state ?? '',
    zip: company.zip ?? '',
    phone_number: company.phone_number ?? '',
    featured_image: '',
    markup: company.markup ?? 0,
    promotion: company.promotion ?? 0
  }

  useEffect(() => {
    setModalProps({
      title: company.name,
      image: company.featured_image
    })
  }, [])

  const handleSubmit = async (values: any, helpers: FormikHelpers<Company>) => {
    router.post(route('company.updateProfile', values.id), {
      _method: 'PUT',
      ...values
    }, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit Company'
      >
        <Head title="Edit" />
        <Formik<Company>
          initialValues={initialValues}
          validationSchema={companySchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, setFieldValue }) => (
            <CompanyForm
              errors={errors}
              submitCount={submitCount}
              states={states}
              isCreate={false}
              setFieldValue={setFieldValue}
              featured_image={company.featured_image}
              modalProps={modalProps}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
