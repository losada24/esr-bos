import React from 'react'
import { Head, useForm } from '@inertiajs/react'

interface ProductItem {
  product_type_id: number
  product_type: string
  product_count: number
  total_filtered_orders: number
}

interface Props {
  productSummary: ProductItem[]
  startDate: string
  endDate: string
}

export default function ProductSummary ({ productSummary, startDate, endDate }: Props) {
  const { data, setData, get, processing, errors } = useForm({
    start_date: startDate || '',
    end_date: endDate || ''
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    get(route('report.product-summary'), { preserveState: true })
  }

  return (
    <>
      <Head title="Resumen de Productos" />
      <div className="p-6">
        <h1 className="text-xl font-bold mb-4">Resumen de Productos</h1>

        <form onSubmit={handleSubmit} className="mb-6 flex gap-4 items-end">
          <div>
            <label className="block text-sm font-medium text-gray-700">Fecha Inicio</label>
            <input
              type="date"
              value={data.start_date}
              onChange={(e) => {
                setData('start_date', e.target.value) // Usamos corchetes aquí para evitar el error
              }}
              className="border p-2 rounded"
            />
            {errors.start_date && <div className="text-red-500 text-sm">{errors.start_date}</div>}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700">Fecha Fin</label>
            <input
              type="date"
              value={data.end_date}
              onChange={(e) => {
                setData('end_date', e.target.value) // Usamos corchetes aquí para evitar el error
              }}
              className="border p-2 rounded"
            />
            {errors.end_date && <div className="text-red-500 text-sm">{errors.end_date}</div>}
          </div>

          <button
            type="submit"
            disabled={processing}
            className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          >
            Filtrar
          </button>
        </form>

        <table className="w-full border border-gray-300">
          <thead className="bg-gray-100">
            <tr>
              <th className="p-2 border">Tipo de Producto</th>
              <th className="p-2 border">Cantidad</th>
              <th className="p-2 border">Total de Órdenes</th>
            </tr>
          </thead>
          <tbody>
            {productSummary.map((item) => (
              <tr key={item.product_type_id}>
                <td className="p-2 border">{item.product_type}</td>
                <td className="p-2 border">{item.product_count}</td>
                <td className="p-2 border">{item.total_filtered_orders}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  ) }