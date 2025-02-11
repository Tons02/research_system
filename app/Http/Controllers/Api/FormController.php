<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Form;
use App\Http\Requests\FormsRequest;

class FormController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');

        $Form = Form::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        return $this->responseSuccess('Form display successfully', $Form);
    }

    public function show(Request $request, $id)
    {
        $status = $request->query('status');

        $Form = Form::where('id', $id)->get();

        if($Form->isEmpty()){
            return $this->responseUnprocessable('', 'Invalid ID');
        }

        return $this->responseSuccess('Single form display successfully', $Form);
    }

    public function store(FormsRequest $request)
    {
        $create_form = Form::create([
            "title" => $request->title,
            "description" => $request->description,
            "sections" => $request->sections,
        ]);


        return $this->responseCreated('Form Successfully Created', $create_form);
    }

    public function update(FormsRequest $request, $id)
    {
        $form_id = Form::find($id);

        if (!$form_id) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $form_id->title = $request['title'];
        $form_id->description = $request['description'];
        $form_id->sections = ($request->has('sections') && $request['sections'] !== null) ? $request['sections'] : $form_id->sections;

        if (!$form_id->isDirty()) {
            return $this->responseSuccess('No Changes', $form_id);
        }

        $form_id->save();

        return $this->responseSuccess('Form successfully updated', $form_id);
    }

    public function archived(Request $request, $id)
    {
        $form = Form::withTrashed()->find($id);

        if (!$form) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
        }

        if ($form->deleted_at) {

            $form->restore();

            return $this->responseSuccess('Form successfully restore', $form);
        }

        // need to put this one once the survey answer is already created
        // if (Survey::where('form_id', $id)->exists()) {
        //     return $this->responseUnprocessable('', 'Unable to Archive, Form already in used!');
        // }

        if (!$form->deleted_at) {

            $form->delete();

            return $this->responseSuccess('form successfully archive', $form);
        }
    }
}
