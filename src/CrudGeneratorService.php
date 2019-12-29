<?php

namespace CrudGenerator;


use Illuminate\Console\Command;
use Illuminate\Support\Str;
use DB;
use Artisan;
use phpDocumentor\Reflection\File;

class CrudGeneratorService
{

    public $modelName = '';
    public $tableName = '';
    public $displayName = '';
    public $prefix = '';
    public $force = false;
    public $layout = '';
    public $existingModel = '';
    public $controllerName = '';
    public $viewFolderName = '';
    public $output = null;
    public $appNamespace = 'App';
    public $gridColumns = [];


    public function __construct()
    {

    }


    public function Generate()
    {

        $this->controllerName = $this->controllerName;


        $modelname = $this->modelName;
        $model_singular = strtolower(Str::singular($this->tableName));

        $this->viewFolderName = str_replace('_','-',$model_singular);

        $this->output->info('');
        $this->output->info('Creating catalogue for table: '.$this->tableName);
        $this->output->info('Model Name:     '.$modelname);
        $this->output->info('Controler Name: '.$this->controllerName);


        $options = [
            'display_name_singular' => $this->displayName,
            'display_name_plural' => str_plural($this->displayName),
            'model_uc' => $modelname,
            'model_uc_plural' => str_plural($modelname),
            'model_singular' => $model_singular,
            'model_plural' => strtolower(str_plural($modelname)),
            'tablename' => $this->tableName,
            'prefix' => $this->prefix,
            'custom_master' => $this->layout ?: 'crudgenerator::layouts.master',
            'controller_name' => $this->controllerName,
            'view_folder' => $this->viewFolderName,
            'route_path' => $this->viewFolderName,
            'appns' => $this->appNamespace,
            'display_uc_plural' => str_plural($modelname),
            'display_singular' => $modelname,


        ];


        if(!$this->force) {
            //if(file_exists(app_path().'/'.$modelname.'.php')) { $this->output->info('Model already exists, use --force to overwrite'); return; }
            if(file_exists(app_path().'/Http/Controllers/'.$this->controllerName.'Controller.php')) { $this->output->info('Controller already exists, use --force to overwrite'); return; }
            if(file_exists(base_path().'/resources/views/'.$this->viewFolderName.'/add.blade.php')) { $this->output->info('Add view already exists, use --force to overwrite'); return; }
            if(file_exists(base_path().'/resources/views/'.$this->viewFolderName.'/show.blade.php')) { $this->output->info('Show view already exists, use --force to overwrite'); return; }
            if(file_exists(base_path().'/resources/views/'.$this->viewFolderName.'/index.blade.php')) { $this->output->info('Index view already exists, use --force to overwrite');  return; }
        }


        $columns = $this->createModel($modelname, $this->prefix, $this->tableName);


        $options['columns'] = $columns;
        $options['grid_columns'] = count($this->gridColumns)  ? $this->makeGridColumns($columns) : $columns;
        $options['number_of_grid_columns'] = count($options['grid_columns']) + 1;
        $options['first_column_nonid'] = count($columns) > 1 ? $columns[1]['name'] : '';
        $options['num_columns'] = count($columns);

        dump($options);


        //###############################################################################
        if(!is_dir(base_path().'/resources/views/'.$this->viewFolderName)) {
            $this->output->info('Creating directory: '.base_path().'/resources/views/'.$this->viewFolderName);
            mkdir( base_path().'/resources/views/'.$this->viewFolderName);
        }


        $filegenerator = new \CrudGenerator\CrudGeneratorFileCreator();
        $filegenerator->options = $options;
        $filegenerator->output = $this->output;

        $filegenerator->templateName = 'controller';
        $filegenerator->path = app_path().'/Http/Controllers/'.$this->controllerName.'Controller.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'Api';
        $filegenerator->path = app_path().'/Http/Controllers/'.$this->controllerName.'Api.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'IndexRequest';
        $filegenerator->path = app_path().'/Http/Requests/'.$modelname.'IndexRequest.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'exports';
        $filegenerator->path = app_path().'/Exports/'.$modelname.'Export.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'FormRequest';
        $filegenerator->path = app_path().'/Http/Requests/'.$modelname.'FormRequest.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'model';
        $filegenerator->path = app_path().'/'.$modelname.'.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'view.create';
        $filegenerator->path = base_path().'/resources/views/'.$this->viewFolderName.'/create.blade.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'view.edit';
        $filegenerator->path = base_path().'/resources/views/'.$this->viewFolderName.'/edit.blade.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'view.show';
        $filegenerator->path = base_path().'/resources/views/'.$this->viewFolderName.'/show.blade.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'view.index';
        $filegenerator->path = base_path().'/resources/views/'.$this->viewFolderName.'/index.blade.php';
        $filegenerator->Generate();

        $filegenerator->templateName = 'view.print';
        $filegenerator->path = base_path().'/resources/views/'.$this->viewFolderName.'/print.blade.php';
        $filegenerator->Generate();

        // Put VueJS componets into a subdirectory
        $vue_subdir = base_path() . '/resources/js/components/' . $this->tableName;

        if (!file_exists($vue_subdir)) {
            mkdir($vue_subdir);
        }

        $filegenerator->templateName = 'Grid.vue';
        $filegenerator->path = $vue_subdir . '/'.$modelname.'Grid.vue';
        $filegenerator->Generate();
        exec("prettier --write " . $vue_subdir . '/'.$modelname.'Grid.vue');

        $filegenerator->templateName = 'Form.vue';
        $filegenerator->path = $vue_subdir . '/'.$modelname.'Form.vue';
        $filegenerator->Generate();
        exec("prettier --write " . $vue_subdir . '/'.$modelname.'Form.vue');


        $filegenerator->templateName = 'Show.vue';
        $filegenerator->path = $vue_subdir . '/'.$modelname.'Show.vue';
        $filegenerator->Generate();
        exec("prettier --write " . $vue_subdir . '/'.$modelname.'Show.vue');

        $filegenerator->templateName = 'ControllerTest';
        $filegenerator->path = base_path().'/tests/Feature/'.$modelname.'ControllerTest.php';
        $filegenerator->Generate();



        //###############################################################################


        // ### VUE JS ###

//        $addvue = "//Vue.component('" . $this->viewFolderName . "-grid',       require('./components/" . $modelname . "Grid.vue'));    // May need to add .default);";
//        $this->appendToEndOfFile(base_path().'/resources/js/components.js', "\n".$addvue, 0, true);
//
//        $addvue = "//Vue.component('" . $this->viewFolderName . "-form',       require('./components/" . $modelname . "Form.vue'));    // May need to add .default);";
//        $this->appendToEndOfFile(base_path().'/resources/js/components.js', "\n".$addvue, 0, true);

        $addvue = "Vue.component('" . $this->viewFolderName . "-grid', () => import(/* webpackChunkName:\"" . $this->viewFolderName . "-grid\" */ './components/".$this->tableName.'/' . $modelname . "Grid.vue'));";
        $this->appendToEndOfFile(base_path().'/resources/js/components.js', "\n".$addvue, 0, true);

        $addvue = "Vue.component('" . $this->viewFolderName . "-form', () => import(/* webpackChunkName:\"" . $this->viewFolderName . "-form\" */ './components/".$this->tableName.'/' . $modelname . "Form.vue'));";
        $this->appendToEndOfFile(base_path().'/resources/js/components.js', "\n".$addvue, 0, true);

        $addvue = "Vue.component('" . $this->viewFolderName . "-show', () => import(/* webpackChunkName:\"" . $this->viewFolderName . "-Show\" */ './components/".$this->tableName.'/' . $modelname . "Show.vue'));";
        $this->appendToEndOfFile(base_path().'/resources/js/components.js', "\n".$addvue, 0, true);


        $this->output->info('Adding Vue: '.$addvue );

        # $model_singular


        // ### ROUTES ###

        $addroute = 'Route::get(\'/api-'.$this->viewFolderName.'\', \''.$this->controllerName.'Api@index\');';
        $this->appendToEndOfFile(base_path().'/routes/web.php', "\n".$addroute, 0, true);
        $this->output->info('Adding Route: '.$addroute );

        $addroute = 'Route::get(\'/api-'.$this->viewFolderName.'/options\', \''.$this->controllerName.'Api@getOptions\');';
        $this->appendToEndOfFile(base_path().'/routes/web.php', "\n".$addroute, 0, true);
        $this->output->info('Adding Route: '.$addroute );

        $addroute = 'Route::get(\'/'.$this->viewFolderName.'/download\', \''.$this->controllerName.'Controller@download\')->name(\''.$this->viewFolderName.'.download\');';
        $this->appendToEndOfFile(base_path().'/routes/web.php', "\n".$addroute, 0, true);
        $this->output->info('Adding Route: '.$addroute );

        $addroute = 'Route::get(\'/'.$this->viewFolderName.'/print\', \''.$this->controllerName.'Controller@print\')->name(\''.$this->viewFolderName.'.print\');';
        $this->appendToEndOfFile(base_path().'/routes/web.php', "\n".$addroute, 0, true);
        $this->output->info('Adding Route: '.$addroute );

        $addroute = 'Route::resource(\'/'.$this->viewFolderName.'\', \''.$this->controllerName.'Controller\');';
        $this->appendToEndOfFile(base_path().'/routes/web.php', "\n".$addroute, 0, true);
        $this->output->info('Adding Route: '.$addroute );





    }

