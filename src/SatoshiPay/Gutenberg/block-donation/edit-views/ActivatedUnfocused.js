import { PayButton } from '../../helpers'

export default ({ attributes }) => (
    <PayButton
        type="donation"
        price={attributes.donationValue}
        containerStyle={{
            minHeight: '100px'
        }}>
        <img src={attributes.coverUrl} width={`${attributes.coverTitle ? `${attributes.coverWidth}px` : '100%'}`} height={`${attributes.coverHeight}px`} />
    </PayButton>
)
