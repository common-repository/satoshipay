import { getSvgSolidColor } from '../../Utils'
import config from './_config'

export default [
    {
        attributes: {
            ...config.attributes,
            coverUrl: { // store the cover url - default is grey solid color
                type: 'string',
                default: getSvgSolidColor()
            },
        },
        save({ attributes }) {
            const { placeholderId, coverWidth, coverHeight, coverUrl, donationCurrency, enabled } = attributes
            return (
                enabled
                ? <div dangerouslySetInnerHTML={{ __html: `<!--satoshipay:donation attachment-id="${placeholderId}" width="${coverWidth}" height="${coverHeight}" preview="${coverUrl === '' ? getSvgSolidColor() : coverUrl}" asset="${donationCurrency}"-->` }}></div>
                : null
            );
        }
    }
]