    protected function makeGridColumns($columns) {
        $columns = collect($columns);
        $grid_columns = $this->gridColumns;
        return $columns->filter(function ($value, $key) use ($grid_columns) {
            return in_array($value['name'],$grid_columns);
        })->all();

    }

    protected function getColumns($tablename) {
        $dbType = DB::getDriverName();
        switch ($dbType) {
            case "pgsql":
                $cols = DB::select("select column_name as Field, "
                                . "data_type as Type, "
                                . "is_nullable as Null "
                                . "from INFORMATION_SCHEMA.COLUMNS "
                                . "where table_name = '" . $tablename . "'");
                break;
            default:
                $cols = DB::select("show columns from " . $tablename);
                break;
        }

        $ret = [];
        foreach ($cols as $c) {
            $field = isset($c->Field) ? $c->Field : $c->field;
            $type = isset($c->Type) ? $c->Type : $c->type;
            $null = isset($c->Null) ? $c->Null : $c->null;

            $key = isset($c->Key) ? $c->Key : isset($c->key) ? $c->key : '' ;

            $primary_key = ($key = 'PRI' ? true : false);

            $default = isset($c->Default) ? $c->Default : isset($c->default) ? $c->default : '';

            if ($x = preg_match( "/\((\d+)\)/", $type, $out)) {
                $size = (int) $out[1];
            } else {
                $size = false;
            }

            $cadd = [];

            $cadd['name'] = $field;
            $cadd['type'] = $type = $field == 'id' ? 'id' : $this->getTypeFromDBType($type);
            $cadd['display'] = ucwords(str_replace('_', ' ', $field));


            $validation = '';

            switch ($field) {
                case 'created':
                case 'created_at':
                case 'created_by':
                case 'modified':
                case 'updated_at':
                case 'modified_by':
                case 'purged_by':
                case 'wid':
                break;

                case 'id':

                    $cadd['validation'] = 'numeric';

                    $ret[] = $cadd;
                    break;

                default:

                    switch ( $type ) {
                        case 'text':
                            $validation = "nullable|string";
                            if ( $size ) $validation .= "|max:$size";
                            break;
                        case 'number':
                            $validation = "nullable|numeric";
                            break;
                        case 'date':
                            $validation = "nullable|date";
                            if ( $size ) $validation .= "|max:$size";
                            break;
                        default:
                            $validation = "nullable|string";

                            if ( $size ) $validation .= "|max:$size";
                            break;
                    }

                    $cadd['validation'] = $validation;


                    $ret[] = $cadd;
                    break;
            }

        }

        return $ret;
    }

