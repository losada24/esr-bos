import { forwardRef } from 'react'

interface SelectInputProps {
  name: string
  id: string
  value: string
  className?: string
  required?: boolean
  isFocused?: boolean
  handleChange: (e: React.ChangeEvent<HTMLSelectElement>) => void
  options: Array<{ value: string, label: string }>
}

export default forwardRef(function SelectInput (
  { name, id, value, className, required, handleChange, options = [] }: SelectInputProps
) {
  return (
        <div className="flex flex-col items-start">
            <select
                name={name}
                id={id}
                value={value}
                className={
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm ' +
                    className
                }
                required={required}
                onChange={(e) => { handleChange(e) }}
            >
              {options.map((option, index) => {
                return (
                  <option key={index} value={option.value}>{option.label}</option>
                )
              })}
            </select>
        </div>
  )
})
