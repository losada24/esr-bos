const ProfileImage = ({ name, className }: { name: string, className?: string }) => {
  const getInitials = (name: string): string => {
    const nameArray = name.split(' ')
    let initials = name
    if (nameArray.length > 1) {
      initials = nameArray[0].charAt(0) + nameArray[1].charAt(0)
    } else if (name.length > 1) {
      initials = name.charAt(0) + name.charAt(1)
    }

    return initials
  }

  return (
    <div className={`shrink-0 bg-primary w-10 h-10 rounded-md flex items-center justify-center text-white ${className}`}>
      <span>{getInitials(name)}</span>
    </div>
  )
}

export default ProfileImage
