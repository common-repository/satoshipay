import { PayButton } from '../../helpers'

export default ({ attributes }) => (
    <PayButton
        type="image"
        price={attributes.mediaPrice}
        containerStyle={{
            minHeight: '100px'
        }}>
        <img src={attributes.coverUrl} width={`${attributes.mediaWidth}px`} height={`${attributes.mediaHeight}px`} />
    </PayButton>
)
