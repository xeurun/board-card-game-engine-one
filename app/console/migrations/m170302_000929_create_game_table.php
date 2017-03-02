<?php

use yii\db\Migration;

/**
 * Handles the creation for table `game`.
 */
class m170302_000929_create_game_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('game', [
            'id' => $this->primaryKey()->unsigned(),
        ]);

        $this->createTable('turn', [
            'game_id' => $this->integer(11)->unsigned()->notNull(),
            'card_number' => $this->integer(11)->unsigned()->notNull(),
        ]);

        $this->addForeignKey(
            'FK_turn_game',
            'turn',
            'game_id',
            'game',
            'id',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('turn');
        $this->dropTable('game');
    }
}
