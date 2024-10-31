import { makeAjaxRequest } from '../../Utils'

const getImagePlaceholder = ({ mediaId, mediaPrice, mediaWidth, mediaHeight, coverUrl = '' }) => (
	<div dangerouslySetInnerHTML={{ __html: `<!--satoshipay:image attachment-id="${mediaId}" width="${mediaWidth}" height="${mediaHeight}" preview="${coverUrl}"-->` }}></div>
)

const getAudioPlaceholder = ({ mediaId, mediaPrice, mediaAutoPlay }) => (
	<div dangerouslySetInnerHTML={{ __html: `<!--satoshipay:audio attachment-id="${mediaId}" autoplay="${mediaAutoPlay}"-->` }}></div>
)

const getVideoPlaceholder = ({ mediaId, mediaPrice, mediaWidth, mediaHeight, mediaAutoPlay, coverUrl = '' }) => (
	<div dangerouslySetInnerHTML={{ __html: `<!--satoshipay:video attachment-id="${mediaId}" width="${mediaWidth}" height="${mediaHeight}" autoplay="${mediaAutoPlay}" preview="${coverUrl}"-->` }}></div>
)

const mediaPlaceholders = {
	image: getImagePlaceholder,
	audio: getAudioPlaceholder,
	video: getVideoPlaceholder,
}

export default ( { attributes } ) => {
    const { mediaId, mediaPrice, mediaType} = attributes
    if(mediaId && mediaPrice){
        // Create good or Update price
        makeAjaxRequest({
            body: {
                action: 'set_product_price',
                post_id: mediaId,
                price: mediaPrice || 0,
                enabled: 1
            }
        })
    }
    return mediaId ? mediaPlaceholders[mediaType](attributes) : ''
}
