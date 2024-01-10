import { Image } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'

import image1 from '../../../assets/images/visual_ids/1.jpg'
import image2 from '../../../assets/images/visual_ids/2.jpg'
import image3 from '../../../assets/images/visual_ids/3.jpg'
import image4 from '../../../assets/images/visual_ids/4.jpg'
import image5 from '../../../assets/images/visual_ids/5.jpg'
import image6 from '../../../assets/images/visual_ids/6.jpg'
import image7 from '../../../assets/images/visual_ids/7.jpg'
import image8 from '../../../assets/images/visual_ids/8.jpg'
import image9 from '../../../assets/images/visual_ids/9.jpg'
import image10 from '../../../assets/images/visual_ids/10.jpg'
import image11 from '../../../assets/images/visual_ids/11.jpg'
import image12 from '../../../assets/images/visual_ids/12.jpg'
import image13 from '../../../assets/images/visual_ids/13.jpg'
import image14 from '../../../assets/images/visual_ids/14.jpg'
import image15 from '../../../assets/images/visual_ids/15.jpg'
import image16 from '../../../assets/images/visual_ids/16.jpg'
import image17 from '../../../assets/images/visual_ids/17.jpg'
import image18 from '../../../assets/images/visual_ids/18.jpg'
import image19 from '../../../assets/images/visual_ids/19.jpg'
import image20 from '../../../assets/images/visual_ids/20.jpg'
import image21 from '../../../assets/images/visual_ids/21.jpg'
import image22 from '../../../assets/images/visual_ids/22.jpg'
import image23 from '../../../assets/images/visual_ids/23.jpg'
import image24 from '../../../assets/images/visual_ids/24.jpg'
import image25 from '../../../assets/images/visual_ids/25.jpg'
import image26 from '../../../assets/images/visual_ids/26.jpg'
import image27 from '../../../assets/images/visual_ids/27.jpg'
import image28 from '../../../assets/images/visual_ids/28.jpg'
import image29 from '../../../assets/images/visual_ids/29.jpg'
import image30 from '../../../assets/images/visual_ids/30.jpg'
import image31 from '../../../assets/images/visual_ids/31.jpg'
import image32 from '../../../assets/images/visual_ids/32.jpg'
import image33 from '../../../assets/images/visual_ids/33.jpg'
import image34 from '../../../assets/images/visual_ids/34.jpg'
import image35 from '../../../assets/images/visual_ids/35.jpg'
import image36 from '../../../assets/images/visual_ids/36.jpg'
import image37 from '../../../assets/images/visual_ids/37.jpg'
import image38 from '../../../assets/images/visual_ids/38.jpg'
import image39 from '../../../assets/images/visual_ids/39.jpg'
import image40 from '../../../assets/images/visual_ids/40.jpg'
import image41 from '../../../assets/images/visual_ids/41.jpg'
import image42 from '../../../assets/images/visual_ids/42.jpg'
import image43 from '../../../assets/images/visual_ids/43.jpg'
import image44 from '../../../assets/images/visual_ids/44.jpg'
import image45 from '../../../assets/images/visual_ids/45.jpg'
import image46 from '../../../assets/images/visual_ids/46.jpg'
import image47 from '../../../assets/images/visual_ids/47.jpg'
import image48 from '../../../assets/images/visual_ids/48.jpg'
import image49 from '../../../assets/images/visual_ids/49.jpg'
import image50 from '../../../assets/images/visual_ids/50.jpg'
import image51 from '../../../assets/images/visual_ids/51.jpg'
import image52 from '../../../assets/images/visual_ids/52.jpg'
import image53 from '../../../assets/images/visual_ids/53.jpg'
import image54 from '../../../assets/images/visual_ids/54.jpg'
import image55 from '../../../assets/images/visual_ids/55.jpg'
import image56 from '../../../assets/images/visual_ids/56.jpg'
import image57 from '../../../assets/images/visual_ids/57.jpg'
import image58 from '../../../assets/images/visual_ids/58.jpg'
import image59 from '../../../assets/images/visual_ids/59.jpg'
import image60 from '../../../assets/images/visual_ids/60.jpg'
import image61 from '../../../assets/images/visual_ids/61.jpg'
import image62 from '../../../assets/images/visual_ids/62.jpg'
import image63 from '../../../assets/images/visual_ids/63.jpg'
import image64 from '../../../assets/images/visual_ids/64.jpg'
import image65 from '../../../assets/images/visual_ids/65.jpg'
import image66 from '../../../assets/images/visual_ids/66.jpg'
import image67 from '../../../assets/images/visual_ids/67.jpg'
import image68 from '../../../assets/images/visual_ids/68.jpg'
import image69 from '../../../assets/images/visual_ids/69.jpg'
import image70 from '../../../assets/images/visual_ids/70.jpg'
import image71 from '../../../assets/images/visual_ids/71.jpg'
import image72 from '../../../assets/images/visual_ids/72.jpg'
import image73 from '../../../assets/images/visual_ids/73.jpg'
import image74 from '../../../assets/images/visual_ids/74.jpg'
import image75 from '../../../assets/images/visual_ids/75.jpg'
import image76 from '../../../assets/images/visual_ids/76.jpg'
import image77 from '../../../assets/images/visual_ids/77.jpg'
import image78 from '../../../assets/images/visual_ids/78.jpg'

const tw = createTw({
  theme: {
    extend: { }
  }
})

const images: string[] = [
  image1,
  image2,
  image3,
  image4,
  image5,
  image6,
  image7,
  image8,
  image9,
  image10,
  image11,
  image12,
  image13,
  image14,
  image15,
  image16,
  image17,
  image18,
  image19,
  image20,
  image21,
  image22,
  image23,
  image24,
  image25,
  image26,
  image27,
  image28,
  image29,
  image30,
  image31,
  image32,
  image33,
  image34,
  image35,
  image36,
  image37,
  image38,
  image39,
  image40,
  image41,
  image42,
  image43,
  image44,
  image45,
  image46,
  image47,
  image48,
  image49,
  image50,
  image51,
  image52,
  image53,
  image54,
  image55,
  image56,
  image57,
  image58,
  image59,
  image60,
  image61,
  image62,
  image63,
  image64,
  image65,
  image66,
  image67,
  image68,
  image69,
  image70,
  image71,
  image72,
  image73,
  image74,
  image75,
  image76,
  image77,
  image78
]

const VisualId = ({ index }: { index: number }) => {
  return (
    <Image src={images[index]} style={tw('w-8 h-8')} />
  )
}

export default VisualId
