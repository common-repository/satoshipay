// Convert JSON Object to FormData string
export default json => {
	let attributes = [];
	for (let key in json) {
		if (json.hasOwnProperty(key)) {
			attributes.push(`${key}=${json[key]}`)
		}
	}
	return attributes.join('&')
}
