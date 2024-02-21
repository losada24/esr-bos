interface Fraction {
  label: string
  min: number
  max: number
}

export const FRACTION_TABLE_ARRAY: Fraction[] = [
  {
    label: '1/16',
    min: 0.001,
    max: 0.093
  },
  {
    label: '1/8',
    min: 0.094,
    max: 0.156
  },
  {
    label: '3/16',
    min: 0.157,
    max: 0.218
  },
  {
    label: '1/4',
    min: 0.219,
    max: 0.281
  },
  {
    label: '5/16',
    min: 0.282,
    max: 0.343
  },
  {
    label: '3/8',
    min: 0.344,
    max: 0.406
  },
  {
    label: '7/16',
    min: 0.407,
    max: 0.468
  },
  {
    label: '1/2',
    min: 0.469,
    max: 0.531
  },
  {
    label: '9/16',
    min: 0.532,
    max: 0.593
  },
  {
    label: '5/8',
    min: 0.594,
    max: 0.656
  },
  {
    label: '11/16',
    min: 0.657,
    max: 0.718
  },
  {
    label: '3/4',
    min: 0.719,
    max: 0.781
  },
  {
    label: '13/16',
    min: 0.782,
    max: 0.843
  },
  {
    label: '7/8',
    min: 0.844,
    max: 0.906
  },
  {
    label: '15/16',
    min: 0.907,
    max: 0.968
  }
]

export const getFraction = (value: number): string => {
  const fraction = FRACTION_TABLE_ARRAY.find(f => value >= f.min && value <= f.max)
  return fraction ? fraction.label : ''
}

export const getDecimalPortion = (value: number): number => {
  const absNumber = Math.abs(value)
  return value - Math.floor(absNumber)
}

export const getFractionFromDecimal = (value: number): string => {
  const decimalPortion = getDecimalPortion(value)
  return getFraction(decimalPortion)
}

export const getNumberWithFraction = (value: number): string => {
  const fraction = getFractionFromDecimal(value)
  if (fraction === '') {
    return `${Math.ceil(value)}`
  }

  return `${Math.trunc(value)} ${fraction}`
}

export const getNumberWithFractionAndInches = (value: number): string => {
  return `${getNumberWithFraction(value)}"`
}
