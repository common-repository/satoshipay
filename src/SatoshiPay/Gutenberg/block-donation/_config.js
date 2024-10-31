const { __ } = wp.i18n
import { SvgIcon } from '../helpers'

export default {
    title: __( 'Donation' ),
    icon: <SvgIcon type="heart" size="15" fill="#565D66" style={{verticalAlign: 'middle'}} />,
    category: 'satoshipay',
    attributes: {
        donationValue: { // store the donation value
            type: 'number'
        },
        donationCurrency: { // store the displayed donation currency
            type: 'string',
            default: null
        },
        placeholderId: { // store the generated placeholder post
            type: 'number'
        },
        enabled: { // is the donation block activated or not
            type: 'boolean',
            default: false
        },
        creatingPlaceholder: { // A loading state to avoid sending multiple requests
            type: 'boolean'
        },
        coverWidth: { // store the cover display width
            type: 'number'
        },
        coverHeight: { // store the cover display height
            type: 'number'
        },
        coverType: { // store the cover type none | image
            type: 'string'
        },
        coverUrl: { // store the cover url - default is grey solid color
            type: 'string',
            default: ''
        },
        coverTitle: { // store the cover title to be displayed in the cover select dropdown
            type: 'string'
        }
    },
    keywords: [
        __( 'article — satoshiPay block' ),
        __( 'satoshiPay' ),
        __( 'paywall' ),
    ]
}
