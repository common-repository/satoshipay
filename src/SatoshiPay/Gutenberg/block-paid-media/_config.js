const { __ } = wp.i18n
import { SvgIcon } from '../helpers'
import { getSvgSolidColor } from '../../Utils'

export default {
    title: __( 'Paid Media' ), // Block title.
    icon: <SvgIcon type="media" width="24px" height="18px" />,
    category: 'satoshipay', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
    attributes: {
    	mediaId: { // store the media attachment id
    		type: 'number'
    	},
    	mediaPrice: { // store the paid media price
    		type: 'number'
    	},
    	mediaType: { // store the media type image | video | audio
    		type: 'string'
    	},
    	mediaMime: { // store the media mime
    		type: 'string'
    	},
    	mediaUrl: { // store the paid media url
    		type: 'string'
    	},
    	mediaTitle: { // store the media file name
    		type: 'string'
    	},
    	mediaSize: { // store the media file size - used in audio placeholder
    		type: 'string'
    	},
    	mediaWidth: { // store the media display width - same used for media cover
    		type: 'number'
    	},
    	mediaHeight: { // store the media display height - same used for media cover
    		type: 'number'
    	},
    	mediaAutoPlay: { // store wether the media auto play or no
    		type: 'boolean',
    		default: false
    	},
    	coverType: { // store the cover type none | image
    		type: 'string'
    	},
    	coverUrl: { // store the cover url - default is grey solid color
    		type: 'string',
    		default: getSvgSolidColor()
    	},
    	coverTitle: { // store the cover title to be displayed in the cover select dropdown
    		type: 'string'
    	}
    },
    keywords: [
        __( 'media — satoshiPay block' ),
        __( 'satoshiPay' ),
        __( 'paid media' ),
    ],
}
