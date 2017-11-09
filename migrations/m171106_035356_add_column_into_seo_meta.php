<?php

use yii\db\Migration;

/**
 * Handles the creation of table `seo_meta`.
 */
class m171106_035356_add_column_into_seo_meta extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->addColumn('{{%seo_meta}}', 'lang', $this->char(5));
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('{{%seo_meta}}');
    }
}
