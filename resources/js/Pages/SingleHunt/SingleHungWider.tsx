import React from 'react'
import { getNumberWithFractionAndInches } from '@/Utils/numbers'
import {
  Svg,
  Defs,
  ClipPath,
  Path,
  G,
  Rect,
  Text
} from '@react-pdf/renderer'

const SingleHungWider = ({ width, height, svgHeight, svgWidth, heightOfMovementPart }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
  heightOfMovementPart: number
}) => {
  return (
    <Svg width={svgWidth} viewBox="0 0 375 374.999991" height={svgHeight} preserveAspectRatio="xMidYMid meet">
      <Defs>
        <ClipPath id="14befc01c9">
          <Path d="M 54.578125 96.230469 L 332.425781 96.230469 L 332.425781 278.761719 L 54.578125 278.761719 Z M 54.578125 96.230469 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="c858764ee8">
          <Path d="M 54.578125 96.511719 L 332.078125 96.511719 L 332.078125 278.757812 L 54.578125 278.757812 Z M 54.578125 96.511719 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="92d922dc9b">
          <Path d="M 77.15625 111.058594 L 309.855469 111.058594 L 309.855469 263.929688 L 77.15625 263.929688 Z M 77.15625 111.058594 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="7fba669a76">
          <Path d="M 77.15625 111.0625 L 309.65625 111.0625 L 309.65625 263.925781 L 77.15625 263.925781 Z M 77.15625 111.0625 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="ef242f5e6c">
          <Path d="M 77.152344 178.027344 L 309.847656 178.027344 L 309.847656 197.148438 L 77.152344 197.148438 Z M 77.152344 178.027344 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="49f2be43b0">
          <Path d="M 77.191406 178.027344 L 309.652344 178.027344 L 309.652344 196.777344 L 77.191406 196.777344 Z M 77.191406 178.027344 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="f998dab42d">
          <Path d="M 81.976562 115.796875 L 305.207031 115.796875 L 305.207031 176.46875 L 81.976562 176.46875 Z M 81.976562 115.796875 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="5fb3011663">
          <Path d="M 81.980469 115.796875 L 305.195312 115.796875 L 305.195312 176.464844 L 81.980469 176.464844 Z M 81.980469 115.796875 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="ae475816e4">
          <Path d="M 81.976562 198.648438 L 304.773438 198.648438 L 304.773438 259.789062 L 81.976562 259.789062 Z M 81.976562 198.648438 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="f6f825f1ec">
          <Path d="M 81.984375 198.648438 L 304.726562 198.648438 L 304.726562 259.785156 L 81.984375 259.785156 Z M 81.984375 198.648438 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="3b8039c8bc">
          <Path d="M 187 215.070312 L 199.738281 215.070312 L 199.738281 242.820312 L 187 242.820312 Z M 187 215.070312 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#14befc01c9)">
        <Path fill="#d9d9d9" d="M 54.578125 278.761719 L 54.578125 96.230469 L 332.65625 96.230469 L 332.65625 278.761719 Z M 54.578125 278.761719 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#c858764ee8)">
        <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 54.579277, 278.759367)" fill="none" stroke-linejoin="miter" d="M 0.00207306 -0.00153589 L 243.371865 -0.00153589 L 243.371865 370.45159 L 0.00207306 370.45159 Z M 0.00207306 -0.00153589 " stroke="#000000" strokeWidth="6" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#92d922dc9b)">
        <Path fill="#ffffff" d="M 77.15625 263.929688 L 77.15625 111.058594 L 310.046875 111.058594 L 310.046875 263.929688 Z M 77.15625 263.929688 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#7fba669a76)">
        <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 77.156169, 263.926917)" fill="none" stroke-linejoin="miter" d="M 0.00151443 0.000107955 L 203.819236 0.000107955 L 203.819236 310.250128 L 0.00151443 310.250128 Z M 0.00151443 0.000107955 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 75, 114.159338)" fill="none" stroke-linejoin="miter" d="M -0.00109086 1.999505 L 22.436411 1.999505 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 309, 115.19009)" fill="none" stroke-linejoin="miter" d="M -0.00175495 2.00049 L 22.435747 2.00049 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 75, 277.831213)" fill="none" stroke-linejoin="miter" d="M -0.00109106 1.999505 L 22.43641 1.999505 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 309, 277.831213)" fill="none" stroke-linejoin="miter" d="M -0.00109106 2.00049 L 22.43641 2.00049 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#ef242f5e6c)">
        <Path fill="#d9d9d9" d="M 77.152344 178.027344 L 309.8125 178.027344 L 309.8125 197.148438 L 77.152344 197.148438 Z M 77.152344 178.027344 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#49f2be43b0)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 77.191663, 178.026668)" fill="none" stroke-linejoin="miter" d="M -0.000341841 0.000901654 L 310.140323 0.000901654 L 310.140323 25.48528 L -0.000341841 25.48528 Z M -0.000341841 0.000901654 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#f998dab42d)">
        <Path fill="#ffffff" d="M 81.976562 115.796875 L 305.230469 115.796875 L 305.230469 176.46875 L 81.976562 176.46875 Z M 81.976562 115.796875 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#5fb3011663)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 81.980229, 114.2)" fill="none" stroke-linejoin="miter" d="M 0.000320045 -0.00194853 L 297.620131 -0.00194853 L 297.620131 80.888682 L 0.000320045 80.888682 Z M 0.000320045 -0.00194853 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#ae475816e4)">
        <Path fill="#ffffff" d="M 81.976562 198.648438 L 304.804688 198.648438 L 304.804688 259.789062 L 81.976562 259.789062 Z M 81.976562 198.648438 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#f6f825f1ec)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 81.984184, 199.5)" fill="none" stroke-linejoin="miter" d="M 0.000254778 0.00125993 L 297.047149 0.00125993 L 297.047149 81.51689 L 0.000254778 81.51689 Z M 0.000254778 0.00125993 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#3b8039c8bc)">
        <Path fill="#000000" d="M 199.359375 220.933594 L 194.039062 215.449219 C 193.671875 215.070312 193.0625 215.070312 192.695312 215.449219 L 187.367188 220.933594 C 186.996094 221.3125 186.996094 221.9375 187.367188 222.316406 C 187.734375 222.699219 188.339844 222.699219 188.710938 222.316406 L 192.410156 218.503906 L 192.410156 241.839844 C 192.410156 242.378906 192.839844 242.820312 193.363281 242.820312 C 193.886719 242.820312 194.3125 242.378906 194.3125 241.839844 L 194.3125 218.515625 L 198.011719 222.328125 C 198.203125 222.527344 198.441406 222.613281 198.691406 222.613281 C 198.941406 222.613281 199.179688 222.515625 199.371094 222.328125 C 199.726562 221.9375 199.726562 221.3125 199.359375 220.933594 Z M 199.359375 220.933594 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 332.224466, 56.624389)" fill="none" stroke-linejoin="miter" d="M 0.000814558 0.502413 L 47.542484 0.502413 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 55.517806, 56.624389)" fill="none" stroke-linejoin="miter" d="M 0.000814558 0.4977 L 47.542484 0.4977 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.749996, -0.00254387, 0.00254387, 0.749996, 54.889515, 59.439409)" fill="none" stroke-linejoin="miter" d="M 0.499789 0.499153 L 368.7832 0.498305 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.749996, -0.00254387, 0.00254387, 0.749996, 54.889515, 59.439409)" fill="none" stroke-linejoin="round" d="M 2.499675 -0.999281 L 0.499789 0.499153 L 2.499916 2.000737 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.749996, -0.00254387, 0.00254387, 0.749996, 54.889515, 59.439409)" fill="none" stroke-linejoin="round" d="M 366.783055 -0.998071 L 368.7832 0.498305 L 366.783296 2.001947 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.00000000000000538, -0.00000000000000538, 0.75, 335.527425, 278.016397)" fill="none" stroke-linejoin="miter" d="M -0.000108156 0.498971 L 47.541562 0.498971 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 366.09461, 278.698839)" fill="none" stroke-linejoin="miter" d="M 0.499494 0.498853 L 109.608876 0.498853 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 366.09461, 278.698839)" fill="none" stroke-linejoin="round" d="M 2.499494 -1.001147 L 0.499494 0.498853 L 2.499494 1.998853 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 366.09461, 278.698839)" fill="none" stroke-linejoin="round" d="M 107.608876 -1.001147 L 109.608876 0.498853 L 107.608876 1.998853 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 335.527425, 195.749987)" fill="none" stroke-linejoin="miter" d="M -0.000108156 0.500017 L 47.541562 0.500017 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 51.568134, 96.983571)" fill="none" stroke-linejoin="miter" d="M 0.00230355 0.498928 L 47.543973 0.498928 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 19.954517, 97.170154)" fill="none" stroke-linejoin="miter" d="M 0.502294 0.501856 L 240.93981 0.501856 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, 0.75, -0.75, 0, 19.954517, 97.170154)" fill="none" stroke-linejoin="round" d="M 2.502294 -0.998145 L 0.502294 0.501856 L 2.502294 2.001856 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, 0.75, -0.75, 0, 19.954517, 97.170154)" fill="none" stroke-linejoin="round" d="M 238.93981 -0.998145 L 240.93981 0.501856 L 238.93981 2.001856 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, 0, 0, -0.75, 51.568134, 279.516397)" fill="none" stroke-linejoin="miter" d="M 0.00230355 0.501029 L 47.543973 0.501029 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Text x="48%" y="45"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(width)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
        transform="matrix(0 -1 1 0 45 200)"
      >{getNumberWithFractionAndInches(height)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1' transform="matrix(0 -1 1 0 360 270)"
      >{getNumberWithFractionAndInches(heightOfMovementPart)}</Text>
    </Svg>
  )
}

export default SingleHungWider
