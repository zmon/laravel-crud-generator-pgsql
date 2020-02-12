<?php

namespace [[appns]]Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class [[model_uc]]FormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->route('[[model_singular]]')) {  // If ID we must be changing an existing record
            return Auth::user()->can('[[model_singular]] edit');
        } else {  // If not we must be adding one
            return Auth::user()->can('[[model_singular]] add');
        }

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $id = $this->route('[[model_singular]]');

        $rules = [
            //  Ignore duplicate email if it is this record
            //   'email' => 'required|string|email|unique:invites,email,' . $id . '|unique:users|max:191',


            [[foreach:columns]]
[[if:i . name != 'name']]
            '[[i.name]]' => '[[i.validation]]',
[[endif]]
[[endforeach]]

        ];

[[foreach:columns]]
[[if:i.name == 'name']]
                $organization_id = session('organization_id', 0);

                if ($this->route('[[model_singular]]')) {  // If ID we must be changing an existing record
                    $rules['name'] = 'required|string|max:60|unique:[[tablename]],name,' . $id . ',id,organization_id,' . $organization_id;
                } else {  // If not we must be adding one
                    $rules['name'] = 'required|string|max:60|unique:[[tablename]],name,NULL,id,organization_id,' . $organization_id;
                }
[[endif]]
[[endforeach]]

        return $rules;
    }
}


