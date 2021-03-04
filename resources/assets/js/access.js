$(".develop").click(function(){
    var cookie_key = $(this).attr('data-cookie-key');

    var parents = $(this).parents('.card-header');
    var content =  parents.siblings('.metric-content');
    if(!$(this).attr('data-slide')){
        var count = content.find('.xie-count').text();
        var span_tmp = '<span class="span-tmp-count" style="font-size: 1.2rem"> '+count+' </span>'

        parents.parents('.card').css("min-height",'57px');
        content.slideUp(500,function(){
            parents.find('.card-title').parent().after(span_tmp);
        });
        $(this).attr('data-slide',1);
        $.removeCookie(cookie_key)
    }else{
        parents.find('.span-tmp-count').hide();
        content.slideDown(500);
        $(this).removeAttr('data-slide');

        $.cookie(cookie_key, '1', { expires: 7 })
    }

});

$(".develop").each(function(){
    var cookie_key = $(this).attr('data-cookie-key');
    var is_open = $.cookie(cookie_key);
    console.log(is_open);
    var parents = $(this).parents('.card-header');
    var content =  parents.siblings('.metric-content');
    if(is_open){
        parents.find('.span-tmp-count').hide();
        content.show();
        $(this).removeAttr('data-slide');
    }else{
        var count = content.find('.xie-count').text();
        var span_tmp = '<span class="span-tmp-count" style="font-size: 1.2rem"> '+count+' </span>'

        parents.parents('.card').css("min-height",'57px');
        content.hide();
        parents.find('.card-title').parent().after(span_tmp);
        $(this).attr('data-slide',1);
    }

});

function checkAutoUpdate(){
    $('.xie-count').each(function(){
        $(this).parents('.metric-content').siblings('.card-header').find('.span-tmp-count').text($(this).text());
    });
}
