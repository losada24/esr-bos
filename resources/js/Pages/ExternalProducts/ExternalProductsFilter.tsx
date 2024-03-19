import { type SyntheticEvent } from 'react'
import { useForm, router } from '@inertiajs/react'
import TextInput from '@/Components/TextInput'
import PrimaryButton from '@/Components/PrimaryButton'

const ExternalProductsFilter = () => {
  const { data, setData } = useForm({
    text: ''
  })

  const reset = () => {
    setData({
      text: ''
    })

    router.get(route('external-products.index'), {
      text: ''
    }, {
      replace: true,
      preserveState: false
    })
  }

  const submit = (e: SyntheticEvent) => {
    e.preventDefault()
    let currentRoute = route().current()
    if (currentRoute === undefined) {
      currentRoute = 'external-products.index'
    }

    router.get(route(currentRoute), data, {
      replace: true,
      preserveState: true
    })
  }

  return (
    <form onSubmit={submit}>
      <div className='flex flex-row gap-3 grow'>
        <div className='mb-3 w-64'>
        <label htmlFor="role">Search</label>
          <TextInput
            id="text"
            name="text"
            value={data.text}
            className="form-input"
            onChange={(e) => {
              setData('text', e.target.value)
            }}
            type='text'
            placeholder='Search by Name'
          />
        </div>
        <div className="flex items-end justify-between w-44 pb-3">
          <PrimaryButton className="btn btn-primary">
            Filter
          </PrimaryButton>
          <button
            onClick={reset}
            className="btn btn-outline-primary"
            type="button"
          >
            Reset
          </button>
        </div>
      </div>
    </form>
  )
}

export default ExternalProductsFilter
