<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Event;
use App\Utilities\DataTable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ClientsController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {
        if ($request->ajax()) {

            $query = Client::select(['name', 'logo', 'address', 'id', 'is_active']);

            [$itemCount, $clients] = $dataTable->setSearchableColumns(['name', 'address'])
                ->query($request, $query)->response();

            $clients = $clients->map(function ($client) {
                $client->logo = sprintf('<img class="img-thumbnail img-rounded" src="%s"/>', $client->logo_url);
                $client->address = sprintf('<span class="text-break">%s</span>', $client->address);
                $client->action = [
                    view('admin.clients.actions.client', compact('client'))->render(),
                ];

                return $client;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $clients,
            ]);

        }

        return view('admin.clients.list');
    }

    public function create(Request $request)
    {
        $client = Client::find($request->route()->parameter('id'));

        return view('admin.clients.create', compact('client'));
    }

    public function store(Request $request)
    {

        $client = Client::find($request->route()->parameter('id'));

        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'logo' => [
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:2048',
            ],
            'is_active' => 'required|boolean',
        ]);

        $data = $request->only(['name', 'address', 'is_active']);

        $data['is_active'] = (int) $data['is_active'] === 1;

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = 'client_'.time().'_'.uniqid().'.'.$logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/clients', $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }

        $flashMessage = sprintf('Client details %s successfully.!.', $client ? 'updated' : 'created');

        if ($client) {
            $client->fill($data)->save();
        } else {
            $client = Client::create($data);
        }

        return redirect()->route('admin.clients')->with('alert', ['type' => 'success', 'message' => $flashMessage]);

    }
}
