export default ( string, length = 15 ) => {
    if (string.length <= length ) return string

    const chunkLength = Math.floor( length / 2 )
    const startChunk = string.substring( 0, chunkLength )
    const endChunk = string.substring( length - chunkLength, length )

    return `${startChunk}...${endChunk}`
}
