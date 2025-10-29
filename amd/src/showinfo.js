import jQuery from 'jquery';

export const init = () => {
    jQuery('.progress-block').mouseenter(function () {
        let ref = jQuery(this).data('info-ref');
        let userid = jQuery(this).data('user');

        jQuery('.progress-info').each(function (index) {
            let progressinfo = jQuery(this);
            if (progressinfo.data('user') == userid) {
                progressinfo.addClass('d-none');
            }
        });

        if (ref !== undefined) {
            jQuery('#' + ref).removeClass('d-none');
        }
    });

    jQuery('.progress-block').click(function () {
        let ref = jQuery(this).data('info-ref');
        if (ref !== undefined) {
            let a = jQuery('#' + ref).find('a').first();
            window.location = a.attr('href');
        }
    });
};
