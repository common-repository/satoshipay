import { Form, If, SvgIcon } from '../../helpers'

const { Button } = Form

export default ({ setAttributes, isSelected }) => (
    <div className="spgb__block spgb__donation--deactivated">
        <div className="spgb__block__header  spgb__text-align--center">
            <SvgIcon type="heart" size="15" fill="#565D66" style={{verticalAlign: 'middle'}} /> Donation Button
        </div>
        <If condition={isSelected}>
            <div className="spgb__block__body spgb__text-align--center">
                <div>This button will let visitors send donations to your payout address specified in your Publisher Dashboard.</div>
                <div>To set up the amount, go ahead and activate your button!</div>
                <Button
                    style={{ margin: '16px auto' }}
                    onClick={() => setAttributes({ enabled: true })}
                    value="Activate donation button"
                />
            </div>
        </If>
    </div>
)
