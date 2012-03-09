jQuery(document).ready(function(){
    jQuery('li.all a').removeClass('current').find('span.count').html('(' + cd_apd.count + ')');
    jQuery('.wp-list-table.plugins tr').each(function(){
        var is_active = jQuery(this).find('a.cd-apd-deactivate');
        if(is_active.length) {
            jQuery(this).removeClass('inactive').addClass('active');
            jQuery(this).find('div.plugin-version-author-uri').removeClass('inactive').addClass('active');
        }
    });
});
