// Convert bytes size to human readable text
export default size => {
    if (size < 1000) return `${size} Byte`
    if (size >= 1000) return `${size / 1000} KB`
    if (size >= 1024000) return `${size / 1024000} MB`
}
