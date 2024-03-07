export const getGlassWidth = (width: number) => {
  return (width / 2) - (5.25 / 2) - 0.125
}

export const getGlassHeight = (height: number) => {
  return height - 5.21 - 0.1
}

export const getMoveGlassHeight = (height: number) => {
  return getGlassHeight(height) - 0.25 - 0.15 - 0.125
}

export const getVentBottomAndTop = (width: number) => {
  return getGlassWidth(width) + 2.188
}
