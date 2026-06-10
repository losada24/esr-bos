import { useForm } from '@inertiajs/react'
import StockMaterialForm from './StockMaterialForm'
import { type PageProps } from '@/types'

type Props = PageProps & {
  areaOptions: string[]
}

export default function Create ({ auth, flash, areaOptions }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    description: '',
    cost: '',
    area: 'Stock',
    requested_date: '',
    quote_id: '',
    quote_id_received_date: '',
  })

  return (
    <StockMaterialForm
      auth={auth}
      flash={flash}
      title="Create Stock Material"
      data={data}
      setData={setData}
      areaOptions={areaOptions}
      processing={processing}
      errors={errors}
      onSubmit={(event) => {
        event.preventDefault()
        post(route('stock-material.store'))
      }}
    />
  )
}
