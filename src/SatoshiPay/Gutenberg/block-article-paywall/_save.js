import { makeAjaxRequest, refreshBlocks } from '../../Utils'

export default ( { attributes } ) => {
    refreshBlocks()
    if(attributes.postId && attributes.price > 0){
        // Create good or Update price
        makeAjaxRequest({
            body: {
                action: 'set_product_price',
                post_id: attributes.postId,
                price: attributes.price || 0,
                enabled: attributes.enabled ? 1 : 0
            }
        })
    }
    return (
        <div>
            {
                attributes.enabled &&
                <div dangerouslySetInnerHTML={{ __html: '<!--satoshipay:start-->' }}></div>
            }
        </div>
    )
}
