const { Fragment } = wp.element
import { DeactivatedView, ActivatedViewFocused, ActivatedViewUnfocused } from './edit-views'
import { If } from '../helpers'
import { makeAjaxRequest } from '../../Utils'

export default ( props ) => {

	const { className, attributes, setAttributes, isSelected, toggleSelection, clientId } = props

    // Create a placeholder post if there is none
    if ( !attributes.placeholderId && !attributes.creatingPlaceholder ) {
        setAttributes({ creatingPlaceholder: true })

        // Create a donation placeholder post
        makeAjaxRequest({
            body: {
                action: 'create_donation_post'
            }
        }).then(({ success, data }) => {
            if( success ) {
                setAttributes({
                    placeholderId: data.ID,
                    creatingPlaceholder: false
                })
            }
        })
    }

    const ActivatedView = (
        <Fragment>
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
                attributes.enabled
                ? ActivatedView
                : <DeactivatedView { ...props } />
            }
        </div>
    );
}
