import React from 'react'
import {
  Document,
  Page,
  StyleSheet,
  PDFViewer
} from '@react-pdf/renderer'
import { CASEMENT_MULTIPOINT_CONFIG } from '@/Utils/constants'
import CasementMultipoint from './CasementMultipoint'
import CasementMultipointXX from './CasementMultipointXX'

const styles = StyleSheet.create({
  page: { padding: 20 },
  viewer: {
    width: 500,
    height: 500
  }
})

const CasementDrawing = ({ width, height, configuration }: {
  width: number
  height: number
  configuration: string
}) => {
  return (
    <PDFViewer style={styles.viewer} showToolbar={false}>
      <Document>
        <Page style={styles.page} size={{ width: 550, height: 550 }}>
          {configuration === CASEMENT_MULTIPOINT_CONFIG
            ? <CasementMultipoint width={width} height={height} svgHeight={500} svgWidth={500} />
            : <CasementMultipointXX width={width} height={height} svgHeight={500} svgWidth={500} widthtOfMovementPart={width / 2} />
          }
        </Page>
      </Document>
    </PDFViewer>
  )
}

export default CasementDrawing
