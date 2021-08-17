/* global wphbGlobal */
/* global ajaxurl */

( function () {
	'use strict';

	const WPHBLazyComment = {
		commentsFormLoading : false,
		commentsLoading: false,
		commentsLoadComplete: false,
		commentsLoaded: 0,
		loadOnScroll: false,
		loadCommentFormOnScroll : false,
		commentLoadingMethod: 'click', //or scroll
		pageCommentsOption: 0,
		totalCommentsPages: 1,
		commentsPageOrder: 'newest', //or oldest
		cpageNum: 1,
		postId: 0,
		commentNonce: '',
		commentsContainer: null,
		commentList: null,
		loadCommentsButton: null,
		loadCommentsButtonWrap: null,
		commentsEndIndicator: null,
		commentsLoadSpinnerWrap: null,
		ajaxurl: null,

		init() {
			if ( wphbGlobal ) {
				this.ajaxurl = wphbGlobal.ajaxurl;
			} else {
				this.ajaxurl = ajaxurl;
			}

			// Run comment lazy loader if we are in site fron-end and in a single page.
			if ( document.getElementById( 'wphb-comments-wrap' ) ) {
				this.initCommentLazyLoader();
			}
		},

		initCommentLazyLoader() {
			this.commentLoadingMethod = document.getElementById(
				'wphb-load-comments-method'
			).value;

			this.pageCommentsOption = parseInt(
				document.getElementById( 'wphb-page-comments-option' ).value
			);

			this.totalCommentsPages = parseInt(
				document.getElementById( 'wphb-total-comments-pages' ).value
			);

			this.commentsPageOrder = document.getElementById(
				'wphb-comments-page-order'
			).value;

			this.cpageNum = parseInt(
				document.getElementById( 'wphb-cpage-num' ).value
			);

			this.postId = parseInt(
				document.getElementById( 'wphb-post-id' ).value
			);

			this.commentNonce = document.getElementById(
				'comment-template-nonce'
			).value;

			this.commentsContainer = document.getElementById(
				'wphb-comments-container'
			);

			this.commentsEndIndicator = document.getElementById(
				'wphb-comments-end-indicator'
			);

			this.commentsLoadSpinnerWrap = document.getElementById(
				'wphb-load-comments-spinner-wrap'
			);

			if ( this.commentLoadingMethod === 'click' ) {
				this.loadCommentsButton = document.getElementById(
					'wphb-load-comments'
				);
				this.loadCommentsButtonWrap = document.getElementById(
					'wphb-load-comments-button-wrap'
				);
			}

			//If we've the load on click enabled
			if ( this.commentLoadingMethod === 'click' ) {
				this.loadCommentsButton.addEventListener( 'click', () =>
					WPHBLazyComment.loadComments()
				);
				//At the very beginning load the comment form and basic wrappers
				this.loadCommentFormOnScroll = true;
				window.addEventListener( 'scroll', WPHBLazyComment.handleScrollingForLoadForm );
				//For some small posts comment area might be in view port on page load. 
				//So try to run loadComments on page load. 
				WPHBLazyComment.handleScrollingForLoadForm();
			}

			//If we've the load on scroll enabled
			if ( this.commentLoadingMethod === 'scroll' ) {
				this.loadOnScroll = true;
				window.addEventListener( 'scroll', () =>
					WPHBLazyComment.handleScrolling()
				);
				//For some small posts comment area might be in view port on page load. 
				//So try to run loadCommentsForm on page load. 
				WPHBLazyComment.handleScrolling();
			}
		},

		enableCommentLoad() {
			if ( this.loadCommentsButton ) {
				this.loadCommentsButton.removeAttribute( 'disabled' );
			}

			this.loadOnScroll = true;
		},

		disableCommentLoad() {
			if ( this.loadCommentsButton ) {
				this.loadCommentsButton.setAttribute( 'disabled', 'disabled' );
			}

			this.loadOnScroll = false;
			this.loadCommentFormOnScroll = false;
		},

		moveLoadButton() {
			if ( this.loadCommentsButton ) {
				this.insertAfter(
					this.loadCommentsButtonWrap,
					this.commentList
				);
			}
		},

		moveCommentsEndIndicator() {
			this.insertAfter( this.commentsEndIndicator, this.commentList );
		},

		moveCommentForm() {
			const form = document.getElementById( 'respond' );
			if ( form ) {
				this.insertBefore( form, this.commentList );
			}
		},

		hideLoadButton() {
			if ( this.loadCommentsButton ) {
				this.loadCommentsButton.style.display = 'none';
			}
		},

		showCommentLoadSpinner() {
			this.commentsLoadSpinnerWrap.style.display = 'block';
		},

		hideCommentLoadSpinner() {
			this.commentsLoadSpinnerWrap.style.display = 'none';
		},

		moveCommentLoadSpinner() {
			this.insertAfter( this.commentsLoadSpinnerWrap, this.commentList );
		},

		finishCommentLoad() {
			this.commentsLoading = false;
			this.disableCommentLoad();
			this.hideLoadButton();
		},

		handleScrolling() {
			/** Check if in viewport and loadOnScroll is enabled **/
			if (
				! this.loadOnScroll ||
				! this.isInViewport( this.commentsEndIndicator )
			) {
				return null;
			}

			this.loadComments();
		},

		handleScrollingForLoadForm() {
			/** Check if in viewport and loadCommentFormOnScroll is enabled **/
			if (
				! WPHBLazyComment.loadCommentFormOnScroll ||
				! WPHBLazyComment.isInViewport( WPHBLazyComment.commentsEndIndicator )
			) {
				return null;
			}
			window.removeEventListener( 'scroll', WPHBLazyComment.handleScrollingForLoadForm );
			
			WPHBLazyComment.loadCommentsForm();
		},

		loadCommentsForm() {
			this.commentsFormLoading = true;
			this.loadComments();
		},

		loadComments() {
			if ( this.commentsLoadComplete || this.commentsLoading ) {
				return false;
			}

			this.getCommentsTemplate();
		},

		getCommentsTemplate() {
			this.disableCommentLoad();
			this.showCommentLoadSpinner();
			const cxhr = new XMLHttpRequest();

			this.commentsLoading = true;
			cxhr.open(
				'GET',
				WPHBLazyComment.ajaxurl +
					'?action=get_comments_template&id=' +
					WPHBLazyComment.postId +
					'&cpage_num=' +
					WPHBLazyComment.cpageNum +
					'&_nonce=' +
					WPHBLazyComment.commentNonce
			);

			cxhr.onload = function () {
				/** Append the comment template **/
				if ( 200 === cxhr.status ) {
					const response = JSON.parse( cxhr.responseText );
					if ( 'undefined' !== typeof response.data ) {
						/** Append the comment template/ Error message returned in Ajax response **/
						if( WPHBLazyComment.commentsFormLoading === true ) {
							WPHBLazyComment.enableCommentLoad();

						} else {

							WPHBLazyComment.commentsLoaded++;
							WPHBLazyComment.cpageNum =
							WPHBLazyComment.commentsPageOrder === 'newest'
								? WPHBLazyComment.cpageNum - 1
								: WPHBLazyComment.cpageNum + 1;
							if (
								WPHBLazyComment.cpageNum < 1 ||
								WPHBLazyComment.cpageNum >
									WPHBLazyComment.totalCommentsPages
							) {
								WPHBLazyComment.finishCommentLoad();
							} else {
								WPHBLazyComment.enableCommentLoad();
							}
						}

						WPHBLazyComment.putCommentContent(
							response.data.content
						);
						
						if( WPHBLazyComment.commentsFormLoading === true ) {
							WPHBLazyComment.commentsFormLoading = false;
						}
						
					} else {
						WPHBLazyComment.enableCommentLoad();
					}
				} else {
					/** Show the error, if failed; Enable the button, scroll loading **/
					WPHBLazyComment.enableCommentLoad();
				}
				WPHBLazyComment.commentsLoading = false;
				WPHBLazyComment.hideCommentLoadSpinner();
			};

			cxhr.send();
		},

		putCommentContent( content ) {
			const html = this.stringToHTML( content );
			const comment = html.querySelector( '.comment' );

			if ( ! comment ) {
				return;
			}

			const commentList = comment.parentNode;

			if( WPHBLazyComment.commentsFormLoading === true ) {
				commentList.innerHTML = '';
			}

			if ( this.commentsLoaded > 1 || ( this.commentLoadingMethod === 'click' && WPHBLazyComment.commentsFormLoading === false ) ) {
				this.commentList.innerHTML += commentList.innerHTML;
			} else {
				this.commentsContainer.appendChild( html );
				this.commentList = commentList;
				this.moveCommentsEndIndicator();
				this.moveCommentLoadSpinner();
				this.moveLoadButton();
				if ( this.commentLoadingMethod === 'scroll' ) {
					this.moveCommentForm();
				}
			}
		},

		stringToHTML( str ) {
			str = '<div>' + str + '</div>';
			const support = ( function () {
				if ( ! window.DOMParser ) return false;
				const parser = new DOMParser();
				try {
					parser.parseFromString( 'x', 'text/html' );
				} catch ( err ) {
					return false;
				}
				return true;
			} )();

			//If DOMParser is supported, use it
			if ( support ) {
				const parser = new DOMParser();
				const doc = parser.parseFromString( str, 'text/html' );
				return doc.body.firstChild;
			}

			// Otherwise, fallback to old-school method
			const dom = document.createElement( 'div' );
			dom.innerHTML = str;
			return dom.firstChild;
		},

		insertAfter( newNode, referenceNode ) {
			referenceNode.parentNode.insertBefore(
				newNode,
				referenceNode.nextSibling
			);
		},

		insertBefore( newNode, referenceNode ) {
			referenceNode.parentNode.insertBefore( newNode, referenceNode );
		},

		isInViewport( element ) {
			const bounding = element.getBoundingClientRect();
			return (
				bounding.top >= 0 &&
				bounding.left >= 0 &&
				bounding.bottom <=
					( window.innerHeight ||
						document.documentElement.clientHeight ) &&
				bounding.right <=
					( window.innerWidth ||
						document.documentElement.clientWidth )
			);
		},
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		WPHBLazyComment.init();
	} );
} )();
