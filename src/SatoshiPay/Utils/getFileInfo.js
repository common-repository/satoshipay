const { get, has } = lodash
import toHumanReadableSize from './toHumanReadableSize'

export default file => {

	// Allowed media types to be uploaded
	const mediaTypes = [ 'image', 'audio', 'video' ]

	// get object value by multiple possible paths
	const getValueByKeys = ( object, keys ) => (
		get(object, keys.find(key => has(object, key)))
	)

	// file attributes possibility paths
	const fileAttrs = {
		id: [ 'id', 'ID' ],
		mime: [ 'mime_type', 'mime', 'post_mime_type' ],
		type: [ 'media_type', 'type' ],
		url: [ 'url', 'guid' ],
		title: [ 'title', 'post_title' ],
		size: [ 'filesizeHumanReadable', 'file_size', 'media_details.filesize' ],
		width: [ 'width', 'media_details.width' ],
		height: [ 'height', 'media_details.height' ],
	}

	// generate file info object
	let fileInfo = Object.keys(fileAttrs).reduce((info, attr) => {
		info[attr] = getValueByKeys(file, fileAttrs[attr])
		return info
	}, {})

	// If unkown type or no type, get it from the mime
	if ( !(mediaTypes.includes(fileInfo.type) && fileInfo.type) && fileInfo.mime ) {
		fileInfo.type = fileInfo.mime.split('/')[0]
	}

    // if raw size convert it to human readable size
    if ( fileInfo.size && /^[0-9]*$/.test(fileInfo.size) ) {
        fileInfo.size = toHumanReadableSize(fileInfo.size)
    }

	return fileInfo
}
