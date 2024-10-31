const { Fragment } = wp.element
import { DeactivatedView, ActivatedViewFocused, ActivatedViewUnfocused } from './edit-views'

import { If, CheckIfBelowPaywall } from '../helpers'
import { updateSavedPrice } from '../../Utils'

export default ( props ) => {
	const { className, attributes, setAttributes, isSelected, clientId } = props
    updateSavedPrice({ setAttributes, attributes })

    const EditFileView = (
        <Fragment>
            {/* Check if this block is below a paywall */}
            <CheckIfBelowPaywall clientId={clientId} />

            {/* Block is selected (focused) */}
            <If condition={isSelected}>
                <ActivatedViewFocused { ...props } />
            </If>

            {/* Block is not selected (unfocused) */}
            <If condition={!isSelected}>
                <ActivatedViewUnfocused { ...props } />
            </If>
        </Fragment>
    )

    return (
        <div className={ `spgb ${className}` }>
            {
                attributes.fileId
                ? EditFileView
                : <DeactivatedView  { ...props } />
            }
        </div>
    );
}
