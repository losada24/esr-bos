import React, { useState, useEffect } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { rawMaterialSchema } from './RawMaterialCommon'
import RawMaterialForm from './RawMaterialForm'
import { type PageProps, type RawMaterial, type ModalProps } from '@/types'

export default function Edit ({ auth, unit_of_measurement, rawMaterial }: PageProps & { unit_of_measurement: string[], rawMaterial: RawMaterial }) {
  const [modalProps, setModalProps] = useState<ModalProps | null>(null)
  const initialValues: RawMaterial = {
    id: rawMaterial.id ?? 0,
    name: rawMaterial.name ?? '',
    qty: rawMaterial.qty ?? 0,
    unit_of_measurement: rawMaterial.unit_of_measurement ?? '',
    cost_per_unit: rawMaterial.cost_per_unit ?? 0,
    notes: rawMaterial.notes ?? '',
    featured_image: ''
  }

  useEffect(() => {
    setModalProps({
      title: rawMaterial.name,
      image: rawMaterial.featured_image
    })
  }, [])

  const handleSubmit = async (values: any, helpers: FormikHelpers<RawMaterial>) => {
    router.post(route('raw-material.update', values.id), {
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
          pageTitle='Edit Raw Material'
      >
        <Head title="Edit" />
        <Formik<RawMaterial>
          initialValues={initialValues}
          validationSchema={rawMaterialSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, setFieldValue }) => (
            <RawMaterialForm
              errors={errors}
              submitCount={submitCount}
              unit_of_measurement={unit_of_measurement}
              setFieldValue={setFieldValue}
              featured_image={rawMaterial.featured_image}
              isCreate={false}
              modalProps={modalProps}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
