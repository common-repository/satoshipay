const { __ } = wp.i18n
import { SvgIcon } from '../helpers'

export default {
    title: __( 'Paid File' ),
    icon: <SvgIcon type="folder" size="24" />,
    category: 'satoshipay',
    attributes: {
        fileId: {
            type: 'number'
        },
        fileTitle: {
            type: 'string'
        },
        filePrice: {
            type: 'number'
        },
        fileSize: {
            type: 'string'
        },
    },
    keywords: [
        __( 'article — satoshiPay block' ),
        __( 'satoshiPay' ),
        __( 'paywall' ),
    ],
}
