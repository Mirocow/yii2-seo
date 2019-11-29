<?php

use mirocow\seo\models\Meta;
use mirocow\seo\Module;
use yii\grid\GridView;
use yii\helpers\Html;

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
            //['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'attribute' => 'key',
                'headerOptions' => ['width' => '150px'],
            ],
            [
                'attribute' => 'name',
                'filter' => Module::getMetaFields(null, false),
                'value'     => function (Meta $model, $key, $index, $widget) {
                    return Module::keyToName($model->name);
                },
            ],
            'content',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update}{delete}',
                'headerOptions' => ['width' => '50px'],
            ],
        ],
    ]); ?>
</div>
