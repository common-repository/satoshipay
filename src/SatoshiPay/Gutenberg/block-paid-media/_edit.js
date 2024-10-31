import { DeactivatedView } from './edit-views'
import MediaEditors from './editors'

import { updateSavedPrice } from '../../Utils'

export default ( props ) => {
    const { setAttributes, attributes, className } = props
    const EditMediaView = MediaEditors[attributes.mediaType]
    updateSavedPrice({ setAttributes, attributes })

    return (
        <div className={ `spgb ${className}` }>
            {
                attributes.mediaType
                ? <EditMediaView { ...props } />
                : <DeactivatedView { ...props } />
            }
        </div>
    );
}
