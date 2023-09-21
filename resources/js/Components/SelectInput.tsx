import { forwardRef, useEffect, useRef } from 'react'

export default forwardRef(function SelectInput (
  { name, id, value, className, required, isFocused, handleChange, options = [] }, ref
) {
  const input = ref ?? useRef()

  useEffect(() => {
    if (isFocused) {
      input.current.focus()
    }
  }, [])

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
                ref={input}
                required={required}
                onChange={(e) => handleChange(e)}
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
