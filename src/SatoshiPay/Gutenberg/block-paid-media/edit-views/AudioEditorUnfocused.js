import { PayButton } from '../../helpers'

const payButtonContainerStyle = {
    paddingLeft: '140px',
    minHeight: '65px',
    display: 'flex',
    alignItems: 'center',
}

export default ({ attributes }) => (
	<PayButton
		type="audio"
		price={attributes.mediaPrice}
		containerStyle={{
			...payButtonContainerStyle,
			background: '#d4e8ec'
		}}>
		<div>{attributes.mediaTitle} ({attributes.mediaSize})</div>
	</PayButton>
)
