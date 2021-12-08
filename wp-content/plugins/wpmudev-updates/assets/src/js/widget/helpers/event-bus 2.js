/**
 * Custom event bus for our app.
 *
 * This event bus will handle all global events.
 *
 * @since 4.11.4
 */
const eventBus = {

	/**
	 * Run an attached event.
	 *
	 * Use this to run custom actions on custom events.
	 * Use it like add_action() in WP.
	 *
	 * @param {string} event Event name.
	 * @param {function} callback Callback.
	 * @since 4.11.4
	 */
	on(event, callback) {
		document.addEventListener(
			'wpmudev' + event,
			(e) => callback(e.detail)
		);
	},

	/**
	 * Dispatch an event.
	 *
	 * Use this to execute custom events.
	 * Use it like do_action() in WP.
	 *
	 * @param {string} event Event name.
	 * @param {object} data Event data.
	 * @since 4.11.4
	 */
	dispatch(event, data) {
		document.dispatchEvent(
			new CustomEvent('wpmudev' + event, {
				detail: data
			})
		);
	},

	/**
	 * Remove an attached event.
	 *
	 * Use this to removed attached custom events.
	 * Use it like remove_action() in WP.
	 *
	 * @param {string} event Event name.
	 * @param {function} callback Callback.
	 * @since 4.11.4
	 */
	remove(event, callback) {
		document.removeEventListener(
			'wpmudev' + event,
			callback
		);
	},
};

export default eventBus;