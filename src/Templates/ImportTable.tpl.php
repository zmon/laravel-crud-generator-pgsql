<?php
/**
 * Created by PhpStorm.
 * User: paulb
 * Date: 2019-12-24
 * Time: 21:18
 */


namespace App\Lib\Import;

use App\Lib\Import\GetDbColumns;

use App\[[model_uc]];

use DB;

class Import[[model_uc]]
{

    var $fields = [

        [[foreach:columns]]
        "[[i.name]]" => ["name" => "[[i.name]]"],
    [[endforeach]]

//        "created_at" => ["name" => "created_at"],
//        "created_by" => ["name" => "created_by"],
//        "updated_at" => ["name" => "updated_at"],
//        "modified_by" => ["name" => "modified_by"],
//        "purged_by" => ["name" => "purged_by"],
    ];


    function import($database, $tablename)
    {

        print "Importing $tablename\n";

        DB::unprepared('SET session_replication_role = \'replica\';');  // Turns constraint checks off.

        $records = DB::connection($database)->select("select * from " . $tablename);

        $count = 0;
        foreach ($records AS $record) {
            //if ($count++ > 5) die;

            $new_rec = $this->clean($record);

            $Org = new [[model_uc]]();
            $Org->forceCreate($new_rec)->save();

        }

        $max_id = [[model_uc]]::max('id') + 1;
        DB::statement('ALTER SEQUENCE  ' . $tablename . '_id_seq RESTART WITH ' . $max_id);

        DB::unprepared('SET session_replication_role = \'origin\';');  // Turns them back on

    }

    function clean($record)
    {
        $data = [];
        foreach ($this->fields AS $org_name => $field) {
            $data[$field['name']] = $record->$org_name;
        }

        return $data;
    }

}
