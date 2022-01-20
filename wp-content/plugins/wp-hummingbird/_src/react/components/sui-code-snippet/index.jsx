/* global SUI */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * CodeSnippet component.
 */
export default class CodeSnippet extends React.Component {
	/**
	 * Share UI actions need to be performed manually for elements.
	 * They should be done in this method.
	 */
	componentDidMount() {
		this.initSUI();
	}

	componentDidUpdate() {
		this.initSUI();
	}

	initSUI() {
		const codeSnippet = document.querySelector( 'pre.sui-code-snippet' );
		if ( codeSnippet ) {
			SUI.suiCodeSnippet( codeSnippet );
		}
	}

	/**
	 * Render component.
	 *
	 * @return {JSX.Element} CodeSnippet component.
	 */
	render() {
		return (
			<pre className="sui-code-snippet">
				{ this.props.code }
			</pre>
		);
	}
}

CodeSnippet.propTypes = {
	code: PropTypes.string,
};
