import {
    Form, If, CheckIfBelowPaywall, SvgIcon
} from '../../helpers'

const { Button } = Form

export default ({ setAttributes, clientId, isSelected }) => (
    <div className="spgb__block spgb__paywall--deactivated">
        <CheckIfBelowPaywall clientId={clientId} />
        <div className="spgb__block__header  spgb__text-align--center">
            <SvgIcon type="wall" size="15" fill="#565D66" style={{verticalAlign: 'middle'}} /> Paywall
        </div>
        <If condition={isSelected}>
            <div className="spgb__block__body spgb__text-align--center">
                <div>Everything you add after this block will be placed behind a paywall;</div>
                <div>visitors will be asked to pay the price you set below, to access further content.</div>
                <Button
                    style={{ margin: '16px auto' }}
                    value="Activate paywall"
                    onClick={() => setAttributes({ enabled: true })}>
                </Button>
            </div>
        </If>
    </div>
)
