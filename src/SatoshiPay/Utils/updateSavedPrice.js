import makeAjaxRequest from './makeAjaxRequest'

export default ({ setAttributes, attributes: { getSavedPriceLoading, getSavedPriceDone, mediaId, mediaPrice, fileId, filePrice } }) => {
    const priceKey = mediaPrice && !filePrice ? 'mediaPrice' : 'filePrice'
    const oldPrice = mediaPrice ||filePrice
    const id = mediaId || fileId
    if(
        id &&
        !getSavedPriceLoading &&
        !getSavedPriceDone
    ){
        setAttributes({ getSavedPriceLoading: true })
        // Get good price
        makeAjaxRequest({
            body: {
                action: 'get_product_price',
                post_id: id,
            }
        }).then(({ data, success }) => {
            setAttributes({ getSavedPriceLoading: false, getSavedPriceDone: true })
            if( success ) {
                if( data.price.satoshi !== oldPrice ){
                    setAttributes({ [priceKey]: data.price.satoshi })
                }
            }
        })
    }
}
