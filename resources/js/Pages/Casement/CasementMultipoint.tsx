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

const CasementMultipoint = ({ width, height, svgHeight, svgWidth }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
}) => {
  return (
    <Svg height={svgHeight} width={svgWidth} viewBox='0 0 375 374.999991' preserveAspectRatio='xMidYMid meet'>
      <Defs>
        <ClipPath id="e8a488b547">
          <Path d="M 25.011719 78.269531 L 306.167969 78.269531 L 306.167969 359.421875 L 25.011719 359.421875 Z M 25.011719 78.269531 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="fac6a15d4f">
          <Path d="M 25.011719 78.269531 L 306.15625 78.269531 L 306.15625 359.414062 L 25.011719 359.414062 Z M 25.011719 78.269531 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="0f1174c296">
          <Path d="M 47.855469 101.113281 L 283.324219 101.113281 L 283.324219 336.578125 L 47.855469 336.578125 Z M 47.855469 101.113281 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="1dca7bb86d">
          <Path d="M 47.855469 101.113281 L 283.320312 101.113281 L 283.320312 336.578125 L 47.855469 336.578125 Z M 47.855469 101.113281 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="bf7858b5dd">
          <Path d="M 53.128906 106.382812 L 278.050781 106.382812 L 278.050781 331.308594 L 53.128906 331.308594 Z M 53.128906 106.382812 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="d8e1ab1e8d">
          <Path d="M 53.128906 106.382812 L 278.046875 106.382812 L 278.046875 331.300781 L 53.128906 331.300781 Z M 53.128906 106.382812 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#e8a488b547)">
        <Path fill="#d9d9d9" d="M 25.011719 78.269531 L 306.167969 78.269531 L 306.167969 359.421875 L 25.011719 359.421875 Z M 25.011719 78.269531 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#fac6a15d4f)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 25.012127, 78.267696)" fill="none" stroke-linejoin="miter" d="M -0.000544997 0.00244745 L 374.858855 0.00244745 L 374.858855 374.861847 L -0.000544997 374.861847 Z M -0.000544997 0.00244745 " stroke="#000000" strokeWidth="6" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#0f1174c296)">
        <Path fill="#ffffff" d="M 47.855469 101.113281 L 283.324219 101.113281 L 283.324219 336.578125 L 47.855469 336.578125 Z M 47.855469 101.113281 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#1dca7bb86d)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 47.856023, 101.111588)" fill="none" stroke-linejoin="miter" d="M -0.000738943 0.00225799 L 313.952427 0.00225799 L 313.952427 313.955424 L -0.000738943 313.955424 Z M -0.000738943 0.00225799 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#bf7858b5dd)">
        <Path fill="#ffffff" d="M 53.128906 106.382812 L 278.050781 106.382812 L 278.050781 331.308594 L 53.128906 331.308594 Z M 53.128906 106.382812 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#d8e1ab1e8d)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 53.127687, 106.383252)" fill="none" stroke-linejoin="miter" d="M 0.00162512 -0.000586286 L 299.892251 -0.000586286 L 299.892251 299.890039 L 0.00162512 299.890039 Z M 0.00162512 -0.000586286 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(-0.462819, -0.590168, 0.590168, -0.462819, 53.893038, 108.161791)" fill="none" stroke-linejoin="miter" d="M 0.00123978 0.499919 L 7.628061 0.497531 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.462819, -0.590168, 0.590168, -0.462819, 280.223278, 334.492039)" fill="none" stroke-linejoin="miter" d="M -0.00210521 0.50033 L 7.628815 0.501156 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.464699, 0.588689, -0.588689, -0.464699, 54.206615, 330.21071)" fill="none" stroke-linejoin="miter" d="M -0.00336252 0.49961 L 7.62816 0.500478 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.464699, 0.588689, -0.588689, -0.464699, 280.536872, 103.456217)" fill="none" stroke-linejoin="miter" d="M -0.000986906 0.501356 L 7.627309 0.498136 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 305.266184, 34.378771)" fill="none" stroke-linejoin="miter" d="M 0.000180517 0.500746 L 55.416851 0.500746 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.000000000000004616, -0.000000000000004616, 0.75, 308.424505, 358.200152)" fill="none" stroke-linejoin="miter" d="M 0.0017016 0.498756 L 55.418372 0.498756 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 26.230919, 34.378771)" fill="none" stroke-linejoin="miter" d="M 0.000180517 0.500601 L 55.416851 0.500601 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 25.858981, 37.722441)" fill="none" stroke-linejoin="miter" d="M 0.500525 0.500287 L 371.552633 0.500287 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.75, 0, 0, 0.75, 25.858981, 37.722441)" fill="none" stroke-linejoin="round" d="M 2.500525 -0.999713 L 0.500525 0.500287 L 2.500525 2.000287 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.75, 0, 0, 0.75, 25.858981, 37.722441)" fill="none" stroke-linejoin="round" d="M 369.552633 -0.999713 L 371.552633 0.500287 L 369.552633 2.000287 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 345.344689, 358.580952)" fill="none" stroke-linejoin="miter" d="M 0.498562 0.498748 L 371.561086 0.498747 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 345.344689, 358.580952)" fill="none" stroke-linejoin="round" d="M 2.498562 -1.001252 L 0.498562 0.498748 L 2.498562 1.998748 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 345.344689, 358.580952)" fill="none" stroke-linejoin="round" d="M 369.561086 -1.001253 L 371.561086 0.498747 L 369.561086 1.998747 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, -0.000000000000000558, 0.000000000000000558, 0.75, 308.424505, 79.164897)" fill="none" stroke-linejoin="miter" d="M 0.0017016 0.498887 L 55.418372 0.498887 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Text x="38%" y="30"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(width)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1' transform="matrix(0 -1 1 0 340 245)"
      >{getNumberWithFractionAndInches(height)}</Text>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 282, 101)" fill="none" stroke-linejoin="miter" d="M -0.00180272 2.001959 L 28.154449 2.001959 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 46, 101)" fill="none" stroke-linejoin="miter" d="M -0.00180272 2.001959 L 28.154449 2.001959 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 46, 358)" fill="none" stroke-linejoin="miter" d="M -0.00206305 2.002225 L 28.154189 2.002225 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 282, 358)" fill="none" stroke-linejoin="miter" d="M -0.00206305 2.002225 L 28.154189 2.002225 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 36, 130)" fill="none" stroke-linejoin="miter" d="M -0.00180272 2.001959 L 28.154449 2.001959 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 240, 348)" fill="none" stroke-linejoin="miter" d="M -0.00147737 1.500547 L 24.154774 1.500547 " stroke="#000000" strokeWidth="4" stroke-opacity="1" stroke-miterlimit="4"/>
    </Svg>
  )
}

export default CasementMultipoint
