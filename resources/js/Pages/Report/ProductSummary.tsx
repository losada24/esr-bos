import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, Field, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface ProductItem {
  product_type_id: number
  product_type: string
  product_count: number
  storefront_area?: number | null
  total_filtered_orders: number
}

type ProductSummaryProps = PageProps & {

  productSummary: ProductItem[]
  startDate: string
  endDate: string
}

export default function ProductSummary({ productSummary, startDate, endDate, auth }: ProductSummaryProps) {
  return (

   <AuthenticatedLayout
             auth={auth}
            pageTitle="Summary of Products"
          >
      <Head title="Resumen de Productos" />
  <Formik
  initialValues={{
    start_date: startDate || '',
    end_date: endDate || ''
  }}
  onSubmit={(values) => {
    router.get(route('report.product-summary'), values, {
      preserveState: true
    })
  }}
>
  {({ isSubmitting, setFieldValue, values }) => (
    <Form className="mb-6 flex gap-4 items-end">
      {/* Campo Start Date */}
      <div>
        <label htmlFor="start_date" className="block mb-1 font-semibold">Start Date</label>
        <Flatpickr
          id="start_date"
          value={values.start_date}
          options={{ dateFormat: 'Y-m-d' }}
          onChange={([date]) => {
            const formatted = date.toISOString().split('T')[0]
            setFieldValue('start_date', formatted)
            // No llamamos a submitForm aquí
          }}
          className="form-input border p-2 rounded w-full"
        />
        <ErrorMessage name="start_date" component="div" className="text-red-500 text-sm mt-1" />
      </div>

      {/* Campo End Date */}
      <div>
        <label htmlFor="end_date" className="block mb-1 font-semibold">End Date</label>
        <Flatpickr
          id="end_date"
          value={values.end_date}
          options={{ dateFormat: 'Y-m-d' }}
          onChange={([date]) => {
            const formatted = date.toISOString().split('T')[0];
            setFieldValue('end_date', formatted);
            // Tampoco llamamos a submitForm aquí
          }}
          className="form-input border p-2 rounded w-full"
        />
        <ErrorMessage name="end_date" component="div" className="text-red-500 text-sm mt-1" />
      </div>

      <button
        type="submit"
        //disabled={isSubmitting}
        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        Filtrar
      </button>
    </Form>
  )}
</Formik>

        {productSummary.length > 0 && (
          <div className="mt-4 text-left font-semibold text-gray-700">
            Total Filtered Orders: {productSummary[0].total_filtered_orders}
          </div>
        )}

        <table className="w-full border border-gray-300 mt-4">
          <thead className="bg-gray-100">
            <tr>
              <th className="p-2 border">Product Type</th>
              <th className="p-2 border">Quantity</th>
              <th className="p-2 border">Storefront Area</th>
            </tr>
          </thead>
          <tbody>
            {productSummary.map((item) => (
              <tr key={item.product_type_id}>
                <td className="p-2 border">{item.product_type}</td>
                <td className="p-2 border">{item.product_count}</td>
                <td className="p-2 border">
                  {item.storefront_area ? `${item.storefront_area} ft²` : '—'}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        </AuthenticatedLayout>
  )
}
