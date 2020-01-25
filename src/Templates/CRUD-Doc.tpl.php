# [[model_uc]] - `[[model_plural]]`

## After running Crud Generator


#### Setup Permissions in `app/Lib/InitialPermissons.php`

From the bottom of the file put these at the top in alpha order

```
        Permission::findOrCreate('[[model_singular]] index');
        Permission::findOrCreate('[[model_singular]] view');
        Permission::findOrCreate('[[model_singular]] export-pdf');
        Permission::findOrCreate('[[model_singular]] export-excel');
        Permission::findOrCreate('[[model_singular]] add');
        Permission::findOrCreate('[[model_singular]] edit');
        Permission::findOrCreate('[[model_singular]] delete');
```

From the bottom of the file, add these to admin

```
'[[model_singular]] index',
'[[model_singular]] view',
'[[model_singular]] export-pdf',
'[[model_singular]] export-excel',
'[[model_singular]] add',
'[[model_singular]] edit',
'[[model_singular]] delete',
```

From the bottom of the file, add these to read-only

```
        '[[model_singular]] index',
        '[[model_singular]] view',
```

Then run the following to install the permissions

```
php artisan lbv:set-initial-permissions
```

### Components

In `resource/js/components`

Remove

```
Vue.component('[[model_singular]]', require('./components/[[model_singular]].vue').default);
```

Add

```
Vue.component('[[view_folder]]', () => import(/* webpackChunkName:"[[view_folder]]" */ './components/[[tablename]]/[[view_folder]].vue'));
Vue.component('[[view_folder]]', () => import(/* webpackChunkName:"[[view_folder]]" */ './components/[[tablename]]/[[view_folder]].vue'));
Vue.component('[[view_folder]]', () => import(/* webpackChunkName:"[[view_folder]]" */ './components/[[tablename]]/[[view_folder]].vue'));

```

#### Add to the menu in `resources/views/layouts/crud-nav.blade.php`

##### Menu

```
@can(['[[model_singular]] index'])
<li class="nav-item @php if(isset($nav_path[0]) && $nav_path[0] == '[[model_singular]]') echo 'active' @endphp">
    <a class="nav-link" href="{{ route('[[model_singular]].index') }}">[[display_name_singular]] <span
            class="sr-only">(current)</span></a>
</li>
@endcan
```

##### Sub Menu

```
@can(['[[model_singular]] index'])
<a class="dropdown-item @php if(isset($nav_path[1]) && $nav_path[1] == '[[model_singular]]') echo 'active' @endphp"
   href="/[[model_singular]]">[[display_name_singular]]</a>
@endcan
```

#### Remove dead code

```
rm app/Queries/GridQueries/[[controller_name]]Query.php
rm resources/js/components/[[controller_name]]Grid.vue
```

###

Remove from routes

```
Route::get('api/owner-all', '\\App\Queries\GridQueries\OwnerQuery@getAllForSelect');
Route::get('api/owner-one', '\\App\Queries\GridQueries\OwnerQuery@selectOne');
```

vi app/Http/Controllers/ApiController.php

Remove the Grid Method

```
// Begin Owner Api Data Grid Method

public function ownerData(Request $request)
{

return GridQuery::sendData($request, 'OwnerQuery');

}

// End Owner Api Data Grid Method
```

#### Code Cleanup

```
app/Exports/[[controller_name]]Export.php
app/Http/Controlers/[[controller_name]]Controler.php
app/Http/Controlers/[[controller_name]]Api.php
app/Http/Requests/[[controller_name]]FormRequest.php
app/Http/Requests/[[controller_name]]IndexRequest.php
app/Lib/Import/Import[[controller_name]].php
app/Observers/[[controller_name]]Observer.php
app/[[model_uc]].php
resources/js/components/[[tablename]]
resources/views/[[tablename]]

node_modules/.bin/prettier --write resources/js/components/[[tablename]]/" . [[modelname]] . 'Grid.vue'
node_modules/.bin/prettier --write resources/js/components/[[tablename]]/" . [[modelname]] . 'Form.vue'
node_modules/.bin/prettier --write resources/js/components/[[tablename]]/" . [[modelname]] . 'Show.vue'
```




## Vue component example.
```
<ui-select-pick-one
    url="/api-[[view_folder]]/options"
    v-model="[[model_singular]]Selected"
    :selected_id=[[model_singular]]Selected"
    name="[[model_singular]]">
</ui-select-pick-one>
```
## Blade component example.

### In Controller

```
$[[model_singular]]_options = \App\[[model_uc]]::getOptions();
```


### In View

```
@component('../components/select-pick-one', [
'fld' => '[[model_singular]]_id',
'selected_id' => $RECORD->[[model_singular]]_id,
'first_option' => 'Select a [[model_uc_plural]]',
'options' => $[[model_singular]]_options
])
@endcomponent
```

