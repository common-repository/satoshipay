const { Fragment } = wp.element
const { MediaUpload } = wp.editor
import { getSvgSolidColor, limitString } from '../../../Utils'
import { Form, If, PayButton, SatoshiResizableBox, BlockEditorLayout, BlockCoverPreview, CryptoToFiat } from '../../helpers'

const { TextInput, Select } = Form

export default ({ attributes, setAttributes, toggleSelection }) => {

    // coverType enum
    const coverTypes = {
        COVER_TYPE_NONE: 'COVER_TYPE_NONE',
        COVER_TYPE_CHOOSE_FILE: 'COVER_TYPE_CHOOSE_FILE',
        COVER_TYPE_FILE: 'COVER_TYPE_FILE'
    }

    // Cover types dropdown options
    const getCoverTypesOptions = () => {
        let baseCoverTypes = [
            {
                label: 'None (grey box)',
                value: coverTypes.COVER_TYPE_NONE
            },
            {
                label: 'Choose file...',
                value: coverTypes.COVER_TYPE_CHOOSE_FILE
            },
        ]

        // Add the current selected cover image
        if( attributes.coverType === coverTypes.COVER_TYPE_FILE ){
            baseCoverTypes.push({
                label: limitString(attributes.coverTitle),
                value: coverTypes.COVER_TYPE_FILE
            })
        }

        return baseCoverTypes;
    }

    return (
        <Fragment>
            <PayButton type="image" price={attributes.mediaPrice}>
                <SatoshiResizableBox
                    size={ {
                        height: attributes.mediaHeight,
                        width: attributes.mediaWidth,
                    } }
                    setAttributes={ setAttributes }
                    toggleSelection={ toggleSelection }>
                    <img src={attributes.mediaUrl} width={`${attributes.mediaWidth}px`} height={`${attributes.mediaHeight}px`} />
                </SatoshiResizableBox>
            </PayButton>
            <BlockEditorLayout>
                <div>
                    <TextInput
                        label="Price"
                        affix="lumens"
                        type="number"
                        value={ attributes.mediaPrice }
                        placeholder="0.00"
                        min="0"
                        onChange={ price => setAttributes( { mediaPrice: price ? (parseInt(price) >= 0 ? parseInt(price) : parseInt(price) * -1) : null } ) }
                    />
                    <CryptoToFiat
                        value={ attributes.mediaPrice }
                    />
                </div>
                <MediaUpload
                    onSelect={ ( media ) => {
                        setAttributes({
                            coverType: coverTypes.COVER_TYPE_FILE,
                            coverUrl: media.url,
                            coverTitle: `${media.title} (${media.name})`
                        })
                    }}
                    allowedTypes={ ['image'] }
                    render={ ( { open } ) => (
                        <Select
                            label="Cover"
                            size="large"
                            value={ attributes.coverType }
                            options={ getCoverTypesOptions() }
                            onSelect={ ( coverType ) => {
                                switch (coverType) {
                                    case coverTypes.COVER_TYPE_CHOOSE_FILE:
                                    open()
                                    break;
                                    case coverTypes.COVER_TYPE_FILE:
                                    break;
                                    default:
                                    setAttributes({ coverType, coverUrl: getSvgSolidColor(), coverTitle: '' })
                                }
                            } }
                        />
                    ) }
                />
            </BlockEditorLayout>
            <BlockCoverPreview>
                <img style={{height: '75px', width: 'auto'}} src={attributes.coverUrl} alt={attributes.coverTitle || 'cover'}/>
            </BlockCoverPreview>
        </Fragment>
    )
}
