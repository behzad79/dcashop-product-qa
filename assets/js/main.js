jQuery(function ($) {

    console.log("DCQA Loaded");
    let dcqaQuestionID = 0;
    let dcqaQuestionText = '';

    // انتقال مودال به انتهای body
    $('#dcqa-modal').appendTo('body');

    function openModal() {
        $('#dcqa-modal').css('display', 'flex');
        $('body').addClass('dcqa-open');
    }

    function closeModal() {
        $('#dcqa-modal').hide();
        $('body').removeClass('dcqa-open');
    }

    // باز کردن مودال
    $(document).on('click', '.dcqa-ask-btn, .dcqa-mobile-trigger', function () {

        if (!dcqa.logged_in) {
            $('#dcqa-login-trigger').trigger('click');
            return;
        }

        openModal();

    });

    // بستن با ضربدر
    $(document).on('click', '.dcqa-close', function () {
        closeModal();
    });

    // بستن با کلیک روی پس زمینه
    $(document).on('click', '#dcqa-modal', function (e) {

        if ($(e.target).is('#dcqa-modal')) {
            closeModal();
        }

    });

    // تست Ajax
    $(document).on('click', '#dcqa-send', function () {

        $.ajax({

            url: dcqa.ajax_url.replace('%%endpoint%%', 'dcqa_submit_question'),

            type: 'POST',

            data: {

    nonce: dcqa.nonce,

    content: $('#dcqa-question').val(),

    product_id: dcqa.product_id

},

success: function (response) {


    if(response.success){


        $('.dcqa-message')
            .html(response.data.message)
            .addClass('success');


        $('#dcqa-question').val('');


        setTimeout(function(){

    closeModal();

    $('.dcqa-message')
        .html('')
        .removeClass('success');

    if (response.data.reload) {

        location.reload();

    }

},3000);


    } else {


        $('.dcqa-message')
            .html(response.data.message)
            .addClass('error');


    }


},

            error: function (xhr) {

                console.log(xhr);

            }

        });

    });
    
    /*
|--------------------------------------------------------------------------
| پاسخ
|--------------------------------------------------------------------------
*/

$('#dcqa-answer-modal').appendTo('body');

function openAnswerModal(){

    $('#dcqa-answer-modal').css('display','flex');

    $('body').addClass('dcqa-open');

}

function closeAnswerModal(){

    $('#dcqa-answer-modal').hide();

    $('body').removeClass('dcqa-open');

}

$(document).on('click','.dcqa-answer-btn',function(){

    if(!dcqa.logged_in){

        $('#dcqa-login-trigger').trigger('click');

        return;

    }

    dcqaQuestionID=$(this).data('question');

    dcqaQuestionText=$(this).data('text');

    $('.dcqa-answer-question').text(dcqaQuestionText);

    $('#dcqa-answer-content').val('');

    openAnswerModal();

});

$(document).on('click','.dcqa-answer-close',function(){

    closeAnswerModal();

});

$(document).on('click','#dcqa-answer-modal',function(e){

    if($(e.target).is('#dcqa-answer-modal')){

        closeAnswerModal();

    }

});

// ارسال پاسخ

$(document).on('click','#dcqa-answer-send',function(){

    let button = $(this);

    let content = $('#dcqa-answer-content').val();


    if(!content.trim()){

        alert('لطفاً پاسخ خود را بنویسید.');

        return;

    }


    button.prop('disabled',true);


    $.ajax({

        url: dcqa.ajax_url.replace('%%endpoint%%','dcqa_submit_answer'),

        type:'POST',

        data:{

            nonce: dcqa.nonce,

            question_id: dcqaQuestionID,

            content: content

        },


        success:function(response){

    if(response.success){

        $('.dcqa-answer-message')
            .html(response.data.message)
            .removeClass('error')
            .addClass('success');

        $('#dcqa-answer-content').val('');

        setTimeout(function(){

    closeAnswerModal();

    $('.dcqa-answer-message')
        .html('')
        .removeClass('success');

    if (response.data.reload) {

        location.reload();

    }

},2000);

    }else{

        $('.dcqa-answer-message')
            .html(response.data.message)
            .removeClass('success')
            .addClass('error');

    }

},


        error:function(xhr){

            console.log(xhr);

            alert('خطایی رخ داد.');

        },


        complete:function(){

            button.prop('disabled',false);

        }


    });


});

});