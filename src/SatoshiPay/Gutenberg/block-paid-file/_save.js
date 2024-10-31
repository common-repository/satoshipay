import { makeAjaxRequest } from '../../Utils'

export default ( { attributes } ) => {
    const { fileId, filePrice} = attributes

    if(fileId && filePrice){
        // Create good or Update price
        makeAjaxRequest({
            body: {
                action: 'set_product_price',
                post_id: fileId,
                price: filePrice || 0,
                enabled: 1
            }
        })
    }

    return fileId ? <div dangerouslySetInnerHTML={{ __html: `<!--satoshipay:download attachment-id="${fileId}"-->` }}></div> : ''
}
