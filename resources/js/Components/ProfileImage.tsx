import { getInitials } from '@/Utils/string'

const ProfileImage = ({ name, className }: { name: string, className?: string }) => {
  return (
    <div className={`shrink-0 bg-primary w-10 h-10 rounded-md flex items-center justify-center text-white ${className}`}>
      <span>{getInitials(name)}</span>
    </div>
  )
}

export default ProfileImage
