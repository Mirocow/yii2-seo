<?php

use yii\db\Migration;

/**
 * Handles the creation of table `seo_meta`.
 */
class m171103_002458_create_table_seo_meta extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('{{%seo_meta}}', [
          'key' => $this->string(255),
          'name' => $this->string(255),
          'content' => $this->string(255),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('{{%seo_meta}}');
    }
}
