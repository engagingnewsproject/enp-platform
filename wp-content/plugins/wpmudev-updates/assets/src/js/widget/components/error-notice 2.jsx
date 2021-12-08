/* global wdpI18n */
import React from 'react'

export default () => {
	return (
		<div className="wpmudui-notice wpmudui-notice-error">
			<p>{wdpI18n.labels.empty}</p>
			<div className="wpmudui-notice-buttons">
				<a onClick={() => window.location.reload()} className="wpmudui-button">
					{wdpI18n.labels.try_again}
				</a>
			</div>
		</div>
	);
}