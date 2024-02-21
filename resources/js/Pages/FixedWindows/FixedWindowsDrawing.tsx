import React from 'react'
import {
  Document,
  Page,
  StyleSheet,
  PDFViewer
} from '@react-pdf/renderer'
import FixedWindowsSquared from './FixedWindowsSquared'
import FixedWindowsTall from './FixedWindowsTall'
import FixedWindowsWider from './FixedWindowsWider'
import { getNumberWithFraction } from '@/Utils/numbers'

const styles = StyleSheet.create({
  page: { padding: 20 },
  viewer: {
    width: 500,
    height: 500
  }
})

const FixedWindowsDrawing = ({ width, height }: {
  width: number
  height: number
}) => {
  return (
    <PDFViewer style={styles.viewer} showToolbar={false}>
      <Document>
        <Page style={styles.page} size={{ width: 550, height: 550 }}>
          {height > width && <FixedWindowsTall width={width} height={height} svgHeight={500} svgWidth={500} /> }
          {width > height && <FixedWindowsWider width={width} height={height} svgHeight={500} svgWidth={500} /> }
          {getNumberWithFraction(height) === getNumberWithFraction(width) && <FixedWindowsSquared width={width} height={height} svgHeight={500} svgWidth={500} />}
        </Page>
      </Document>
    </PDFViewer>
  )
}

export default FixedWindowsDrawing
