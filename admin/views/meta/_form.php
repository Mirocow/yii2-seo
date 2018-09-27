<?php

use mirocow\seo\Module;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model mirocow\seo\models\Meta */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="meta-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'key')->textInput(['maxlength' => true])
        ->hint('Example: http://sile.loc/about, about, about/page-1.html', ['class'=>'form-text text-muted']) ?>

    <?= $form->field($model, 'name')
        ->dropDownList(Module::getMetaFields(null, false),['prompt'=>'Select meta name'])
        ->hint('', ['class'=>'form-text text-muted']) ?>

    <?= $form->field($model, 'content')->textInput(['maxlength' => true])
        ->hint('', ['class'=>'form-text text-muted']) ?>

    <?= $form->field($model, 'lang')->textInput(['maxlength' => true])
        ->hint('', ['class'=>'form-text text-muted']) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
