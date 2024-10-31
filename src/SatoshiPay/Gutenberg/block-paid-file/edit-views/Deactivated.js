const { MediaPlaceholder } = wp.editor
const { Fragment } = wp.element

import { SvgIcon } from '../../helpers'

import { getFileInfo } from '../../../Utils'

export default ({ setAttributes }) => {
    // Initial Media placeholder labels
    const labels = {
        title: (
            <Fragment>
                <SvgIcon type="folder" size="20" fill="#565D66" style={{verticalAlign: 'middle', marginRight: '5px'}} /> Paid File
            </Fragment>
        ),
        instructions:'Drag a file, upload a new one or select a file from your library.'
    }
    const onMediaSelect = file => {
        const {
            id: fileId,
            title: fileTitle,
            size: fileSize,
        } = getFileInfo(file)

        if( fileId ) {
            setAttributes({
                fileId,
                fileTitle,
                fileSize
            })
        }
    }
    return (
        <MediaPlaceholder
            onSelect={ onMediaSelect }
            labels={labels}
        />
    )
}
