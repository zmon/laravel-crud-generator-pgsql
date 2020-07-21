<?php

namespace [[appns]]Http\Controllers;

use [[appns]][[model_uc]];
use App\Exports\[[model_uc]]Export;
use [[appns]]Http\Middleware\TrimStrings;
use [[appns]]Http\Requests\[[model_uc]]FormRequest;
use [[appns]]Http\Requests\[[model_uc]]IndexRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
//use PDF; // TCPDF, not currently in use

class [[model_uc]]Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index([[model_uc]]IndexRequest $request)
    {

        if (!$request->user()->can('[[model_singular]] index')) {
            $request->session()->flash('flash_error_message', 'You do not have access to [[display_name_singular]]s.');
            return Redirect::route('home');
        }

        // Remember the search parameters, we saved them in the Query
        $page = session('[[model_singular]]_page', '');
        $search = session('[[model_singular]]_keyword', '');
        $column = session('[[model_singular]]_column', 'name');
        $direction = session('[[model_singular]]_direction', '-1');

        $can_add = $request->user()->can('[[model_singular]] add');
        $can_show = $request->user()->can('[[model_singular]] view');
        $can_edit = $request->user()->can('[[model_singular]] edit');
        $can_delete = $request->user()->can('[[model_singular]] delete');
        $can_excel = $request->user()->can('[[model_singular]] export-excel');
        $can_pdf = $request->user()->can('[[model_singular]] export-pdf');

        return view('[[view_folder]].index', compact('page', 'column', 'direction', 'search', 'can_add', 'can_edit', 'can_delete', 'can_show', 'can_excel', 'can_pdf'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function create(Request $request)
    {
        if (!$request->user()->can('[[model_singular]] add')) {  // TODO: add -> create
            $request->session()->flash('flash_error_message', 'You do not have access to add a [[display_name_singular]].');
            if ($request->user()->can('[[model_singular]] index')) {
                return Redirect::route('[[view_folder]].index');
            } else {
                return Redirect::route('home');
            }
        }

        $cancel_url = Redirect::back()->getTargetUrl();
        return view('[[view_folder]].create', compact('cancel_url'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store([[model_uc]]FormRequest $request)
    {
        $[[model_singular]] = new \App\[[model_uc]];

        try {
            $attributes = $request->validated();
            $attributes['organization_id'] = session('organization_id', 0);
            unset($attributes['id']);
            $[[model_singular]]->add($attributes);
        } catch (\Exception $e) {
            info(__METHOD__ . ' ' . $e->getMessage());
            return response()->json([
                'message' => 'Unable to process request',
            ], 400);
        }

        $request->session()->flash('flash_success_message', '[[display_name_singular]] ' . $[[model_singular]]->name . ' was added.');

        return response()->json([
            'message' => 'Added record',
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if (!$request->user()->can('[[model_singular]] view')) {
            $request->session()->flash('flash_error_message', 'You do not have access to view a [[display_name_singular]].');
            if ($request->user()->can('[[model_singular]] index')) {
                return Redirect::route('[[view_folder]].index');
            } else {
                return Redirect::route('home');
            }
        }

        if ($[[model_singular]] = $this->sanitizeAndFind($id)) {
            $can_edit = $request->user()->can('[[model_singular]] edit');
            $can_delete = ($request->user()->can('[[model_singular]] delete') && $[[model_singular]]->canDelete());
            return view('[[view_folder]].show', compact('[[model_singular]]', 'can_edit', 'can_delete'));
        } else {
            $request->session()->flash('flash_error_message', 'Unable to find [[display_name_singular]] to display.');
            return Redirect::route('[[view_folder]].index');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, $id)
    {
        if (!$request->user()->can('[[model_singular]] edit')) {
            $request->session()->flash('flash_error_message', 'You do not have access to edit a [[display_name_singular]].');
            if ($request->user()->can('[[model_singular]] index')) {
                return Redirect::route('[[view_folder]].index');
            } else {
                return Redirect::route('home');
            }
        }

        if ($[[model_singular]] = $this->sanitizeAndFind($id)) {
            $cancel_url = Redirect::back()->getTargetUrl();
            return view('[[view_folder]].edit', compact('[[model_singular]]', 'cancel_url'));
        } else {
            $request->session()->flash('flash_error_message', 'Unable to find [[display_name_singular]] to edit.');
            return Redirect::route('[[view_folder]].index');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param \App\[[model_uc]] $[[model_singular]]
     * @return \Illuminate\Http\Response
     */
    public function update([[model_uc]]FormRequest $request, $id)
    {
        if (!$[[model_singular]] = $this->sanitizeAndFind($id)) {
            return response()->json([
                'message' => 'Not Found',
            ], 404);
        }

        $attributes = $request->all();
        $attributes['organization_id'] = session('organization_id', 0);
        $[[model_singular]]->fill($attributes);

        if ($[[model_singular]]->isDirty()) {

            try {
                $[[model_singular]]->save();
            } catch (\Exception $e) {
                info(__METHOD__ . ' ' . $e->getMessage());
                return response()->json([
                    'message' => 'Unable to process request',
                ], 400);
            }

            $request->session()->flash('flash_success_message', '[[display_name_singular]] ' . $[[model_singular]]->name . ' was changed.');
        } else {
            $request->session()->flash('flash_info_message', 'No changes were made.');
        }

        return response()->json([
            'message' => 'Changed record',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\[[model_uc]] $[[model_singular]]
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('[[model_singular]] delete')) {
            $request->session()->flash('flash_error_message', 'You do not have access to remove a [[display_name_singular]].');
            if ($request->user()->can('[[model_singular]] index')) {
                return Redirect::route('[[view_folder]].index');
            } else {
                return Redirect::route('home');
            }
        }

        if (!$[[model_singular]] = $this->sanitizeAndFind($id)) {
            $request->session()->flash('flash_error_message', 'Unable to find [[display_name_singular]] to delete.');
        } else {

            if ($[[model_singular]] && $[[model_singular]]->canDelete()) {

                try {
                    $[[model_singular]]->delete();
                } catch (\Exception $e) {
                    info(__METHOD__ . ' ' . $e->getMessage());
                    return response()->json([
                        'message' => 'Unable to process request.',
                    ], 400);
                }
                $request->session()->flash('flash_success_message', '[[display_name_singular]] ' . $[[model_singular]]->name . ' was removed.');
            } else {
                $request->session()->flash('flash_error_message', 'Unable to find [[display_name_singular]] to delete.');
            }
        }

        if ($request->user()->can('[[model_singular]] index')) {
            return Redirect::route('[[view_folder]].index');
        } else {
            return Redirect::route('home');
        }
    }

    /**
     * Find by ID, sanitize the ID first.
     *
     * @param $id
     * @return [[model_uc]] or null
     */
    private function sanitizeAndFind($id)
    {
        return \App\[[model_uc]]::where('organization_id',session('organization_id', 0))
            ->where('id',intval($id))
            ->first();
    }

    public function download(Request $request)
    {

        if (!$request->user()->can('[[model_singular]] export-excel')) {
            $request->session()->flash('flash_error_message', 'You do not have access to download [[display_name_plural]].');
            if ($request->user()->can('[[model_singular]] index')) {
                return Redirect::route('[[view_folder]].index');
            } else {
                return Redirect::route('home');
            }
        }

        // Remember the search parameters, we saved them in the Query
        $search = session('[[model_singular]]_keyword', '');
        $column = session('[[model_singular]]_column', 'name');
        $direction = session('[[model_singular]]_direction', '-1');

        $column = $column ? $column : 'name';

        // #TODO wrap in a try/catch and display english message on failuer.

        $dataQuery = [[model_uc]]::exportDataQuery($column, $direction, $search);
            //dump($data->toArray());
            //if ($data->count() > 0) {

            // TODO: is it possible to do 0 check before query executes somehow? i think the query would have to be executed twice, once for count, once for excel library
            return Excel::download(
                new [[model_uc]]Export($dataQuery),
                '[[view_folder]].xlsx');
    }

    public function print(Request $request)
    {
            if (!$request->user()->can('[[model_singular]] export-pdf')) { // TODO: i think these permissions may need to be updated to match initial permissions?
                $request->session()->flash('flash_error_message', 'You do not have access to print [[display_name_plural]].');
                if ($request->user()->can('[[model_singular]] index')) {
                    return Redirect::route('[[view_folder]].index');
                } else {
                    return Redirect::route('home');
                }
            }

            // Remember the search parameters, we saved them in the Query
            $search = session('[[model_singular]]_keyword', '');
            $column = session('[[model_singular]]_column', 'name');
            $direction = session('[[model_singular]]_direction', '-1');
            $column = $column ? $column : 'name';


            // Get query data
            $columns = [
                [[foreach:grid_columns]]
                '[[i.name]]',
[[endforeach]]
        ];
        $dataQuery = [[model_uc]]::pdfDataQuery($column, $direction, $search, $columns);
        $data = $dataQuery->get();

        // Pass it to the view for html formatting:
        $printHtml = view('[[view_folder]].print', compact('data'));

        // Begin DOMPDF/laravel-dompdf
        $pdf = \App::make('dompdf.wrapper');
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions(['isPhpEnabled' => true]);
        $pdf->loadHTML($printHtml);
        $currentDate = new \DateTime(null, new \DateTimeZone('America/Chicago'));
        return $pdf->stream('[[view_folder]]-' . $currentDate->format('Ymd_Hi') . '.pdf');

    }
}
