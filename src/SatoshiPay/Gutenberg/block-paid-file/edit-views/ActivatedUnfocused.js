import { PayButton } from '../../helpers'

const payButtonContainerStyle = {
    paddingLeft: '140px',
    minHeight: '65px',
    display: 'flex',
    alignItems: 'center',
}

export default ({ attributes }) => (
    <PayButton
        type="file"
        price={attributes.filePrice}
        containerStyle={payButtonContainerStyle}>
        <div>{attributes.fileTitle} { attributes.fileSize ? `(${attributes.fileSize})` : '' }</div>
    </PayButton>
)
