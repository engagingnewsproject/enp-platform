/**
 * Post request maker function.
 *
 * We use window.fetch instead of jQuery ajax to avoid using jQuery.
 *
 * @param {string} action Request action.
 * @param {string|object} data Request data.
 *
 * @since 4.11.4
 * @return {Promise<any>}
 */
const ajaxRequest = async (action, data = {}) => {
	// Request configuration.
	let options = {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': wdp_analytics_ajax.nonce
		}
	};

	// Create form data with required data.
	let formData = new FormData();
	formData.append('action', action);
	formData.append('hash', wdp_analytics_ajax.nonce);
	formData.append('network', wdp_analytics_ajax.network_flag);

	// Set request data.
	if (Object.keys(data).length > 0) {
		Object.keys(data).forEach((key) => {
			formData.append(key, data[key]);
		})
	}

	// Set request body.
	options.body = formData

	// Make request.
	const response = await fetch(window.ajaxurl, options);

	// Response should always be in json.
	return response.json();
}

export default ajaxRequest;