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

const SingleHungSquared = ({ width, height, svgHeight, svgWidth, heightOfMovementPart }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
  heightOfMovementPart: number
}) => {
  return (
    <Svg width={svgWidth} viewBox="0 0 375 374.999991" height={svgHeight} preserveAspectRatio="xMidYMid meet">
      <Defs>
        <ClipPath id="632c60c258">
          <Path d="M 66.34375 85.730469 L 307.539062 85.730469 L 307.539062 326.925781 L 66.34375 326.925781 Z M 66.34375 85.730469 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="68ddf86f63">
          <Path d="M 85.941406 105.328125 L 287.941406 105.328125 L 287.941406 307.328125 L 85.941406 307.328125 Z M 85.941406 105.328125 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="55fd1a661c">
          <Path d="M 85.941406 105.328125 L 287.691406 105.328125 L 287.691406 307.078125 L 85.941406 307.078125 Z M 85.941406 105.328125 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="ceb228b5af">
          <Path d="M 85.941406 196.765625 L 287.941406 196.765625 L 287.941406 215.886719 L 85.941406 215.886719 Z M 85.941406 196.765625 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="0364b75b75">
          <Path d="M 85.976562 196.765625 L 287.691406 196.765625 L 287.691406 215.515625 L 85.976562 215.515625 Z M 85.976562 196.765625 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="b8efab4e3b">
          <Path d="M 90.371094 109.417969 L 282.9375 109.417969 L 282.9375 194.683594 L 90.371094 194.683594 Z M 90.371094 109.417969 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="d7ec27f894">
          <Path d="M 90.371094 109.417969 L 282.933594 109.417969 L 282.933594 194.683594 L 90.371094 194.683594 Z M 90.371094 109.417969 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="e15b7ccfa7">
          <Path d="M 90.371094 217.636719 L 282.9375 217.636719 L 282.9375 302.90625 L 90.371094 302.90625 Z M 90.371094 217.636719 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="dda43b13cf">
          <Path d="M 90.371094 217.636719 L 282.933594 217.636719 L 282.933594 302.902344 L 90.371094 302.902344 Z M 90.371094 217.636719 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="1deca8237a">
          <Path d="M 179.804688 245.792969 L 193.304688 245.792969 L 193.304688 273.542969 L 179.804688 273.542969 Z M 179.804688 245.792969 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#632c60c258)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 66.343675, 85.729346)" fillOpacity="1" fill="#d9d9d9" fill-rule="nonzero" stroke-linejoin="miter" d="M 0.0000995421 0.00149757 L 321.593871 0.00149757 L 321.593871 321.595269 L 0.0000995421 321.595269 Z M 0.0000995421 0.00149757 " stroke="#000000" strokeWidth="6" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#68ddf86f63)">
        <Path fill="#ffffff" d="M 85.941406 105.328125 L 287.941406 105.328125 L 287.941406 307.328125 L 85.941406 307.328125 Z M 85.941406 105.328125 " fillOpacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#55fd1a661c)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 85.940864, 105.326537)" fill="none" stroke-linejoin="miter" d="M 0.000722391 0.00211722 L 269.328882 0.00211722 L 269.328882 269.330277 L 0.000722391 269.330277 Z M 0.000722391 0.00211722 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 306.819623, 48.078292)" fill="none" stroke-linejoin="miter" d="M -0.000222724 0.49908 L 47.541447 0.49908 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 67.44241, 48.078292)" fill="none" stroke-linejoin="miter" d="M -0.000222724 0.501338 L 47.541447 0.501338 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 67.070162, 50.893317)" fill="none" stroke-linejoin="miter" d="M 0.5002 0.501619 L 318.672096 0.501619 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.75, 0, 0, 0.75, 67.070162, 50.893317)" fill="none" stroke-linejoin="round" d="M 2.5002 -0.998381 L 0.5002 0.501619 L 2.5002 2.00162 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.75, 0, 0, 0.75, 67.070162, 50.893317)" fill="none" stroke-linejoin="round" d="M 316.672096 -0.998381 L 318.672096 0.501619 L 316.672096 2.00162 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.000000000000004484, -0.000000000000004484, 0.75, 312.039858, 326.175535)" fill="none" stroke-linejoin="miter" d="M -0.00106015 0.500328 L 47.54061 0.500328 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.00369937, -0.749991, 0.749991, 0.00369937, 343.458263, 326.767479)" fill="none" stroke-linejoin="miter" d="M 0.499741 0.501107 L 147.845274 0.498284 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.00369937, -0.749991, 0.749991, 0.00369937, 343.458263, 326.767479)" fill="none" stroke-linejoin="round" d="M 2.497577 -0.998348 L 0.499741 0.501107 L 2.501958 2.001667 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.00369937, -0.749991, 0.749991, 0.00369937, 343.458263, 326.767479)" fill="none" stroke-linejoin="round" d="M 145.843056 -1.002275 L 147.845274 0.498284 L 145.842229 1.997766 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 312.039858, 215.137853)" fill="none" stroke-linejoin="miter" d="M -0.00106015 0.498488 L 47.54061 0.498488 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 84.5, 324.79489)" fill="none" stroke-linejoin="miter" d="M 0.0025615 1.500009 L 24.153605 1.500009 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 84.5, 105)" fill="none" stroke-linejoin="miter" d="M 0.00072382 1.500009 L 24.151767 1.500009 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 287, 324.79489)" fill="none" stroke-linejoin="miter" d="M 0.0025615 1.501269 L 24.153605 1.501269 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 287, 105)" fill="none" stroke-linejoin="miter" d="M 0.0025088 1.501269 L 24.153552 1.501269 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#ceb228b5af)">
        <Path fill="#d9d9d9" d="M 85.941406 196.765625 L 287.980469 196.765625 L 287.980469 215.886719 L 85.941406 215.886719 Z M 85.941406 196.765625 " fillOpacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#0364b75b75)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 85.977832, 196.767028)" fill="none" stroke-linejoin="miter" d="M -0.00169221 -0.00187051 L 269.227509 -0.00187051 L 269.227509 25.482508 L -0.00169221 25.482508 Z M -0.00169221 -0.00187051 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0.569816, -0.487658, 0.487658, 0.569816, 282.550913, 109.449698)" fill="none" stroke-linejoin="miter" d="M -0.0015958 0.498228 L 4.157936 0.50013 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#b8efab4e3b)">
        <Path fill="#ffffff" d="M 90.371094 109.417969 L 282.886719 109.417969 L 282.886719 194.683594 L 90.371094 194.683594 Z M 90.371094 109.417969 " fillOpacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#d7ec27f894)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 90.5, 108.5)" fill="none" stroke-linejoin="miter" d="M 0.00137598 0.00065378 L 256.751393 0.00065378 L 256.751393 113.688161 L 0.00137598 113.688161 Z M 0.00137598 0.00065378 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(-0.465731, -0.587873, 0.587873, -0.465731, 90.417193, 110.221385)" fill="none" stroke-linejoin="miter" d="M 0.000407483 0.499996 L 4.193556 0.49958 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.465731, -0.587873, 0.587873, -0.465731, 284.250132, 196.807209)" fill="none" stroke-linejoin="miter" d="M 0.00104059 0.498953 L 4.194189 0.498537 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.53033, -0.53033, 0.53033, 0.53033, 88.265456, 196.945782)" fill="none" stroke-linejoin="miter" d="M 0.000601949 0.500583 L 5.348097 0.500583 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.569816, -0.487658, 0.487658, 0.569816, 282.550913, 217.670273)" fill="none" stroke-linejoin="miter" d="M -0.00340057 0.500337 L 4.159517 0.498282 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#e15b7ccfa7)">
        <Path fill="#ffffff" d="M 90.371094 217.636719 L 282.886719 217.636719 L 282.886719 302.90625 L 90.371094 302.90625 Z M 90.371094 217.636719 " fillOpacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#dda43b13cf)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 90.370062, 219)" fill="none" stroke-linejoin="miter" d="M 0.00137598 -0.00179168 L 256.751393 -0.00179168 L 256.751393 113.685716 L 0.00137598 113.685716 Z M 0.00137598 -0.00179168 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(-0.465731, -0.587873, 0.587873, -0.465731, 90.417193, 218.441954)" fill="none" stroke-linejoin="miter" d="M -0.00177319 0.498269 L 4.195458 0.501087 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#1deca8237a)">
        <Path fill="#000000" d="M 192.90625 251.652344 L 187.273438 246.171875 C 186.882812 245.792969 186.242188 245.792969 185.851562 246.171875 L 180.207031 251.652344 C 179.816406 252.035156 179.816406 252.660156 180.207031 253.039062 C 180.597656 253.417969 181.238281 253.417969 181.628906 253.039062 L 185.546875 249.226562 L 185.546875 272.5625 C 185.546875 273.101562 186 273.542969 186.554688 273.542969 C 187.109375 273.542969 187.5625 273.101562 187.5625 272.5625 L 187.5625 249.238281 L 191.484375 253.050781 C 191.683594 253.246094 191.9375 253.332031 192.199219 253.332031 C 192.464844 253.332031 192.71875 253.234375 192.917969 253.050781 C 193.296875 252.660156 193.296875 252.035156 192.90625 251.652344 Z M 192.90625 251.652344 " fillOpacity="1" fill-rule="nonzero"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 62.952408, 87.548322)" fill="none" stroke-linejoin="miter" d="M -0.000955511 0.50193 L 47.540714 0.50193 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 31.336376, 87.17248)" fill="none" stroke-linejoin="miter" d="M 0.499194 0.500584 L 318.681506 0.500584 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, 0.75, -0.75, 0, 31.336376, 87.17248)" fill="none" stroke-linejoin="round" d="M 2.499194 -0.999416 L 0.499194 0.500584 L 2.499194 2.000584 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, 0.75, -0.75, 0, 31.336376, 87.17248)" fill="none" stroke-linejoin="round" d="M 316.681506 -0.999416 L 318.681506 0.500584 L 316.681506 2.000584 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, 0, 0, -0.75, 62.952416, 326.925535)" fill="none" stroke-linejoin="miter" d="M -0.000945912 0.499672 L 47.540724 0.499672 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.53033, -0.53033, 0.53033, 0.53033, 86, 306)" fill="none" stroke-linejoin="miter" d="M 0.00124774 0.500807 L 5.348743 0.500807 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.465731, -0.587873, 0.587873, -0.465731, 285, 306)" fill="none" stroke-linejoin="miter" d="M -0.000996851 0.498983 L 4.192152 0.498567 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Text x="45%" y="30"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(width)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
        transform="matrix(0 -1 1 0 25 220)"
      >{getNumberWithFractionAndInches(height)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1' transform="matrix(0 -1 1 0 340 300)"
      >{getNumberWithFractionAndInches(heightOfMovementPart)}</Text>
    </Svg>
  )
}

export default SingleHungSquared
