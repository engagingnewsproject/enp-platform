( function( $ ) {
	'use strict'

	$( function() {
		$( '.rank-math-mark-solved a' ).on( 'click', function( e ) {
			e.preventDefault()
			const $this = $( this )
			const isSolved = $this.data( 'is-solved' )
			$.ajax( {
				url: rankMath.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'rank_math_mark_answer_solved',
					security: rankMath.security,
					topic: $this.data( 'topic-id' ),
					reply: $this.data( 'id' ),
					isSolved,
				},
			} ).done( function() {
				if ( isSolved ) {
					$this.text( $this.data( 'unsolved-text' ) )
					$( '.rank-math-mark-solved.rank-math-hidden' ).removeClass( 'rank-math-hidden' )
					return
				}

				$( '.rank-math-mark-solved' ).addClass( 'rank-math-hidden' )
				$this.parent().removeClass( 'rank-math-hidden' )
				$this.text( $this.data( 'solved-text' ) )
			} )

			return false
		} )
	} )
}( jQuery ) )
