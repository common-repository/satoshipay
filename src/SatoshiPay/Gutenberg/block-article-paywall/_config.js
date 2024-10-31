const { __ } = wp.i18n

import { SvgIcon } from '../helpers'

export default {
    title: __( 'Article Paywall' ),
	icon: <SvgIcon type="wall" size="512pt" />,
	category: 'satoshipay',
	supports: {
		multiple: false,
	},
	attributes: {
		postId: {
			type: 'number'
		},
		price: {
			type: 'number'
		},
		enabled: {
			type: 'boolean'
		}
	},
	keywords: [
		__( 'article — satoshiPay block' ),
		__( 'satoshiPay' ),
		__( 'paywall' ),
	],
}
