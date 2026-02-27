<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCheckItemRequestFunction extends Migration
{
    public function up()
    {
        $sql = "CREATE FUNCTION IF NOT EXISTS `check_item_request`(
            `xcharge_id` INT,
            `xcharge_item_id` INT
        )
        RETURNS varchar(50) CHARSET utf8mb3 COLLATE utf8mb3_unicode_ci
        LANGUAGE SQL
        NOT DETERMINISTIC
        CONTAINS SQL
        SQL SECURITY DEFINER
        COMMENT ''
        BEGIN
            Declare xReturn varchar(50) default '0#0';
            Declare xNo_Count,xStatus varchar(5) default '0';
            
            select count(*) into xNo_Count from lab_request  where charge_id=xcharge_id and charge_item_id=xcharge_item_id;
            
            if xNo_Count>0 then
                select status into xStatus from lab_request  where charge_id=xcharge_id and charge_item_id=xcharge_item_id;
            End if;
            
            Set xReturn=Concat(xNo_Count,';',xStatus);
            
            return xReturn;
        END";

        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query("DROP FUNCTION IF EXISTS `check_item_request`");
    }
}
