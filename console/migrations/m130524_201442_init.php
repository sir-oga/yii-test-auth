<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        // reference m160321_163614_fitline_and_fitlinesearch_options
        $sql = "
            CREATE TABLE `user` (
              `id` int(11) UNSIGNED NOT NULL,
              `email` varchar(255) NOT NULL,
              `auth_key` varchar(32) NOT NULL,
              `password_hash` varchar(255) NOT NULL,
              `password_reset_token` varchar(255) DEFAULT NULL,
              `type` int(11) NOT NULL,
              `status` smallint(6) NOT NULL DEFAULT '0',
              `created_at` int(11) NOT NULL,
              `updated_at` int(11) NOT NULL,
              `avatar` varchar(255) DEFAULT NULL,
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            ALTER TABLE `user`
              ADD PRIMARY KEY (`id`),
              ADD UNIQUE KEY `email` (`email`),
              ADD UNIQUE KEY `password_reset_token` (`password_reset_token`);
            
            ALTER TABLE `user`
              MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
            
            CREATE TABLE `user_token` (
              `id` int(10) UNSIGNED NOT NULL,
              `user_id` int(10) UNSIGNED NOT NULL,
              `access_token` varchar(32) DEFAULT NULL ,
              `user_agent` varchar(32) DEFAULT NULL ,
              `life_time` int(10) UNSIGNED NOT NULL DEFAULT '0' ,
              `logged_at` int(10) UNSIGNED NOT NULL DEFAULT '0',
              `last_activity_at` int(10) UNSIGNED NOT NULL DEFAULT '0',
              `last_reset_at` int(10) UNSIGNED NOT NULL DEFAULT '0' ,
              `token_updated_at` int(10) UNSIGNED NOT NULL,
              `push_dev_token` varchar(255) DEFAULT NULL,
              `push_options` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
              `push_type` tinyint(3) UNSIGNED DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            ALTER TABLE `user_token`
              ADD PRIMARY KEY (`id`),
              ADD UNIQUE KEY `access_token_UNIQUE` (`access_token`),
              ADD KEY `fk_user_token_user1_idx` (`user_id`);
            
            ALTER TABLE `user_token`
              MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
            ALTER TABLE `user_token`
              ADD CONSTRAINT `fk_user_token_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

";

        $this->execute($sql);
    }

    public function down()
    {
        $sql = "
                  ALTER TABLE `user` DROP FOREIGN KEY `fk_user_token_user1`;
                  ALTER TABLE `user` DROP INDEX `fk_user_token_user1_idx`;
                  DROP TABLE IF EXISTS `user_token`;
                  DROP TABLE IF EXISTS `user`;
                ";

        $this->execute($sql);
    }
}
