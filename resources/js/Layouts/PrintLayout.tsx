import { type PropsWithChildren } from 'react'
import {
  Document,
  StyleSheet,
  PDFViewer,
  Font
} from '@react-pdf/renderer'

import NunitoRegular from '@/fonts/Nunito-Regular.ttf'
import NunitoBold from '@/fonts/Nunito-Bold.ttf'

Font.register({ family: 'NunitoBold', src: NunitoBold, fontWeight: 'bold' })
Font.register({ family: 'NunitoRegular', src: NunitoRegular, fontWeight: 'normal' })

// Create styles
export const styles = StyleSheet.create({
  viewer: {
    width: '100%',
    height: window.innerHeight
  }
})

const PrintLayout = ({ children }: PropsWithChildren) => {
  return (
    <PDFViewer style={styles.viewer}>
      <Document>
        {children}
      </Document>
    </PDFViewer>
  )
}

export default PrintLayout
