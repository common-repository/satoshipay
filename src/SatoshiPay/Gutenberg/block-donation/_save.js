import { makeAjaxRequest, getSvgSolidColor } from '../../Utils'

export default ( { attributes } ) => {
    const { placeholderId, donationValue, coverWidth, coverHeight, coverUrl, donationCurrency, enabled } = attributes
    if(placeholderId && donationValue && enabled){
        // Create good or Update price
        makeAjaxRequest({
            body: {
                action: 'set_product_price',
                post_id: placeholderId,
                price: donationValue || 0,
                enabled: 1
            }
        })
    }
    return (
        enabled
        ? <div dangerouslySetInnerHTML={{ __html: `<!--satoshipay:donation attachment-id="${placeholderId}" width="${coverWidth}" height="${coverHeight}" preview="${coverUrl}" asset="${donationCurrency}"-->` }}></div>
        : null
    );
}
