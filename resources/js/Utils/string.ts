export const ellipse = (str: string, length: number) => {
  if (str.length > length) {
    return `${str.substring(0, length)}...`
  }
  return str
}

export const getInitials = (name: string): string => {
  const nameArray = name.split(' ')
  let initials = name
  if (nameArray.length > 1) {
    initials = nameArray[0].charAt(0) + nameArray[1].charAt(0)
  } else if (name.length > 1) {
    initials = name.charAt(0) + name.charAt(1)
  }

  return initials
}
