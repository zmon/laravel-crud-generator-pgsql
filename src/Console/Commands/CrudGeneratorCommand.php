<?php

namespace CrudGenerator\Console\Commands;

use Illuminate\Container\Container;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use DB;
use Artisan;

class CrudGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {model-name} {--force} {--singular} {--table-name=} {--display-name=} {--grid-columns=} {--master-layout=} {--custom-controller=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create fully functional CRUD code based on a mysql table instantly';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $modelname = strtolower($this->argument('model-name'));
        $prefix = \Config::get('database.connections.mysql.prefix');
        $custom_table_name = $this->option('table-name');
        $custom_controller = $this->option('custom-controller');
        $singular = $this->option('singular');
        $custom_display_name = $this->option('display-name');
        $grid_columns = $this->option('grid-columns');

        $tocreate = [];

        if($modelname == 'all') {
            $pretables = json_decode(json_encode(DB::select("show tables")), true);
            $tables = [];
            foreach($pretables as $p) {
                list($key) = array_keys($p);
                $tables[] = $p[$key];
            }
            $this->info("List of tables: ".implode($tables, ","));

            foreach ($tables as $t) {
                // Ignore tables with different prefix
                if($prefix == '' || str_contains($t, $prefix)) {
                    $t = strtolower(substr($t, strlen($prefix)));
                    $toadd = ['modelname'=> str_singular($t), 'tablename'=>''];
                    if(str_plural($toadd['modelname']) != $t) {
                        $toadd['tablename'] = $t;
                    }
                    $tocreate[] = $toadd;
                }
            }
            // Remove options not applicabe for multiples tables
            $custom_table_name = null;
            $custom_controller = null;
            $singular = null;
        }
        else {

            $tocreate = [
                'modelname' => $modelname,
                'tablename' => $modelname,
            ];
            if($singular) {
                $tocreate['tablename'] = strtolower($modelname);
            }
            else if($custom_table_name) {
                $tocreate['tablename'] = $custom_table_name;
            }

            $tocreate = [$tocreate];
        }



        foreach ($tocreate as $c) {
            $generator = new \CrudGenerator\CrudGeneratorService();
            $generator->output = $this;

            $generator->appNamespace = Container::getInstance()->getNamespace();
            $generator->modelName = ucfirst(Str::camel(Str::singular($c['modelname'])));
            $generator->tableName = $c['tablename'];
            $generator->displayName = $custom_display_name ? $custom_display_name : $this->make_display_name($c['tablename']);

            $generator->prefix = $prefix;
            $generator->force = $this->option('force');
            $generator->layout = $this->option('master-layout');
            $generator->controllerName = ucfirst(strtolower($custom_controller)) ?: ucfirst(Str::camel(Str::singular($generator->modelName)));

            $generator->gridColumns = $grid_columns ? explode(':',$grid_columns) : [];
            $generator->Generate();
        }

    }

    function make_display_name( $name ) {
        return (ucwords(str_replace('_', ' ', str_singular($name))));
    }


}




















