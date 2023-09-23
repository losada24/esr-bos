import { STATUS } from '@/Utils/constants'
import Badge from '@/Components/Badge'

const StatusBadge = ({ status }: { status: string }) => {
  const statusFound = STATUS.find((item) => item.id === status)
  if (statusFound) {
    return <Badge className={statusFound.color}>{statusFound.label}</Badge>
  }
  return <Badge className="bg-info">Status not Found</Badge>
}

export default StatusBadge
