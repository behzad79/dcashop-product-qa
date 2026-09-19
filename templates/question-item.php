<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$table = $wpdb->prefix . 'product_questions';

$answers = $wpdb->get_results(

    $wpdb->prepare(

        "SELECT *
        FROM {$table}
        WHERE parent_id=%d
        AND status='approved'
        ORDER BY created_at ASC",
        $question->id

    )

);

?>

<div class="dcqa-question">

    <div class="dcqa-question-header">

        <?php

$user = get_userdata($question->user_id);

$roles = $user ? (array) $user->roles : array();

$is_official = (
    in_array('administrator', $roles, true) ||
    in_array('shop_manager', $roles, true) ||
    in_array('editor', $roles, true)
);

?>
<div class="dcqa-user">

<strong>
    <?php
    echo esc_html(
        !empty($question->display_name)
            ? $question->display_name
            : dcqa_get_user_name($question->user_id)
    );
    ?>
</strong>

</div>
        <span class="dcqa-date">

            <?php

            echo esc_html(
                dcqa_get_date(
                    $question->created_at
                )
            );

            ?>

        </span>

    </div>

    <div class="dcqa-question-content">

        <?php echo nl2br(esc_html($question->content)); ?>

    </div>

    <?php if ($answers): ?>

        <div class="dcqa-answers">

            <?php foreach ($answers as $answer): ?>

                <?php

                $user = get_userdata($answer->user_id);

                $roles = $user ? (array) $user->roles : array();

                $is_official = (
                    in_array('administrator', $roles, true) ||
                    in_array('shop_manager', $roles, true) ||
                    in_array('editor', $roles, true)
                );

                ?>

                <div class="dcqa-answer">

                    <div class="dcqa-answer-header">

                       <div class="dcqa-answer-author">

<strong>
    <?php
    echo esc_html(
        !empty($answer->display_name)
            ? $answer->display_name
            : dcqa_get_user_name($answer->user_id)
    );
    ?>
</strong>

    <?php if ($is_official): ?>

        <span class="dcqa-official-badge">

            ✔ پاسخ رسمی دکاشاپ

        </span>

    <?php endif; ?>

</div>

                        <span class="dcqa-date">

                            <?php echo esc_html(dcqa_get_date($answer->created_at)); ?>

                        </span>

                    </div>

                    <div class="dcqa-answer-content">

                        <?php

                        echo nl2br(
                            esc_html(
                                $answer->content
                            )
                        );

                        ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <div class="dcqa-question-footer">

        <button
            class="dcqa-answer-btn"
            data-question="<?php echo intval($question->id); ?>"
            data-text="<?php echo esc_attr($question->content); ?>">

            پاسخ به این پرسش

        </button>

    </div>

</div>