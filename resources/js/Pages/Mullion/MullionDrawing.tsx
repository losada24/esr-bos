import React from 'react'
import {
  Document,
  Page,
  StyleSheet,
  PDFViewer
} from '@react-pdf/renderer'
import MullionSGV from './MullionCSV'

const styles = StyleSheet.create({
  page: { padding: 20 },
  viewer: {
    width: 500,
    height: 500
  }
})

const MullionDrawing = ({ width, height }: {
  width: number
  height: number
}) => {
  return (
    <PDFViewer style={styles.viewer} showToolbar={false}>
      <Document>
        <Page style={styles.page} size={{ width: 550, height: 550 }}>
          {height > 0 && width > 0 && <MullionSGV width={width} height={height} svgHeight={500} svgWidth={500} /> }
        </Page>
      </Document>
    </PDFViewer>
  )
}

export default MullionDrawing
