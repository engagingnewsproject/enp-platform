import React from 'react';

const ChartOptionButton = (props) => {
	// Setup classnames.
	let className = props.current ? 'wpmudui-current' : ''
	className += ' wpmudui-' + props.total.direction

	return (
		<button
			className={`${className} wpmudui-tooltip wpmudui-tooltip-top wpmudui-tooltip-top-right wpmudui-tooltip-constrained`}
			data-type={props.name}
			data-tooltip={props.option.desc}
			onClick={() => props.handleClick(props.name)}
		>
			<span className="wpmudui-chart-option-title">{props.option.name}</span>
			<span className="wpmudui-chart-option-value">{props.total.value}</span>
			<span className="wpmudui-chart-option-trend">{props.total.change}</span>
		</button>
	);
}

export default ChartOptionButton