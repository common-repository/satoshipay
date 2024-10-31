const { Fragment } = wp.element

import {
	Form,
    PayButton,
    BlockEditorLayout,
    CryptoToFiat,
} from '../../helpers'

import { getFileInfo } from '../../../Utils'

const { TextInput } = Form

const payButtonContainerStyle = {
    paddingLeft: '140px',
    minHeight: '65px',
    display: 'flex',
    alignItems: 'center',
}

export default ({ attributes, setAttributes }) => (
    <Fragment>
        <PayButton
            type="file"
            price={attributes.filePrice}
            containerStyle={payButtonContainerStyle}>
            <div>{attributes.fileTitle} ({attributes.fileSize})</div>
        </PayButton>
        <BlockEditorLayout>
            <div>
                <TextInput
                    label="Price"
                    affix="lumens"
                    type="number"
                    value={ attributes.filePrice }
                    placeholder="0.00"
                    min="0"
                    onChange={ price => setAttributes( { filePrice: price ? (parseInt(price) >= 0 ? parseInt(price) : parseInt(price) * -1) : null } ) }
                />
                <CryptoToFiat
                    value={ attributes.filePrice }
                />
            </div>
        </BlockEditorLayout>
    </Fragment>
)
