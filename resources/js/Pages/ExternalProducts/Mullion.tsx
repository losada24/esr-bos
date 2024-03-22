import { type ExternalProductsExtrasMullion } from '@/types'

const Mullion = ({ extras }: { extras: ExternalProductsExtrasMullion }) => {
  return (
    <ul>
      <li><strong>Configuration:</strong> {extras.configuration}</li>
    </ul>
  )
}

export default Mullion
