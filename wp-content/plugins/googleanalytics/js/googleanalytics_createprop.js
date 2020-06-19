(
  function ( $, wp ) {
    $( document ).ready( function () {
      var theData = JSON.stringify( {
        onboarding_product: 'ga',
        domain: gasiteURL,
        email: gaAdminEmail,
        is_wordpress: true
      } );

      $.ajax( {
        url: 'https://platform-api.sharethis.com/v1.0/property',
        method: 'POST',
        async: false,
        contentType: 'application/json; charset=utf-8',
        data: theData,
        success: function ( result ) {
          setGACredentials( result.secret, result._id );
        }
      } );
    } );

    /**
     * WP Ajax call to set prop id/secret
     */
    function setGACredentials(secret, propid) {
      wp.ajax.post( 'set_ga_credentials', {
        secret: secret,
        propid: propid,
        nonce: gaNonce
      } ).always( function( results ) {
      });
    }
  }
)( window.jQuery, window.wp );
