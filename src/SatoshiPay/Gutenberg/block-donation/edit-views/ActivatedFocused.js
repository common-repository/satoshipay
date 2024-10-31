const { MediaUpload } = wp.editor

import {
	Form, PayButton, CryptoToFiat,
    BlockEditorLayout, BlockCoverPreview,
} from '../../helpers'

import { getSvgSolidColor, limitString } from '../../../Utils'

const { TextInput, Select, Button } = Form

// Donation displayed currencies
const allowedCurrencies = [
    {
        label: 'USD',
        value: 'USD',
    },
    {
        label: 'EUR',
        value: 'EUR',
    },
    {
        label: 'GBP',
        value: 'GBP',
    }
]

// coverType enum
const coverTypes = {
    COVER_TYPE_NONE: 'COVER_TYPE_NONE',
    COVER_TYPE_CHOOSE_FILE: 'COVER_TYPE_CHOOSE_FILE',
    COVER_TYPE_FILE: 'COVER_TYPE_FILE'
}

// Cover types dropdown options
const getCoverTypesOptions = ({ attributes }) => {
    let baseCoverTypes = [
        {
            label: 'None',
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

export default ({ attributes, setAttributes }) => (
    <div className="spgb__block__body">
        <PayButton
            type="donation"
            price={attributes.donationValue}
            style={{marginBottom: '20px'}}
        />
        <BlockEditorLayout>
            <div>
                <TextInput
                    label="Price"
                    affix="lumens"
                    type="number"
                    value={ attributes.donationValue }
                    placeholder="0.00"
                    min="0"
                    onChange={ donationValue => setAttributes( { donationValue: donationValue ? (parseInt(donationValue) >= 0 ? parseInt(donationValue) : parseInt(donationValue) * -1) : null } ) }
                />
                <CryptoToFiat
                    fiat={ attributes.donationCurrency || undefined }
                    value={ attributes.donationValue }
                />
            </div>
            <Select
                label="Currency"
                value={ attributes.donationCurrency }
                options={ allowedCurrencies }
                placeholder="Choose currency..."
                onSelect={ ( donationCurrency ) => {
                    setAttributes({ donationCurrency });
                } }
            />
            <MediaUpload
                onSelect={ ({ url, title, name, height, width } ) => {
                    setAttributes({
                        coverType: coverTypes.COVER_TYPE_FILE,
                        coverUrl: url,
                        coverTitle: `${title} (${name})`,
                        coverHeight: height ? Math.round(height * 580 / width) : 0,
                        coverWidth: width ? 580 : 0,
                    })
                }}
                allowedTypes={ ['image'] }
                render={ ( { open } ) => (
                    <Select
                        label="Cover"
                        size="large"
                        value={ attributes.coverType }
                        options={ getCoverTypesOptions({ attributes }) }
                        onSelect={ ( coverType ) => {
                            switch (coverType) {
                                case coverTypes.COVER_TYPE_CHOOSE_FILE:
                                open()
                                break;
                                case coverTypes.COVER_TYPE_FILE:
                                break;
                                default:
                                setAttributes({ coverType, coverUrl: '', coverTitle: '' })
                            }
                        } }
                    />
                ) }
            />
        </BlockEditorLayout>
        <BlockCoverPreview>
			{
				attributes.coverUrl &&
				<img style={{height: '75px', width: 'auto'}} src={attributes.coverUrl} alt={attributes.coverTitle || 'cover'}/>
			}
        </BlockCoverPreview>
        <BlockEditorLayout>
            <Button
                value="Deactivate donation button"
                isSolid
                onClick={() => setAttributes({ enabled: false })}>
            </Button>
        </BlockEditorLayout>
    </div>
)