    protected function getTypeFromDBType($dbtype) {
        if(str_contains($dbtype, 'varchar')) { return 'text'; }
        if(str_contains($dbtype, 'char')) { return 'text'; }
        if(str_contains($dbtype, 'int') || str_contains($dbtype, 'float')) { return 'number'; }
        if(str_contains($dbtype, 'date')) { return 'date'; }
        return 'unknown';
    }



    protected function createModel($modelname, $prefix, $table_name) {

//        Artisan::call('make:model', ['name' => $modelname]);
//
//
//        if($table_name) {
//            $this->output->info('Custom table name: '.$prefix.$table_name);
//            $this->appendToEndOfFile(app_path().'/'.$modelname.'.php', "    protected \$table = '".$table_name."';\n\n}", 2);
//        }


        $columns = $this->getColumns($table_name);

//        $cc = collect($columns);
//
//        if(!$cc->contains('name', 'updated_at') || !$cc->contains('name', 'created_at')) {
//            $this->appendToEndOfFile(app_path().'/'.$modelname.'.php', "    public \$timestamps = false;\n\n}", 2, true);
//        }

        $this->output->info('Model created, columns: '.json_encode($columns));
        return $columns;
    }

    protected function deletePreviousFiles($tablename, $existing_model) {
        $todelete = [
                app_path().'/Http/Controllers/'.ucfirst($tablename).'Controller.php',
                base_path().'/resources/views/'.str_plural($tablename).'/index.blade.php',
                base_path().'/resources/views/'.str_plural($tablename).'/add.blade.php',
                base_path().'/resources/views/'.str_plural($tablename).'/show.blade.php',
            ];
        if(!$existing_model) {
            $todelete[] = app_path().'/'.ucfirst(str_singular($tablename)).'.php';
        }
        foreach($todelete as $path) {
            if(file_exists($path)) {
                unlink($path);
                $this->output->info('Deleted: '.$path);
            }
        }
    }

    protected function appendToEndOfFile($path, $text, $remove_last_chars = 0, $dont_add_if_exist = false) {
        $content = file_get_contents($path);
        if(!str_contains($content, $text) || !$dont_add_if_exist) {
            $newcontent = substr($content, 0, strlen($content)-$remove_last_chars).$text;
            file_put_contents($path, $newcontent);
        }
    }
}
