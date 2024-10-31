import { ActivatedView, DeactivatedView } from './edit-views'
import { refreshBlocks } from '../../Utils'

// Used to refresh the <CheckIfBelowPaywall /> in other blocks
let oldBlockIndex;

export default ( props ) => {

    const { attributes, className, setAttributes, clientId } = props

    // Save the postId
    setAttributes({postId: wp.data.select('core/editor').getCurrentPostId()})

    // Refresh other blocks if paywall index changed
    const blockIndex = wp.data.select('core/editor').getBlockIndex(clientId)
    if(typeof oldBlockIndex === 'number' && blockIndex !== oldBlockIndex){
        refreshBlocks()
    }
    oldBlockIndex = blockIndex

    return (
        <div className={ `spgb ${className}` }>
            {
                attributes.enabled
                    ? <ActivatedView { ...props } />
                    : <DeactivatedView { ...props } />
            }
        </div>
    );
}
