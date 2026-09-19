jQuery(function ($) {


    /*
    |--------------------------------------------------------------------------
    | باز کردن فرم پاسخ در پیشخوان
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.dcqa-admin-reply-btn',
        function () {

            var questionID = $(this).data('question');

            var row = $('#dcqa-reply-' + questionID);

            $('.dcqa-admin-reply-row')
                .not(row)
                .hide();

            row.toggle();

            if (row.is(':visible')) {

                row.find('.dcqa-admin-reply-content')
                    .focus();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | لغو
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.dcqa-admin-reply-cancel',
        function () {

            var row = $(this).closest(
                '.dcqa-admin-reply-row'
            );

            row.hide();

            row.find('.dcqa-admin-reply-content')
                .val('');

            row.find('.dcqa-admin-reply-message')
                .html('');

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ثبت پاسخ از داخل پیشخوان
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.dcqa-admin-reply-submit',
        function () {

            var button = $(this);

            var questionID = button.data('question');

            var row = $('#dcqa-reply-' + questionID);

            var textarea = row.find(
                '.dcqa-admin-reply-content'
            );

            var message = row.find(
                '.dcqa-admin-reply-message'
            );

            var nonce = row.find(
                '.dcqa-admin-reply-nonce'
            ).val();

            var content = textarea.val();


            /*
            |--------------------------------------------------------------------------
            | بررسی متن
            |--------------------------------------------------------------------------
            */

            if (!content || !content.trim()) {

                message
                    .html('لطفاً متن پاسخ را وارد کنید.')
                    .css('color', '#d63638');

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | جلوگیری از چند بار کلیک
            |--------------------------------------------------------------------------
            */

            button.prop(
                'disabled',
                true
            );


            message
                .html('در حال ثبت...')
                .css('color', '');


            /*
            |--------------------------------------------------------------------------
            | AJAX
            |--------------------------------------------------------------------------
            */

            $.ajax({

                url: ajaxurl,

                type: 'POST',

                data: {

                    action: 'dcqa_admin_reply',

                    nonce: nonce,

                    question_id: questionID,

                    content: content

                },


                success: function (response) {


                    if (response.success) {


                        /*
                        | اضافه کردن پاسخ جدید
                        | همان لحظه داخل همان سوال
                        */

                        var answersBox = row
                            .prev('tr')
                            .find('.dcqa-admin-answers');


                        /*
                        | اگر قبلاً پاسخی وجود نداشته
                        */

                        if (!answersBox.length) {

                            row
                                .prev('tr')
                                .find('.dcqa-admin-question-text')
                                .after(

                                    '<div class="dcqa-admin-answers">' +
                                    '<strong>پاسخ‌ها:</strong>' +
                                    '</div>'

                                );

                            answersBox = row
                                .prev('tr')
                                .find('.dcqa-admin-answers');

                        }


                        answersBox.append(
                            response.data.html
                        );


                        /*
                        | پاک کردن فرم
                        */

                        textarea.val('');


                        /*
                        | پیام موفقیت
                        */

                        message
                            .html('پاسخ ثبت شد.')
                            .css('color', '#008a20');


                        /*
                        | بستن فرم بعد از ثبت
                        */

                        setTimeout(
                            function () {

                                row.hide();

                                message.html('');

                            },
                            1200
                        );


                    } else {


                        message
                            .html(
                                response.data.message
                            )
                            .css(
                                'color',
                                '#d63638'
                            );

                    }

                },


                error: function () {

                    message
                        .html(
                            'خطایی در ثبت پاسخ رخ داد.'
                        )
                        .css(
                            'color',
                            '#d63638'
                        );

                },


                complete: function () {

                    button.prop(
                        'disabled',
                        false
                    );

                }

            });

        }
    );


});