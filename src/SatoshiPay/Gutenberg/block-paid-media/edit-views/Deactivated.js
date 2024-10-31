const { withState } = wp.compose
const { MediaPlaceholder } = wp.editor
const { Fragment } = wp.element

import { If, Loader, SvgIcon } from '../../helpers'

import { getFileInfo, makeAjaxRequest } from '../../../Utils'

const SelectMediaView = ({ setAttributes, isLoading, setState }) => {
    // Initial Media placeholder labels
    const labels = {
        title: (
            <Fragment>
                <SvgIcon type="media" size="15" fill="#565D66" style={{verticalAlign: 'middle', marginRight: '5px'}} /> Paid Media
            </Fragment>
        ),
        instructions:'Drag a media file, upload a new one or select a file from your library.'
    }

    // Allowed media types to be uploaded
    const allowedMediaTypes = [ 'image', 'audio', 'video' ]

    // On paid media upload or select from media library
    const onMediaSelect = media => {
        if( media.id || media.ID ) {
            const {
                id: mediaId,
                type: mediaType,
                mime: mediaMime,
                url: mediaUrl,
                title: mediaTitle,
                size: mediaSize,
                height: mediaHeight,
                width: mediaWidth,
            } = getFileInfo(media)

            setAttributes({
                mediaId,
                mediaType,
                mediaMime,
                mediaUrl,
                mediaTitle,
                mediaSize,
                mediaHeight: mediaHeight ? Math.round(mediaHeight * 580 / mediaWidth) : 0,
                mediaWidth: mediaWidth ? 580 : 0,
            })
        }
    }

    // On paid media URL submit
    const onMediaSelectURL = async url => {
        setState({ isLoading: true })

        // Upload media file
        const { data, success } = await makeAjaxRequest({
            body: {
                action: 'upload_media_from_url',
                url,
            }
        })

        if( success ) {
            const { media, file_size, media_meta } = data

            setState({ isLoading: false })

            onMediaSelect({
                ...media,
                file_size,
                ...media_meta,
            })
        }
    }

    return (
        <Fragment>
            <If condition={ isLoading }>
                <Loader />
            </If>
            <MediaPlaceholder
                onSelect={ onMediaSelect }
                onSelectURL={ onMediaSelectURL }
                labels={labels}
                allowedTypes={ allowedMediaTypes }
            />
        </Fragment>
    )
}

export default withState( {
    isLoading: false,
} )( SelectMediaView )
