import React from 'react'
import { getNumberWithFractionAndInches } from '@/Utils/numbers'
import {
  Svg,
  Defs,
  ClipPath,
  Path,
  G,
  Text
} from '@react-pdf/renderer'

const FixedWindowsWider = ({ width, height, svgHeight, svgWidth }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
}) => {
  return (
    <Svg height={svgHeight} width={svgWidth} viewBox='0 0 375 374.999991' preserveAspectRatio='xMidYMid meet'>
      <Defs>
        <ClipPath id="5bef5d2fc3">
          <Path d="M 25.582031 118.613281 L 303.429688 118.613281 L 303.429688 301.144531 L 25.582031 301.144531 Z M 25.582031 118.613281 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="7eac9bbb43">
          <Path d="M 25.582031 118.894531 L 303.082031 118.894531 L 303.082031 301.144531 L 25.582031 301.144531 Z M 25.582031 118.894531 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="8d9aa56e51">
          <Path d="M 48.160156 133.441406 L 280.859375 133.441406 L 280.859375 286.316406 L 48.160156 286.316406 Z M 48.160156 133.441406 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="4d63fff8e8">
          <Path d="M 48.160156 133.445312 L 280.660156 133.445312 L 280.660156 286.3125 L 48.160156 286.3125 Z M 48.160156 133.445312 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="ce8bd7f4f6">
          <Path d="M 54.859375 139.914062 L 273.394531 139.914062 L 273.394531 279.84375 L 54.859375 279.84375 Z M 54.859375 139.914062 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="416e31b1aa">
          <Path d="M 54.863281 139.921875 L 273.109375 139.921875 L 273.109375 279.84375 L 54.863281 279.84375 Z M 54.863281 139.921875 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#5bef5d2fc3)">
        <Path fill="#d9d9d9" d="M 25.582031 301.144531 L 25.582031 118.613281 L 303.660156 118.613281 L 303.660156 301.144531 Z M 25.582031 301.144531 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#7eac9bbb43)">
        <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 25.583189, 301.143554)" fill="none" stroke-linejoin="miter" d="M -0.00130323 -0.00154428 L 243.368489 -0.00154428 L 243.368489 370.451581 L -0.00130323 370.451581 Z M -0.00130323 -0.00154428 " stroke="#000000" strokeWidth="6" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#8d9aa56e51)">
        <Path fill="#ffffff" d="M 48.160156 286.316406 L 48.160156 133.441406 L 281.050781 133.441406 L 281.050781 286.316406 Z M 48.160156 286.316406 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#4d63fff8e8)">
        <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 48.160077, 286.311104)" fill="none" stroke-linejoin="miter" d="M -0.00186187 0.000106283 L 203.821068 0.000106283 L 203.821068 310.250127 L -0.00186187 310.250127 Z M -0.00186187 0.000106283 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#ce8bd7f4f6)">
        <Path fill="#ffffff" d="M 54.859375 279.84375 L 54.859375 139.914062 L 273.5 139.914062 L 273.5 279.84375 Z M 54.859375 279.84375 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#416e31b1aa)">
        <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 54.864241, 279.843673)" fill="none" stroke-linejoin="miter" d="M -0.000102902 -0.00127953 L 186.562397 -0.00127953 L 186.562397 291.363304 L -0.000102902 291.363304 Z M -0.000102902 -0.00127953 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.000000000000003114, -0.000000000000003114, 0.75, 308.345785, 120.817389)" fill="none" stroke-linejoin="miter" d="M 0.00249493 0.49869 L 54.768124 0.49869 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 344.996819, 299.081464)" fill="none" stroke-linejoin="miter" d="M 0.499244 0.499034 L 235.509676 0.499034 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 344.996819, 299.081464)" fill="none" stroke-linejoin="round" d="M 2.499244 -1.000967 L 0.499244 0.499034 L 2.499244 1.999034 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 344.996819, 299.081464)" fill="none" stroke-linejoin="round" d="M 233.509676 -1.000967 L 235.509676 0.499034 L 233.509676 1.999034 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.000000000000004671, -0.000000000000004671, 0.75, 308.345785, 299.439415)" fill="none" stroke-linejoin="miter" d="M 0.00249493 0.497447 L 54.768124 0.497447 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, 0, 0, -0.75, 302.58514, 78.261624)" fill="none" stroke-linejoin="miter" d="M 0.498936 0.499873 L 367.186461 0.499873 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.75, 0, 0, -0.75, 302.58514, 78.261624)" fill="none" stroke-linejoin="round" d="M 2.498937 -1.000127 L 0.498936 0.499873 L 2.498937 1.999873 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.75, 0, 0, -0.75, 302.58514, 78.261624)" fill="none" stroke-linejoin="round" d="M 365.18646 -1.000127 L 367.186461 0.499873 L 365.18646 1.999873 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 279, 137.574248)" fill="none" stroke-linejoin="miter" d="M 0.0000386879 2.000514 L 22.43754 2.000514 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.588689, 0.464699, -0.464699, 0.588689, 51.659944, 137.286231)" fill="none" stroke-linejoin="miter" d="M -0.000694167 0.503021 L 7.627601 0.499801 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.588689, 0.464699, -0.464699, 0.588689, 273.860377, 279.850059)" fill="none" stroke-linejoin="miter" d="M 0.00102544 0.499407 L 7.625233 0.499415 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.590168, 0.462819, -0.462819, -0.590168, 55.408093, 280.158733)" fill="none" stroke-linejoin="miter" d="M 0.000643818 0.501128 L 7.627465 0.49874 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.590168, 0.462819, -0.462819, -0.590168, 278.116774, 136.844917)" fill="none" stroke-linejoin="miter" d="M -0.000640332 0.497891 L 7.63028 0.498717 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 27.206139, 73.849514)" fill="none" stroke-linejoin="miter" d="M -0.00247747 0.498811 L 54.768359 0.498811 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 302.220513, 114.923724)" fill="none" stroke-linejoin="miter" d="M 0.00246487 0.49765 L 54.768093 0.49765 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 47, 136.543496)" fill="none" stroke-linejoin="miter" d="M 0.000702778 1.999497 L 22.438204 1.999497 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 47, 300.215371)" fill="none" stroke-linejoin="miter" d="M 0.000702581 1.999497 L 22.438204 1.999497 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 279, 300.215371)" fill="none" stroke-linejoin="miter" d="M 0.000702581 2.000514 L 22.438204 2.000514 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Text x="38%" y="70"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(width)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1' transform="matrix(0 -1 1 0 340 224)"
      >{getNumberWithFractionAndInches(height)}</Text>
    </Svg>
  )
}

export default FixedWindowsWider
