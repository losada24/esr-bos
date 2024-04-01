import { type ExternalProductsExtrasCasement } from '@/types/interfaces/externalProducts'

const Casement = ({ extras }: { extras: ExternalProductsExtrasCasement }) => {
  return (
    <ul>
      <li><strong>Configuration:</strong> {extras.configuration}</li>
      <li><strong>Screen:</strong> {extras.screen}</li>
      <li><strong>Muntins:</strong> {extras.muntins}</li>
      <li><strong>Opening:</strong> {extras.opening}</li>
      <li><strong>Frame Type:</strong> {extras.frame_type}</li>
      <li><strong>Limit Device:</strong> {extras.limit_device}</li>
      <li><strong>Protective Film:</strong> {extras.protective_film}</li>
      <li><strong>Locking Mechanism:</strong> {extras.locking_mechanism}</li>
      <li><strong>Glass Type:</strong> {extras.glass_type}</li>
    </ul>
  )
}

export default Casement
