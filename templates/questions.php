<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$table = $wpdb->prefix . 'product_questions';

$questions = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT *
        FROM {$table}
        WHERE product_id=%d
        AND parent_id=0
        AND status='approved'
        ORDER BY created_at DESC",
        $product_id
    )
);

$question_count = count($questions);

$answer_count = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*)
        FROM {$table}
        WHERE product_id=%d
        AND parent_id!=0
        AND status='approved'",
        $product_id
    )
);

?>

<div class="dcqa-layout">

    <div class="dcqa-main">

        <div class="dcqa-header">

            <h3>

                پرسش و پاسخ

            </h3>

        </div>
        
        <div class="dcqa-questions-list">

        <?php

        if ($questions) {

            foreach ($questions as $question) {

                include DCQA_PATH . 'templates/question-item.php';

            }

        } else {

            ?>

            <div class="dcqa-empty">

                هنوز پرسشی ثبت نشده است.

            </div>

            <?php

        }

        ?>
    </div>
    </div>


    <aside class="dcqa-sidebar">

        <div class="dcqa-sidebar-box">

            <h4>

                شما هم درباره این کالا

                پرسش ثبت کنید

            </h4>

            <button class="dcqa-ask-btn">

                ثبت پرسش

            </button>

        </div>

    </aside>

</div>

<div class="dcqa-mobile-cta dcqa-mobile-trigger">

    <span class="dcqa-mobile-icon">

        ❓

    </span>

    <span class="dcqa-mobile-title">

        شما هم درباره این کالا سوال بپرسید

    </span>

    <span class="dcqa-mobile-arrow">

        ›

    </span>

</div>

<button
id="dcqa-login-trigger"
class="mobits_popup"
type="button"
style="display:none;">
</button>

<?php include DCQA_PATH . 'templates/popup-question.php'; ?>

<?php include DCQA_PATH . 'templates/popup-answer.php'; ?>