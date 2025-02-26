jQuery( function( $ ) {
    $( 'div.preorder-my-account' ).each( function () {
        var unix_time = parseInt( $( this ).data( 'time' ) );
        var date = new Date(0);
        date.setUTCSeconds( unix_time );
        var time = date.toLocaleTimeString();
        time = time.slice(0, -3);
        $( this ).find( '.preorder-date' ).text( date.toLocaleDateString() );
        $( this ).find( '.preorder-time' ).text( time );
    });
});