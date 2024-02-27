const getGlassHeight = (height: number) => {
  return (height / 2) - (5.25 / 2) - 0.0625
}

export const getFrameJambs = (height: number) => {
  return getGlassHeight(height) + 2.188
}
