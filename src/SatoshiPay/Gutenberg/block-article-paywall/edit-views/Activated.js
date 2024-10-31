import {
    Form, If, CheckIfBelowPaywall, SvgIcon,
    PayButton, CryptoToFiat, BlockEditorLayout
} from '../../helpers'

const { TextInput, Button } = Form

export default ({ attributes, setAttributes, clientId, isSelected }) => (
    <div className="spgb__block spgb__paywall--activated">
        <CheckIfBelowPaywall clientId={clientId} />
        <If condition={!isSelected}>
            <div className="spgb__block__header  spgb__text-align--center">
                <SvgIcon type="wall" size="15" /> Paywall
            </div>
        </If>

        <If condition={isSelected}>
            <div className="spgb__block__body">
                <PayButton
                    price={attributes.price}
                    type="paywall"
                    style={{marginBottom: '20px'}}
                />
                <BlockEditorLayout>
                    <TextInput
                        label="Price"
                        affix="lumens"
                        type="number"
                        value={ attributes.price }
                        placeholder="0.00"
                        min="0"
                        onChange={ price => setAttributes( { price: price ? (parseInt(price) >= 0 ? parseInt(price) : parseInt(price) * -1) : null } ) }
                    />
                    <CryptoToFiat
                        value={ attributes.price }
                    />
                    <Button
                        className="spgb__paywall__active-toggle"
                        value="Deactivate Paywall"
                        isSolid
                        alignRight
                        onClick={() => setAttributes({ enabled: false })}>
                    </Button>
                </BlockEditorLayout>
            </div>
        </If>
    </div>
)
