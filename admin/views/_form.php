<?php

use mirocow\seo\Module;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use mirocow\seo\models\Meta;

/* @var $this yii\web\View */
/* @var $model mirocow\seo\models\Meta */
/* @var $seo mirocow\seo\components\MetaFieldsBehavior */
$seo = $model->getBehavior('seo');

if (empty($seo) || !$seo->userCanEdit) {
    return;
}
?>
<fieldset>
    <legend>SEO-oriented settings</legend>
    <?php

    if (!empty($seo->seoUrl)) {
        if ($form instanceof ActiveForm) {
            echo $form->field($model, 'seourl')->textInput();
        } else {
            echo '<div class="seo_row">';
            echo Html::activeLabel($model, 'seourl');
            echo Html::activeTextInput($model, 'seourl');
            echo Html::error($model, 'seourl');
            echo '</div>';
        }
    }

    foreach (Module::getMetaFields() as $attr) {

        $label = Module::keyToName($attr);

        if ($form instanceof ActiveForm) {
            $input = ($attr == Meta::KEY_DESCRIPTION) ? 'textarea' : 'textInput';
            echo $form->field($model, $attr)->label($label)->$input();
        } else {
            $input = ($attr == Meta::KEY_DESCRIPTION) ? 'activeTextarea' : 'activeTextInput';
            echo '<div class="seo_row">';
            echo Html::activeLabel($model, $attr, [
                'label' => $label
            ]);
            echo Html::$input($model, $attr);
            echo Html::error($model, $attr);
            echo '</div>';
        }

    }
    ?>
</fieldset>