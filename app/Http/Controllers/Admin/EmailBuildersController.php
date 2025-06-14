<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Utilities\DataTable;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\EmailTemplate;

class EmailBuildersController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {

        if ($request->ajax()) {
            $query = EmailTemplate::select(['id', 'name', 'subject', 'created_at', 'updated_at']);

            list($itemCount, $items) = $dataTable->setSearchableColumns(['name', 'subject'])->query($request, $query)->response();

            $events = $items->map(function ($item) {
                $item->created = Carbon::parse($item->created_at)->format('m/d/Y h:i A');

                if ($item->updated_at) {
                    $item->updated = Carbon::parse($item->updated_at)->format('m/d/Y h:i A');
                } else {
                    $item->updated = '---';
                }
                $item->action = [
                    view('admin.email-builders.actions.template', compact('item'))->render()
                ];
                return $item;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items
            ]);
        }

        return view('admin.email-builders.list');
    }

    public function create(Request $request)
    {

        $templateId = $request->route()->parameter('id');

        $emailBuilder = EmailTemplate::find($templateId);

        return view('admin.email-builders.create', compact('emailBuilder'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'subject' => 'required',
            'content' => 'required',
        ]);

        $templateId = $request->route()->parameter('id');

        $emailBuilder = EmailTemplate::find($templateId);

        $data = $request->only('name', 'subject', 'content');

        if ($emailBuilder) {
            $data['updated_at'] = Carbon::now();
            $emailBuilder->fill($data)->save();
            return redirect()->route('admin.email.builders')->with('alert', ['type' => 'success', 'message' => 'Email Template updated successfully']);
        }

        EmailTemplate::create($data);

        return redirect()->route('admin.email.builders')->with('alert', ['type' => 'success', 'message' => 'Email Template created successfully']);
    }

    public function destroy(Request $request)
    {
        EmailTemplate::destroy($request->route()->getParameter('id'));

        return redirect()->route('admin.email.builders')->with('alert', ['type' => 'success', 'message' => 'Email Template deleted successfully']);
    }
}
