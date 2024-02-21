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

const FixedWindowsTall = ({ width, height, svgHeight, svgWidth }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
}) => {
  return (
    <Svg height={svgHeight} width={svgWidth} viewBox='0 0 375 374.999991' preserveAspectRatio='xMidYMid meet'>
      <Defs>
        <ClipPath id="4026a6f80e">
          <Path d="M 97.496094 73.902344 L 280.027344 73.902344 L 280.027344 351.75 L 97.496094 351.75 Z M 97.496094 73.902344 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="aa63de0921">
          <Path d="M 97.496094 73.902344 L 279.746094 73.902344 L 279.746094 351.402344 L 97.496094 351.402344 Z M 97.496094 73.902344 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="125ff700a6">
          <Path d="M 112.324219 96.476562 L 265.195312 96.476562 L 265.195312 329.175781 L 112.324219 329.175781 Z M 112.324219 96.476562 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="fc44b4b50d">
          <Path d="M 112.328125 96.476562 L 265.191406 96.476562 L 265.191406 328.976562 L 112.328125 328.976562 Z M 112.328125 96.476562 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="30fba0590f">
          <Path d="M 118.796875 103.183594 L 258.726562 103.183594 L 258.726562 321.71875 L 118.796875 321.71875 Z M 118.796875 103.183594 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="c20fdbf784">
          <Path d="M 118.796875 103.1875 L 258.71875 103.1875 L 258.71875 321.433594 L 118.796875 321.433594 Z M 118.796875 103.1875 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#4026a6f80e)">
        <Path fill="#d9d9d9" d="M 97.496094 73.902344 L 280.027344 73.902344 L 280.027344 351.980469 L 97.496094 351.980469 Z M 97.496094 73.902344 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#aa63de0921)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 97.496585, 73.901678)" fill="none" stroke-linejoin="miter" d="M -0.000655397 0.000887204 L 243.369137 0.000887204 L 243.369137 370.454013 L -0.000655397 370.454013 Z M -0.000655397 0.000887204 " stroke="#000000" strokeWidth="6" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#125ff700a6)">
        <Path fill="#ffffff" d="M 112.324219 96.476562 L 265.195312 96.476562 L 265.195312 329.367188 L 112.324219 329.367188 Z M 112.324219 96.476562 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#fc44b4b50d)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 112.328736, 96.476845)" fill="none" stroke-linejoin="miter" d="M -0.000814032 -0.000376486 L 203.816908 -0.000376486 L 203.816908 310.249644 L -0.000814032 310.249644 Z M -0.000814032 -0.000376486 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#30fba0590f)">
        <Path fill="#ffffff" d="M 118.796875 103.183594 L 258.726562 103.183594 L 258.726562 321.824219 L 118.796875 321.824219 Z M 118.796875 103.183594 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#c20fdbf784)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 118.795837, 103.188984)" fill="none" stroke-linejoin="miter" d="M 0.00138333 -0.00197923 L 186.563884 -0.00197923 L 186.563884 291.362605 L 0.00138333 291.362605 Z M 0.00138333 -0.00197923 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 264.28947, 30.524853)" fill="none" stroke-linejoin="miter" d="M -0.0018877 0.500544 L 54.763741 0.500544 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 113.837803, 34.431311)" fill="none" stroke-linejoin="miter" d="M 0.497513 0.497835 L 199.294401 0.497835 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.75, 0, 0, 0.75, 113.837803, 34.431311)" fill="none" stroke-linejoin="round" d="M 2.497513 -1.002165 L 0.497513 0.497835 L 2.497513 1.997835 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.75, 0, 0, 0.75, 113.837803, 34.431311)" fill="none" stroke-linejoin="round" d="M 197.294401 -1.002165 L 199.294401 0.497835 L 197.294401 1.997835 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 320.378304, 350.912322)" fill="none" stroke-linejoin="miter" d="M 0.497679 0.500803 L 367.185203 0.500802 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 320.378304, 350.912322)" fill="none" stroke-linejoin="round" d="M 2.497679 -0.999197 L 0.497679 0.500803 L 2.497679 2.000803 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 320.378304, 350.912322)" fill="none" stroke-linejoin="round" d="M 365.185203 -0.999198 L 367.185203 0.500802 L 365.185203 2.000802 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 263, 350)" fill="none" stroke-linejoin="miter" d="M -0.0000580495 1.999211 L 27.82286 1.999211 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 111, 350)" fill="none" stroke-linejoin="miter" d="M -0.0000580495 1.998482 L 27.82286 1.998482 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 111, 96)" fill="none" stroke-linejoin="miter" d="M 0.00124664 1.999808 L 27.824165 1.999808 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 263, 96)" fill="none" stroke-linejoin="miter" d="M 0.00246449 1.999211 L 27.820175 1.999211 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 114.520406, 30.524853)" fill="none" stroke-linejoin="miter" d="M -0.0018877 0.501166 L 54.763741 0.501166 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.464699, 0.588689, -0.588689, -0.464699, 261.35776, 99.983043)" fill="none" stroke-linejoin="miter" d="M 0.0000309481 0.501531 L 7.628326 0.498311 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.464699, 0.588689, -0.588689, -0.464699, 118.793939, 322.183484)" fill="none" stroke-linejoin="miter" d="M -0.00233915 0.501158 L 7.629183 0.502026 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.462819, -0.590168, 0.590168, -0.462819, 118.485261, 103.731197)" fill="none" stroke-linejoin="miter" d="M -0.000967858 0.500775 L 7.629952 0.501601 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.462819, -0.590168, 0.590168, -0.462819, 261.799089, 326.439881)" fill="none" stroke-linejoin="miter" d="M 0.00185877 0.500741 L 7.62868 0.498353 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 324.79554, 75.528182)" fill="none" stroke-linejoin="miter" d="M -0.00178004 0.501118 L 54.763849 0.501118 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.000000000000003114, -0.000000000000003114, 0.75, 283.721323, 350.542566)" fill="none" stroke-linejoin="miter" d="M 0.00177704 0.500537 L 54.767406 0.500537 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Text x="48%" y="30"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(width)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1' transform="matrix(0 -1 1 0 340 230)"
      >{getNumberWithFractionAndInches(height)}</Text>
    </Svg>
  )
}

export default FixedWindowsTall
