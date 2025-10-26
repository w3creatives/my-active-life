<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FitLifeActivityCategory;
use App\Utilities\DataTable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class QuestCategoriesController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {

        if ($request->ajax()) {

            $query = FitLifeActivityCategory::select(['id', 'name', 'logo']);

            [$itemCount, $items] = $dataTable->setSearchableColumns(['name'])->query($request, $query)->response();

            $items = $items->map(function ($item) {
                $item->logo = view('admin.quests.categories.actions.logo', compact('item'))->render();
                $item->action = [
                    view('admin.quests.categories.actions.action', compact('item'))->render(),
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

        return view('admin.quests.categories.list');
    }

    public function create(Request $request, $id = null)
    {

        $item = FitLifeActivityCategory::find($id);

        return view('admin.quests.categories.create', compact('item'));
    }

    public function store(Request $request, $id = null)
    {

        $item = FitLifeActivityCategory::find($id);

        $request->validate([
            'name' => [
                'required',
                Rule::unique('fit_life_activity_categories')->ignore($item),
            ],
        ],
            [
                'name.unique' => 'Quest category name already exists',
            ],
        );

        $data = $request->only('name');

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = 'fitlife_category_'.time().'_'.uniqid().'.'.$logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/fitlife-categories', $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }

        if ($item) {
            $item->fill($data)->save();
            $message = 'Quest category updated successfully.';
        } else {
            FitLifeActivityCategory::create($data);
            $message = 'Quest category created successfully.';
        }

        return redirect()->route('admin.quests.categories')->with('alert', ['type' => 'success', 'message' => $message]);
    }
}
