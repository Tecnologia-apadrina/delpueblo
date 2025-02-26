jQuery( function( $ ) {

    load_dates();
    $(document.body).on('updated_cart_totals updated_checkout', load_dates);

    function load_dates() {
        $('div.pre_order_on_cart').each(function () {
            var unix_time = parseInt($(this).data('time'));
            var date = new Date(0);
            date.setUTCSeconds(unix_time);
            var time = date.toLocaleTimeString();
            time = time.slice(0, -3);
            $(this).find('.availability_date').text(date.toLocaleDateString());
            $(this).find('.availability_time').text(time);
        });
    }
});