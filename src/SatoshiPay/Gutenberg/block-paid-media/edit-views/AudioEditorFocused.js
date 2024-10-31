const { Fragment } = wp.element
import { Form, PayButton, BlockEditorLayout, CryptoToFiat } from '../../helpers'

const { TextInput, Checkbox } = Form

const payButtonContainerStyle = {
    paddingLeft: '140px',
    minHeight: '65px',
    display: 'flex',
    alignItems: 'center',
}

export default ({ attributes, setAttributes }) => (
    <Fragment>
        <PayButton
            type="audio"
            price={attributes.mediaPrice}
            containerStyle={payButtonContainerStyle}>
            <audio controls style={{ height: '42px' }}>
                <source src={attributes.mediaUrl} type={attributes.mediaMime} />
            </audio>
        </PayButton>
        <BlockEditorLayout>
            <div>
                <TextInput
                    label="Price"
                    affix="lumens"
                    type="number"
                    value={ attributes.mediaPrice }
                    placeholder="0.00"
                    min="0"
                    onChange={ price => setAttributes( { mediaPrice: price ? (parseInt(price) >= 0 ? parseInt(price) : parseInt(price) * -1) : null } ) }
                />
                <CryptoToFiat
                    value={ attributes.mediaPrice }
                />
            </div>
            <Checkbox
                label="Autoplay"
                checked={ attributes.mediaAutoPlay }
                onChange={ ( mediaAutoPlay ) => { setAttributes( { mediaAutoPlay } ) } }
            />
        </BlockEditorLayout>
    </Fragment>
)
