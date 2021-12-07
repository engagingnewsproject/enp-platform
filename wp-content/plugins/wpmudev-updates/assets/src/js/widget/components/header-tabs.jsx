import React from 'react'

export default ({tabs, current, onChange}) => {
	return (
		<div className="wpmudui-analytics-tabs" data-tabs>
			{Object.keys(tabs).map((key) =>
				<a
					key={key}
					data-tab={key}
					className={key === current ? 'wpmudui-current' : ''}
					onClick={() => onChange(key)}
				>
					{tabs[key]}
				</a>
			)}
		</div>
	);
}