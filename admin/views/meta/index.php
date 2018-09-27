<?php

use mirocow\seo\Module;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel mirocow\seo\models\MetaSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Metas';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="meta-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Meta', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'key',
            [
                'attribute' => 'name',
                'filter' => Module::getMetaFields(null, false),
            ],
            'content',
            'lang',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update}{delete}',
                'headerOptions' => ['width' => '50px'],
            ],
        ],
    ]); ?>
</div>
