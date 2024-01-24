export const createMarkWithLeadingZero = (mark: number, length: number): string => {
  const markString = mark.toString()
  const markLength = markString.length
  if (markLength >= length) {
    return markString
  }
  const leadingZeroes = '0'.repeat(length - markLength)
  return leadingZeroes + markString
}

export const createNextMarkWithLeadingZero = (mark: number, length: number): string => {
  const nextMark = mark + 1
  return createMarkWithLeadingZero(nextMark, length)
}
