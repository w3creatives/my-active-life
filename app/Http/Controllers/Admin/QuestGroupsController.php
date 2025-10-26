<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FitLifeActivityGroup;
use App\Utilities\DataTable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class QuestGroupsController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {

        if ($request->ajax()) {

            $query = FitLifeActivityGroup::select(['id', 'name', 'logo']);

            [$itemCount, $items] = $dataTable->setSearchableColumns(['name'])->query($request, $query)->response();

            $items = $items->map(function ($item) {
                $item->logo = view('admin.quests.groups.actions.logo', compact('item'))->render();
                $item->action = [
                    view('admin.quests.groups.actions.action', compact('item'))->render(),
                ];

                return $item;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }

        return view('admin.quests.groups.list');
    }

    public function create(Request $request, $id = null)
    {

        $item = FitLifeActivityGroup::find($id);

        return view('admin.quests.groups.create', compact('item'));
    }

    public function store(Request $request, $id = null)
    {

        $item = FitLifeActivityGroup::find($id);

        $request->validate([
            'name' => [
                'required',
                Rule::unique('fit_life_activity_groups')->ignore($item),
            ],
        ],
            [
                'name.unique' => 'Quest group name already exists',
            ],
        );

        $data = $request->only('name');

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = 'fitlife_group_'.time().'_'.uniqid().'.'.$logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/fitlife-groups', $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }

        if ($item) {
            $item->fill($data)->save();
            $message = 'Quest group updated successfully.';
        } else {
            FitLifeActivityGroup::create($data);
            $message = 'Quest group created successfully.';
        }

        return redirect()->route('admin.quests.groups')->with('alert', ['type' => 'success', 'message' => $message]);
    }
}
