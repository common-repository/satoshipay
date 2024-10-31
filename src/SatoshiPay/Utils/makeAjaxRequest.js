import jsonToFormData from './jsonToFormData'

// Make ajax request
export default async ({
	url = ajaxurl,
	body = {},
	method = 'POST',
	headers = {}
}) => {
	try{
		const response = await fetch(url, {
			method,
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
				...headers
			},
			body: jsonToFormData(body),
			credentials: 'same-origin'
		})
		return await response.json()
	}catch(e){
		// console.log(e);
	}
}
