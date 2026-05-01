import { useForm } from '@inertiajs/react'
import StockMaterialForm from './StockMaterialForm'
import { type PageProps } from '@/types'

type Material = {
  id: number
  name: string
  description?: string | null
  cost?: string | number | null
  area?: string | null
  requested_date?: string | null
  quote_id?: string | null
  quote_id_received_date?: string | null
}

type Props = PageProps & {
  material: Material
  areaOptions: string[]
}

export default function Edit ({ auth, flash, material, areaOptions }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    name: material.name ?? '',
    description: material.description ?? '',
    cost: material.cost ?? '',
    area: material.area ?? '',
    requested_date: material.requested_date ?? '',
    quote_id: material.quote_id ?? '',
    quote_id_received_date: material.quote_id_received_date ?? '',
  })

  return (
    <StockMaterialForm
      auth={auth}
      flash={flash}
      title="Edit Stock Material"
      data={data}
      setData={setData}
      areaOptions={areaOptions}
      processing={processing}
      errors={errors}
      onSubmit={(event) => {
        event.preventDefault()
        put(route('stock-material.update', material.id))
      }}
    />
  )
}
